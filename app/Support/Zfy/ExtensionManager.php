<?php

namespace App\Support\Zfy;

use App\Models\Plugin;
use App\Services\PackageManifestService;
use App\Services\PluginLifecycleManager;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ExtensionManager
{
    public function __construct(
        private readonly PackageManifestService $packages,
        private readonly AdminRegistry $admin,
        private readonly SettingsRegistry $settings,
    ) {}

    public function boot(): void
    {
        if (app()->runningInConsole() && in_array($_SERVER['argv'][1] ?? '', ['zfy:extension', 'zfy:update', 'zfy:extensions-update', 'migrate', 'package:discover'], true)) {
            return;
        }
        if (config('extensions.safe_mode') || is_file(storage_path('app/private/extensions/safe-mode')) || ! Schema::hasTable('plugins')) {
            return;
        }

        $manifests = $this->packages->plugins();

        $manager = app(PluginLifecycleManager::class);
        $enabled = Plugin::where('enabled', true)->pluck('slug')->all();
        try {
            $ordered = $manager->ordered($enabled);
        } catch (Throwable $exception) {
            $manager->record('system', 'dependency-failed', $exception::class);
            report($exception);

            return;
        }
        $failed = [];
        foreach ($ordered as $slug) {
            $manifest = $manifests[$slug];
            if (array_intersect(array_keys($manifest['requires']['plugins'] ?? []), $failed)) {
                $failed[] = $slug;

                continue;
            }
            try {
                $provider = $manager->provider($slug);
                // Boot inside the guarded callback, after Laravel has finished booting core providers.
                if ($provider) {
                    $provider->register();
                    if (method_exists($provider, 'boot')) {
                        app()->call([$provider, 'boot']);
                    }
                }
                $this->registerManifest($manifest);
            } catch (Throwable $exception) {
                $failed[] = $slug;
                $manager->record($slug, 'boot-failed', $exception::class);
                report($exception);
            }
        }
    }

    private function registerManifest(array $manifest): void
    {
        foreach ($manifest['admin_pages'] ?? [] as $page) {
            if (is_array($page)) {
                $this->admin->page(array_replace([
                    'group' => 'plugins',
                    'kind' => 'extension',
                    'status' => 'ready',
                    'permission' => 'manage plugins',
                ], $page));
            }
        }

        foreach ($manifest['settings_schema'] ?? [] as $groupKey => $group) {
            if (! is_array($group)) {
                continue;
            }

            $this->settings->group([
                'key' => 'plugin.'.$manifest['slug'].'.'.$groupKey,
                'label' => $group['label'] ?? $groupKey,
                'description' => $group['description'] ?? '',
                'scope' => 'plugin',
                'position' => $group['position'] ?? 200,
            ]);

            foreach ($group['fields'] ?? [] as $field) {
                if (is_array($field)) {
                    $this->settings->setting(array_replace($field, [
                        'group' => 'plugin.'.$manifest['slug'].'.'.$groupKey,
                    ]));
                }
            }
        }
    }

    private function bootProvider(?string $provider): void
    {
        if (! $provider || ! class_exists($provider)) {
            return;
        }

        try {
            app()->register($provider);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
