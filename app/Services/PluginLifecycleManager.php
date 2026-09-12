<?php

namespace App\Services;

use App\Models\Plugin;
use App\Models\Theme;
use App\Support\Zfy\PluginLifecycle;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

class PluginLifecycleManager
{
    public function __construct(private readonly PackageManifestService $packages) {}

    public function ordered(array $slugs): array
    {
        $manifests = $this->packages->plugins();
        $visited = [];
        $visiting = [];
        $result = [];
        $visit = function ($slug) use (&$visit, &$visited, &$visiting, &$result, $slugs, $manifests) {
            if (isset($visited[$slug])) {
                return;
            }
            if (isset($visiting[$slug])) {
                $this->fail('Circular plugin dependency: '.$slug);
            }
            $manifest = $manifests[$slug] ?? null;
            if (! $manifest) {
                $this->fail('Plugin manifest is missing: '.$slug);
            }
            $errors = $this->packages->validatePluginPayload($manifest);
            if ($errors) {
                $this->fail(implode('; ', $errors));
            }
            $this->packages->assertDependencies($manifest, $slugs);
            $visiting[$slug] = true;
            foreach ($manifest['requires']['plugins'] ?? [] as $dependency => $constraint) {
                if (! in_array($dependency, $slugs, true) || ! $this->packages->satisfies($manifests[$dependency]['version'] ?? '', $constraint)) {
                    $this->fail('Unsatisfied dependency: '.$dependency.' '.$constraint);
                }
                $visit($dependency);
            }
            unset($visiting[$slug]);
            $visited[$slug] = true;
            $result[] = $slug;
        };
        foreach ($slugs as $slug) {
            $visit($slug);
        }

        return $result;
    }

    public function provider(string $slug, ?string $archive = null): ?ServiceProvider
    {
        return app(ExtensionCodeLoader::class)->provider('plugin', $slug, $archive);
    }

    public function toggle(Plugin $plugin, bool $enabled): void
    {
        Cache::store('file')->lock('zfy-plugin-lifecycle', 300)->block(5, function () use ($plugin, $enabled) {
            $plugin->refresh();
            if ($plugin->enabled === $enabled) {
                return;
            }
            if ($enabled) {
                $this->ordered(array_values(array_unique([...Plugin::where('enabled', true)->pluck('slug')->all(), $plugin->slug])));
            } else {
                $activeTheme = Theme::where('is_active', true)->value('slug');
                $activeThemes = $activeTheme ? app(ThemePackageLoader::class)->chain($activeTheme) : [];
                foreach ($this->packages->themes() as $slug => $manifest) {
                    if (isset($manifest['requires']['plugins'][$plugin->slug]) && in_array($slug, $activeThemes, true)) {
                        $this->fail('Switch dependent theme first: '.$slug);
                    }
                }
                foreach ($this->packages->plugins() as $slug => $manifest) {
                    if (isset($manifest['requires']['plugins'][$plugin->slug]) && Plugin::where('slug', $slug)->where('enabled', true)->exists()) {
                        $this->fail('Deactivate dependent plugin first: '.$slug);
                    }
                }
            }
            app(ExtensionMaintenance::class)->run(function () use ($plugin, $enabled) {
                try {
                    if ($enabled && ! app()->runningUnitTests()) {
                        $process = new Process([PHP_BINARY, '-d', 'disable_functions=', base_path('scripts/extension-preflight.php'), $plugin->slug], base_path(), ['ZFY_SAFE_MODE' => 'true']);
                        $process->setTimeout(30)->run();
                        if (! $process->isSuccessful()) {
                            $this->fail('Plugin startup preflight failed. Check the private application log.');
                        }
                    }
                    $provider = $this->provider($plugin->slug);
                    if ($enabled && is_dir(base_path('plugins/'.$plugin->slug.'/database/migrations'))) {
                        if (Artisan::call('migrate', ['--path' => base_path('plugins/'.$plugin->slug.'/database/migrations'), '--realpath' => true, '--force' => true]) !== 0) {
                            $this->fail('Plugin migration failed.');
                        }
                    }
                    if ($provider instanceof PluginLifecycle) {
                        $previous = data_get($plugin->settings()->where('key', '_lifecycle.previous_version')->first()?->value, 'raw');
                        if ($enabled && $previous) {
                            $provider->upgrade($previous);
                        }
                        $enabled ? $provider->activate() : $provider->deactivate();
                    }
                    DB::transaction(function () use ($plugin, $enabled) {
                        $plugin->update(['enabled' => $enabled]);
                        if ($enabled) {
                            $plugin->settings()->whereIn('key', ['_lifecycle.previous_version', '_lifecycle.previous_directory'])->delete();
                        }
                        zfy_after_commit($enabled ? 'zfy_plugin_activated' : 'zfy_plugin_deactivated', $plugin);
                    });
                    $this->record($plugin->slug, $enabled ? 'activated' : 'deactivated');
                } catch (\Throwable $exception) {
                    $this->record($plugin->slug, 'failed', $exception::class);
                    throw $exception;
                }
            }, $enabled ? fn () => $this->restorePreviousFiles($plugin) : null, ['plugin' => $plugin->slug]);
        });
    }

    public function uninstall(Plugin $plugin, bool $deleteData = false): void
    {
        if ($deleteData) {
            $this->fail('Uninstall preserves data. Use the separate purge operation.');
        }
        Cache::store('file')->lock('zfy-plugin-lifecycle', 300)->block(5, fn () => $this->uninstallLocked($plugin));
    }

