<?php

namespace App\Services;

use App\Models\Theme;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;

class ThemePackageLoader
{
    /** Parent first, with all dependencies checked before any code executes. */
    public function chain(string $slug): array
    {
        $manifests = app(PackageManifestService::class)->themes();
        $chain = [];
        $seen = [];
        while ($slug !== '') {
            if (isset($seen[$slug]) || ! isset($manifests[$slug])) {
                throw ValidationException::withMessages(['theme' => 'Missing or circular parent theme: '.$slug]);
            }
            $seen[$slug] = true;
            $manifest = $manifests[$slug];
            app(PackageManifestService::class)->assertDependencies($manifest);
            $chain[] = $slug;
            $slug = $manifest['parent'] ?? '';
        }

        return array_reverse($chain);
    }

    public function bootActive(): void
    {
        if (config('extensions.safe_mode') || is_file(storage_path('app/private/extensions/safe-mode'))) {
            return;
        }
        if (app()->runningInConsole() && in_array($_SERVER['argv'][1] ?? '', ['zfy:extension', 'zfy:update', 'zfy:extensions-update', 'migrate', 'package:discover'], true)) {
            return;
        }
        if (! Schema::hasTable('themes')) {
            return;
        }
        $slug = Theme::where('is_active', true)->value('slug');
        if (! $slug) {
            return;
        }
        $manifests = app(PackageManifestService::class)->themes();
        $seen = [];
        $boot = function (string $slug) use (&$boot, &$seen, $manifests) {
            if (isset($seen[$slug])) {
                throw new \RuntimeException('Circular theme dependency.');
            }
            $seen[$slug] = true;
            $manifest = $manifests[$slug] ?? throw new \RuntimeException('Active theme is missing.');
            app(PackageManifestService::class)->assertDependencies($manifest);
            if (! empty($manifest['parent'])) {
                $boot($manifest['parent']);
            }
            $provider = app(ExtensionCodeLoader::class)->provider('theme', $slug);
            if ($provider) {
                $provider->register();
                if (method_exists($provider, 'boot')) {
                    app()->call([$provider, 'boot']);
                }
            }
        };
        try {
            $boot($slug);
        } catch (\Throwable $exception) {
            app(PluginLifecycleManager::class)->record('theme:'.$slug, 'boot-failed', $exception::class);
            report($exception);
        }
    }

    public function register(): void
    {
        $manifests = app(PackageManifestService::class)->themes();
        foreach ($manifests as $slug => $manifest) {
            try {
                $paths = $this->paths($slug, $manifests);
                if ($paths !== []) {
                    View::addNamespace('theme-'.$slug, $paths);
                }
                foreach ($manifest['menus'] ?? [] as $key) {
                    if (is_string($key)) {
                        zfy_register_nav_area($key, ['label' => $key]);
                    }
                }
                foreach ($manifest['regions'] ?? [] as $key) {
                    if (is_string($key)) {
                        zfy_register_widget_area($key, ['label' => $key]);
                    }
                }
            } catch (\Throwable $exception) {
                report($exception);
            }
        }
    }

    public function entry(array $manifest): string
    {
        $entry = $manifest['entry'] ?? 'views/layout.blade.php';
        if (! preg_match('~^views/([a-zA-Z0-9_/-]+)\.blade\.php$~', $entry, $matches)) {
            throw ValidationException::withMessages(['theme' => 'Invalid theme entry.']);
        }

        return 'theme-'.$manifest['slug'].'::'.str_replace('/', '.', $matches[1]);
    }

    public function template(string $slug, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (preg_match('/^[a-zA-Z0-9_.-]+$/', $candidate)) {
                $view = 'theme-'.$slug.'::'.$candidate;
                if (View::exists($view)) {
                    return $view;
                }
            }
        }

        return null;
    }

    private function paths(string $slug, array $manifests, array $seen = []): array
    {
        if (in_array($slug, $seen, true)) {
            throw new \RuntimeException('Circular parent theme.');
        }
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return [];
        }
        $manifest = $manifests[$slug] ?? null;
        if (! $manifest) {
            throw new \RuntimeException('Parent theme not found.');
        }
        $root = realpath(base_path('themes/'.$slug));
        $views = realpath(base_path('themes/'.$slug.'/views'));
        $paths = $root && $views && dirname($views) === $root ? [$views] : [];
        if (! empty($manifest['parent'])) {
            $parent = $manifests[$manifest['parent']] ?? null;
            $constraint = $manifest['requires']['themes'][$manifest['parent']] ?? '*';
            if (! $parent || ! app(PackageManifestService::class)->satisfies($parent['version'], $constraint)) {
                throw new \RuntimeException('Parent theme version requirement is not satisfied.');
            }
            $paths = array_merge($paths, $this->paths($manifest['parent'], $manifests, [...$seen, $slug]));
        }

        return $paths;
    }
}
