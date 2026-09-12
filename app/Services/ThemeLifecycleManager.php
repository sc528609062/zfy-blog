<?php

namespace App\Services;

use App\Models\Plugin;
use App\Models\Theme;
use App\Support\Zfy\ThemeLifecycle;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

class ThemeLifecycleManager
{
    public function activate(Theme $theme, callable $switch): void
    {
        Cache::store('file')->lock('zfy-theme-lifecycle', 300)->block(5, function () use ($theme, $switch) {
            $theme->refresh();
            if ($theme->is_active) {
                return;
            }
            app(ExtensionMaintenance::class)->run(function () use ($theme, $switch) {
                $chain = app(ThemePackageLoader::class)->chain($theme->slug);
                $old = Theme::where('is_active', true)->first();
                $oldChain = $old ? app(ThemePackageLoader::class)->chain($old->slug) : [];
                if (! app()->runningUnitTests()) {
                    $process = new Process([PHP_BINARY, '-d', 'disable_functions=', base_path('scripts/extension-preflight.php'), $theme->slug, 'theme'], base_path(), ['ZFY_SAFE_MODE' => 'true']);
                    $process->setTimeout(30)->run();
                    if (! $process->isSuccessful()) {
                        $this->fail('Theme startup preflight failed.');
                    }
                }
                foreach (array_diff($chain, $oldChain) as $slug) {
                    $member = Theme::where('slug', $slug)->firstOrFail();
                    $provider = app(ExtensionCodeLoader::class)->provider('theme', $slug);
                    $migrations = base_path('themes/'.$slug.'/database/migrations');
                    if (is_dir($migrations) && Artisan::call('migrate', ['--path' => $migrations, '--realpath' => true, '--force' => true]) !== 0) {
                        $this->fail('Theme migration failed.');
                    }
                    if ($provider instanceof ThemeLifecycle) {
                        $previous = data_get($member->settings()->where('key', '_lifecycle.previous_version')->first()?->value, 'raw');
                        if ($previous) {
                            $provider->upgrade($previous);
                        }
                        $provider->activate();
                    }
                }
                foreach (array_diff(array_reverse($oldChain), $chain) as $slug) {
                    $oldProvider = app(ExtensionCodeLoader::class)->provider('theme', $slug);
                    if ($oldProvider instanceof ThemeLifecycle) {
                        $oldProvider->deactivate();
                    }
                }
                DB::transaction(function () use ($theme, $old, $switch, $chain) {
                    $switch();
                    foreach ($chain as $slug) {
                        Theme::where('slug', $slug)->firstOrFail()->settings()->whereIn('key', ['_lifecycle.previous_version', '_lifecycle.previous_directory'])->delete();
                    }
                    if ($old) {
                        zfy_after_commit('zfy_theme_deactivated', $old);
                    }
                    zfy_after_commit('zfy_theme_activated', $theme->fresh());
                });
                app(PluginLifecycleManager::class)->record('theme:'.$theme->slug, 'activated');
            }, function () use ($theme) {
                foreach (app(ThemePackageLoader::class)->chain($theme->slug) as $slug) {
                    $this->restorePreviousFiles(Theme::where('slug', $slug)->firstOrFail());
                }
            }, ['theme' => $theme->slug, 'theme_chain' => app(ThemePackageLoader::class)->chain($theme->slug)]);
        });
    }

    public function restorePreviousFiles(Theme $theme): void
    {
        $theme->refresh();
        $backup = data_get($theme->settings()->where('key', '_lifecycle.previous_directory')->first()?->value, 'raw');
        if (! is_string($backup) || ! is_dir($backup)) {
            return;
        }
        app(ExtensionMaintenance::class)->restorePackageFiles(['type' => 'theme', 'slug' => $theme->slug, 'backup' => $backup]);
        $manifest = app(PackageManifestService::class)->themes()[$theme->slug];
        $theme->update(['version' => $manifest['version'], 'name' => $manifest['name'], 'entry_view' => app(ThemePackageLoader::class)->entry($manifest), 'settings_schema' => $manifest['settings_schema'] ?? [], 'is_active' => false]);
        $theme->settings()->whereIn('key', ['_lifecycle.previous_version', '_lifecycle.previous_directory'])->delete();
        app(ThemeManager::class)->forgetActiveCache();
    }

