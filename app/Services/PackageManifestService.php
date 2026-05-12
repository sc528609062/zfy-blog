<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

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
        $required = ['name', 'slug', 'version', 'compatible', 'permissions', 'events'];

        foreach ($required as $key) {
            if (! array_key_exists($key, $manifest)) {
                $errors[] = "缺少 {$key}";
            }
        }

        foreach ($files as $file) {
            $normalized = str_replace('\\', '/', $file);
            if (str_starts_with($normalized, '../') || str_contains($normalized, '/app/') || str_contains($normalized, '/vendor/')) {
                $errors[] = "禁止插件覆盖核心路径：{$file}";
            }
        }

        return $errors;
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

                $payload = json_decode(File::get($path), true) ?: [];
                $payload['_path'] = $path;

                return [basename($directory) => $payload];
            })
            ->all();
    }
}
