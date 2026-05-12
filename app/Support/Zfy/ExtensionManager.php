<?php

namespace App\Support\Zfy;

use App\Models\Plugin;
use App\Services\PackageManifestService;
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
        if (! Schema::hasTable('plugins')) {
            return;
        }

        $manifests = $this->packages->plugins();

        foreach ($manifests as $manifest) {
            $this->registerManifest($manifest);
        }

        Plugin::query()->where('enabled', true)->get()->each(function (Plugin $plugin): void {
            $this->bootProvider($plugin->provider);
        });
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
                'key' => 'plugin.'.$groupKey,
                'label' => $group['label'] ?? $groupKey,
                'description' => $group['description'] ?? '',
                'scope' => 'plugin',
                'position' => $group['position'] ?? 200,
            ]);

            foreach ($group['fields'] ?? [] as $field) {
                if (is_array($field)) {
                    $this->settings->setting(array_replace($field, [
                        'group' => 'plugin.'.$groupKey,
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
