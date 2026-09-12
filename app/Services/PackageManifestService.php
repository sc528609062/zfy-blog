<?php

namespace App\Services;

use App\Models\Plugin;
use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;

class PackageManifestService
{
    public function themes(): array
    {
        return $this->manifests(base_path('themes'), 'theme.json');
    }

    public function plugins(): array
    {
        return $this->manifests(base_path('plugins'), 'plugin.json');
    }

    public function validatePluginPayload(array $manifest, array $files = []): array
    {
        $errors = [];
        if (array_key_exists('schema_version', $manifest)) {
            $errors = $this->validateSchema($manifest, 'plugin');
        }
        $required = ['name', 'slug', 'version', 'compatible', 'permissions', 'events'];

        foreach ($required as $key) {
            if (! array_key_exists($key, $manifest)) {
                $errors[] = "缺少 {$key}";
            }
        }
        foreach (['name', 'slug', 'version', 'compatible'] as $key) {
            if (! is_string($manifest[$key] ?? null) || trim($manifest[$key]) === '') {
                $errors[] = "{$key} 必须是非空字符串";
            }
        }
        foreach (['permissions', 'events'] as $key) {
            if (! is_array($manifest[$key] ?? null)) {
                $errors[] = "{$key} 必须是数组";
            }
        }
        if (isset($manifest['compatible']) && is_string($manifest['compatible'])) {
            if (! $this->satisfies(config('zfy.version'), $manifest['compatible'])) {
                $errors[] = '插件与当前系统版本不兼容';
            }
        }

        if (! is_string($manifest['slug'] ?? null) || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $manifest['slug']) || strlen($manifest['slug']) > 80) {
            $errors[] = 'Invalid package slug';
        }
        if (! is_string($manifest['version'] ?? null) || ! $this->satisfies($manifest['version'], '*')) {
            $errors[] = 'Invalid package version';
        }
        if (($manifest['schema_version'] ?? 1) !== 1) {
            $errors[] = 'Unsupported manifest schema version';
        }
        if (isset($manifest['requires']) && ! is_array($manifest['requires'])) {
            $errors[] = 'requires must be an object';

            return $errors;
        }
        if (isset($manifest['requires']['php']) && (! is_string($manifest['requires']['php']) || ! $this->satisfies(PHP_VERSION, $manifest['requires']['php']))) {
            $errors[] = 'PHP version requirement is not satisfied';
        }
        foreach (['permissions', 'events'] as $field) {
            foreach (is_array($manifest[$field] ?? null) ? $manifest[$field] : [] as $value) {
                if (! is_string($value)) {
                    $errors[] = $field.' values must be strings';
                }
            }
        }
        foreach (['entry', 'provider', 'parent'] as $field) {
            if (isset($manifest[$field]) && (! is_string($manifest[$field]) || trim($manifest[$field]) === '')) {
                $errors[] = $field.' must be a nonempty string';
            }
        }
        foreach (['plugins', 'themes'] as $kind) {
            $dependencies = $manifest['requires'][$kind] ?? [];
            if (! is_array($dependencies)) {
                $errors[] = 'Invalid dependency map';

                continue;
            }
            foreach ($dependencies as $slug => $constraint) {
                if (! is_string($slug) || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) || ! is_string($constraint)) {
                    $errors[] = 'Invalid dependency declaration';

                    continue;
                }
                try {
                    (new VersionParser)->parseConstraints($constraint);
                } catch (\Throwable) {
                    $errors[] = 'Invalid dependency constraint: '.$slug;
                }
            }
        }
        foreach (['settings_schema', 'admin_pages', 'autoload', 'assets', 'update'] as $field) {
            if (isset($manifest[$field]) && ! is_array($manifest[$field])) {
                $errors[] = $field.' must be an object or array';
            }
        }

        foreach ($files as $file) {
            $normalized = str_replace('\\', '/', $file);
            if (preg_match('~(^/|(^|/)\.\.(/|$)|:)~', $normalized) || str_contains($normalized, '/app/') || str_contains($normalized, '/vendor/')) {
                $errors[] = "禁止插件覆盖核心路径：{$file}";
            }
        }

        return $errors;
    }

    public function validateSchema(array $manifest, string $type): array
    {
        if (! in_array($type, ['theme', 'plugin'], true)) {
            return ['Unknown manifest type'];
        }
        try {
            $schema = json_decode(File::get(base_path('schemas/'.$type.'.schema.json')), false, 64, JSON_THROW_ON_ERROR);
            $data = json_decode(json_encode($manifest, JSON_THROW_ON_ERROR), false, 64, JSON_THROW_ON_ERROR);
            // PHP associative arrays cannot distinguish empty JSON maps from empty lists.
            foreach (['requires', 'autoload', 'settings_schema', 'assets', 'defaults', 'update'] as $field) {
                if (isset($data->$field) && $data->$field === []) {
                    $data->$field = new \stdClass;
                }
            }
            foreach (['requires' => ['plugins', 'themes'], 'autoload' => ['psr-4']] as $field => $maps) {
                foreach ($maps as $map) {
                    if (isset($data->$field->$map) && $data->$field->$map === []) {
                        $data->$field->$map = new \stdClass;
                    }
                }
            }
            $result = (new Validator)->validate($data, $schema);

            return $result->isValid() ? [] : array_values((new ErrorFormatter)->formatFlat($result->error()));
        } catch (\Throwable $exception) {
            report($exception);

            return ['Manifest schema validation failed'];
        }
    }

    public function satisfies(string $version, string $constraint): bool
    {
        try {
            return Semver::satisfies($version, $constraint);
        } catch (\Throwable) {
            return false;
        }
    }

    public function assertDependencies(array $manifest, ?array $enabledPlugins = null): void
    {
        $enabledPlugins ??= Plugin::where('enabled', true)->pluck('slug')->all();
        foreach (['plugins' => $this->plugins(), 'themes' => $this->themes()] as $kind => $installed) {
            foreach ($manifest['requires'][$kind] ?? [] as $slug => $constraint) {
                if (! isset($installed[$slug]) || ! $this->satisfies($installed[$slug]['version'], $constraint) || ($kind === 'plugins' && ! in_array($slug, $enabledPlugins, true))) {
                    throw ValidationException::withMessages(['package' => 'Unsatisfied '.$kind.' dependency: '.$slug.' '.$constraint]);
                }
            }
        }
    }

    private function manifests(string $root, string $filename): array
    {
        if (! File::isDirectory($root)) {
            return [];
        }

        return collect(File::directories($root))
            ->mapWithKeys(function (string $directory) use ($filename) {
                $path = $directory.DIRECTORY_SEPARATOR.$filename;

                if (! File::exists($path)) {
                    return [];
                }

                try {
                    $payload = json_decode(File::get($path), true, 64, JSON_THROW_ON_ERROR);
                } catch (\Throwable $exception) {
                    report($exception);

                    return [];
                }
                if (! is_array($payload) || ($payload['slug'] ?? '') !== basename($directory)) {
                    return [];
                }
                $payload['_path'] = $path;

                return [basename($directory) => $payload];
            })
            ->all();
    }
}