    private function uninstallLocked(Plugin $plugin): void
    {
        $plugin->refresh();
        if ($plugin->enabled) {
            $this->fail('Deactivate the plugin before uninstalling.');
        }
        foreach ($this->packages->plugins() as $slug => $manifest) {
            if (isset($manifest['requires']['plugins'][$plugin->slug]) && Plugin::where('slug', $slug)->where('enabled', true)->exists()) {
                $this->fail('Deactivate dependent plugin first: '.$slug);
            }
        }
        $root = realpath(base_path('plugins'));
        $target = realpath(base_path('plugins/'.$plugin->slug));
        if (! $root || ! $target || dirname($target) !== $root) {
            $this->fail('Invalid plugin directory.');
        }
        $archive = storage_path('app/private/extensions/uninstalled/'.$plugin->slug.'-'.now()->format('YmdHis').'-'.bin2hex(random_bytes(4)));
        File::ensureDirectoryExists(dirname($archive));
        $context = ['plugin' => $plugin->slug, 'uninstall_archive' => $archive];
        app(ExtensionMaintenance::class)->run(function () use ($plugin, $target, $archive) {
            $provider = $this->provider($plugin->slug);
            if ($provider instanceof PluginLifecycle) {
                $provider->uninstall(false);
            }
            if (! File::moveDirectory($target, $archive)) {
                $this->fail('Cannot archive plugin files.');
            }
            $plugin->settings()->updateOrCreate(['key' => '_lifecycle.uninstalled'], ['value' => ['raw' => true]]);
            $plugin->settings()->updateOrCreate(['key' => '_lifecycle.uninstalled_archive'], ['value' => ['raw' => $archive]]);
            $this->record($plugin->slug, 'uninstalled');
            zfy_after_commit('zfy_plugin_uninstalled', $plugin);
        }, fn () => $this->restoreUninstalledFiles($plugin->slug, $archive), $context);
    }

    public function purge(Plugin $plugin): void
    {
        Cache::store('file')->lock('zfy-plugin-lifecycle', 300)->block(5, function () use ($plugin) {
            $plugin->refresh();
            if ($plugin->enabled || is_dir(base_path('plugins/'.$plugin->slug)) || ! $plugin->settings()->where('key', '_lifecycle.uninstalled')->exists()) {
                $this->fail('Only uninstalled plugin data can be purged.');
            }
            $archive = data_get($plugin->settings()->where('key', '_lifecycle.uninstalled_archive')->first()?->value, 'raw');
            if (! is_string($archive)) {
                $this->fail('Reinstall and uninstall this plugin to prepare data cleanup.');
            }
            app(ExtensionMaintenance::class)->run(function () use ($plugin, $archive) {
                $provider = $this->provider($plugin->slug, $archive);
                if ($provider instanceof PluginLifecycle) {
                    $provider->uninstall(true);
                }
                DB::transaction(function () use ($plugin) {
                    $plugin->settings()->delete();
                    $plugin->delete();
                    zfy_after_commit('zfy_plugin_data_deleted', $plugin);
                });
                $this->record($plugin->slug, 'data-deleted');
            });
        });
    }

    public function restoreUninstalledFiles(string $slug, string $archive): void
    {
        $source = realpath($archive);
        if (! $source) {
            return;
        }
        if (dirname($source) !== realpath(storage_path('app/private/extensions/uninstalled')) || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $this->fail('Invalid uninstall recovery path.');
        }
        $manifest = json_decode(File::get($source.'/plugin.json'), true, 64, JSON_THROW_ON_ERROR);
        if (($manifest['slug'] ?? '') !== $slug) {
            $this->fail('Invalid uninstall recovery identity.');
        }
        $target = base_path('plugins/'.$slug);
        if (is_dir($target) || ! File::moveDirectory($source, $target)) {
            $this->fail('Cannot restore uninstalled plugin files.');
        }
    }

    public function record(string $slug, string $status, ?string $error = null): void
    {
        $directory = storage_path('app/private/extensions');
        File::ensureDirectoryExists($directory);
        File::append($directory.'/lifecycle.jsonl', json_encode(['slug' => $slug, 'status' => $status, 'error' => $error, 'time' => now()->toIso8601String()])."\n");
    }

    public function restorePreviousFiles(Plugin $plugin): void
    {
        $plugin->refresh();
        $previous = data_get($plugin->settings()->where('key', '_lifecycle.previous_directory')->first()?->value, 'raw');
        $root = realpath(storage_path('app/private/extensions/versions'));
        $previous = is_string($previous) ? realpath($previous) : false;
        if (! $root || ! $previous || dirname($previous) !== $root) {
            return;
        }
        $manifest = json_decode(File::get($previous.'/plugin.json'), true, 64, JSON_THROW_ON_ERROR);
        if (($manifest['slug'] ?? '') !== $plugin->slug) {
            $this->fail('Invalid previous plugin identity.');
        }
        $target = base_path('plugins/'.$plugin->slug);
        $failed = storage_path('app/private/extensions/versions/failed-'.$plugin->slug.'-'.bin2hex(random_bytes(8)));
        if (! File::moveDirectory($target, $failed)) {
            $this->fail('Cannot archive failed plugin version.');
        }
        if (! File::moveDirectory($previous, $target)) {
            File::moveDirectory($failed, $target);
            $this->fail('Cannot restore previous plugin files.');
        }
        $plugin->update(['version' => $manifest['version'], 'provider' => $manifest['provider'] ?? null, 'name' => $manifest['name'], 'permissions' => $manifest['permissions'] ?? [], 'events' => $manifest['events'] ?? [], 'enabled' => false]);
        $plugin->settings()->whereIn('key', ['_lifecycle.previous_version', '_lifecycle.previous_directory'])->delete();
        app(PackageIntegrity::class)->record('plugin', $plugin->slug);
        $this->record($plugin->slug, 'upgrade-rolled-back');
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['plugin' => $message]);
    }
}