    public function uninstall(Theme $theme): void
    {
        Cache::store('file')->lock('zfy-theme-lifecycle', 300)->block(5, function () use ($theme) {
            $theme->refresh();
            if ($theme->is_active) {
                $this->fail('请先启用其他主题。');
            }
            foreach (app(PackageManifestService::class)->plugins() as $slug => $manifest) {
                if (isset($manifest['requires']['themes'][$theme->slug]) && Plugin::where('slug', $slug)->where('enabled', true)->exists()) {
                    $this->fail('请先停用依赖此主题的插件：'.$slug);
                }
            }
            foreach (app(PackageManifestService::class)->themes() as $manifest) {
                if (($manifest['parent'] ?? null) === $theme->slug) {
                    $this->fail('请先卸载依赖此主题的子主题。');
                }
            }
            $root = realpath(base_path('themes'));
            $target = realpath(base_path('themes/'.$theme->slug));
            if (! $root || ! $target || dirname($target) !== $root) {
                $this->fail('主题目录不存在或非法。');
            }
            $archive = storage_path('app/private/extensions/uninstalled/theme-'.$theme->slug.'-'.Str::uuid());
            File::ensureDirectoryExists(dirname($archive));
            app(ExtensionMaintenance::class)->run(function () use ($theme, $target, $archive) {
                $provider = app(ExtensionCodeLoader::class)->provider('theme', $theme->slug);
                if ($provider instanceof ThemeLifecycle) {
                    $provider->uninstall(false);
                }
                if (! File::moveDirectory($target, $archive)) {
                    $this->fail('无法归档主题文件。');
                }
                DB::transaction(function () use ($theme, $archive) {
                    $theme->settings()->updateOrCreate(['scope' => 'global', 'key' => '_lifecycle.uninstalled'], ['value' => ['raw' => true]]);
                    $theme->settings()->updateOrCreate(['scope' => 'global', 'key' => '_lifecycle.uninstalled_archive'], ['value' => ['raw' => $archive]]);
                    zfy_after_commit('zfy_theme_uninstalled', $theme);
                });
            }, fn () => $this->restoreUninstalledFiles($theme->slug, $archive), ['theme' => $theme->slug, 'uninstall_archive' => $archive]);
            app(ThemeManager::class)->forgetActiveCache();
        });
    }

    public function purge(Theme $theme): void
    {
        Cache::store('file')->lock('zfy-theme-lifecycle', 300)->block(5, function () use ($theme) {
            $theme->refresh();
            if ($theme->is_active || is_dir(base_path('themes/'.$theme->slug)) || ! $theme->settings()->where('key', '_lifecycle.uninstalled')->exists()) {
                $this->fail('仅可清理已卸载主题的数据。');
            }
            app(ExtensionMaintenance::class)->run(function () use ($theme) {
                $archive = data_get($theme->settings()->where('key', '_lifecycle.uninstalled_archive')->first()?->value, 'raw');
                if ($archive) {
                    $provider = app(ExtensionCodeLoader::class)->provider('theme', $theme->slug, $archive);
                    if ($provider instanceof ThemeLifecycle) {
                        $provider->uninstall(true);
                    }
                }
                DB::transaction(function () use ($theme) {
                    $theme->settings()->delete();
                    $theme->delete();
                    zfy_after_commit('zfy_theme_data_deleted', $theme);
                });
            });
        });
    }

    public function restoreUninstalledFiles(string $slug, string $archive): void
    {
        if (! is_dir($archive)) {
            return;
        }
        $root = realpath($archive);
        if (! $root || dirname($root) !== realpath(storage_path('app/private/extensions/uninstalled')) || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $this->fail('Invalid theme recovery directory.');
        }
        $manifest = json_decode(File::get($root.'/theme.json'), true, 64, JSON_THROW_ON_ERROR);
        if (($manifest['slug'] ?? '') !== $slug) {
            $this->fail('Theme recovery identity mismatch.');
        }
        if (! File::moveDirectory($root, base_path('themes/'.$slug))) {
            $this->fail('Cannot restore theme archive.');
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['theme' => $message]);
    }
}
