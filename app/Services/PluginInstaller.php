<?php

namespace App\Services;

use App\Models\Plugin;
use App\Services\Updates\ReleaseVerifier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PharData;
use RecursiveIteratorIterator;

class PluginInstaller
{
    public function install(UploadedFile $upload): Plugin
    {
        return Cache::store('file')->lock('zfy-plugin-lifecycle', 300)->block(5, fn () => $this->installLocked($upload));
    }

    private function installLocked(UploadedFile $upload): Plugin
    {
        $work = storage_path('app/zfy/packages/'.Str::uuid());
        File::ensureDirectoryExists($work);
        $archivePath = $work.'/package.zip';
        File::copy($upload->getRealPath(), $archivePath);
        $target = null;
        $moved = false;
        $previousDirectory = null;
        try {
            $archive = new PharData($archivePath);
            $entries = [];
            $seen = [];
            $total = 0;
            foreach (new RecursiveIteratorIterator($archive) as $file) {
                $path = str_replace('\\', '/', $file->getPathname());
                $prefix = 'phar://'.str_replace('\\', '/', $archivePath).'/';
                $relative = str_starts_with($path, $prefix) ? substr($path, strlen($prefix)) : '';
                if ($relative === '' || preg_match('~(^/|(^|/)\.\.(/|$)|:|[\x00-\x1f])~', $relative) || $file->isLink()) {
                    throw ValidationException::withMessages(['file' => '插件包包含非法路径。']);
                }
                app(ReleaseVerifier::class)->path($relative);
                if (isset($seen[strtolower($relative)])) {
                    throw ValidationException::withMessages(['file' => '插件包有重复路径。']);
                }
                $seen[strtolower($relative)] = true;
                $total += $file->getSize();
                $entries[$relative] = $file->getPathname();
                if (count($entries) > 2000 || $total > 100 * 1024 * 1024) {
                    throw ValidationException::withMessages(['file' => '插件包解压后过大。']);
                }
            }
            $manifests = array_values(array_filter(array_keys($entries), fn ($path) => preg_match('~^(?:[a-z0-9-]+/)?plugin\.json$~', $path)));
            if (count($manifests) !== 1) {
                throw ValidationException::withMessages(['file' => '插件包必须包含唯一的 plugin.json。']);
            }
            $manifestPath = $manifests[0];
            $prefix = dirname($manifestPath) === '.' ? '' : dirname($manifestPath).'/';
            $manifest = json_decode(file_get_contents($entries[$manifestPath]), true, 32, JSON_THROW_ON_ERROR);
            $errors = app(PackageManifestService::class)->validatePluginPayload($manifest);
            if ($errors !== []) {
                throw ValidationException::withMessages(['file' => implode('；', $errors)]);
            }
            $slug = $manifest['slug'];
            if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) || strlen($slug) > 80 || ($prefix !== '' && $prefix !== $slug.'/')) {
                throw ValidationException::withMessages(['file' => '插件目录必须与合法的 slug 一致。']);
            }
            $target = base_path('plugins/'.$slug);
            $existing = Plugin::where('slug', $slug)->first();
            $upgrading = $existing && File::exists($target);
            if ($existing?->enabled) {
                throw ValidationException::withMessages(['file' => '请先停用插件再升级。']);
            }
            if ($upgrading) {
                if (version_compare($manifest['version'], $existing->version, '<=')) {
                    throw ValidationException::withMessages(['file' => '升级包版本必须高于已安装版本。']);
                }
                app(PackageIntegrity::class)->assertUnmodified('plugin', $slug);
            } elseif (File::exists($target) || ($existing && ! $existing->settings()->where('key', '_lifecycle.uninstalled')->exists())) {
                throw ValidationException::withMessages(['file' => '插件目录或安装状态冲突。']);
            }
            File::ensureDirectoryExists($work.'/files');
            foreach ($entries as $path => $source) {
                if (! str_starts_with($path, $prefix)) {
                    throw ValidationException::withMessages(['file' => '插件包包含目录外文件。']);
                }
                $destination = $work.'/files/'.substr($path, strlen($prefix));
                File::ensureDirectoryExists(dirname($destination));
                File::put($destination, file_get_contents($source));
            }
            $plugin = DB::transaction(function () use ($manifest, $work, $target, &$moved, &$previousDirectory, $upgrading, $existing) {
                if ($upgrading) {
                    $previousDirectory = storage_path('app/private/extensions/versions/'.$manifest['slug'].'-'.$existing->version.'-'.Str::uuid());
                    File::ensureDirectoryExists(dirname($previousDirectory));
                    if (! File::moveDirectory($target, $previousDirectory)) {
                        throw ValidationException::withMessages(['file' => '无法备份旧插件。']);
                    }
                }
                if (! File::moveDirectory($work.'/files', $target)) {
                    throw ValidationException::withMessages(['file' => '无法写入插件目录。']);
                }
                $moved = true;

                $plugin = Plugin::updateOrCreate(['slug' => $manifest['slug']], [
                    'name' => $manifest['name'], 'slug' => $manifest['slug'], 'version' => $manifest['version'],
                    'provider' => $manifest['provider'] ?? null, 'permissions' => $manifest['permissions'],
                    'events' => $manifest['events'], 'enabled' => false,
                ]);
                $plugin->settings()->where('key', '_lifecycle.uninstalled')->delete();
                if ($upgrading) {
                    $plugin->settings()->updateOrCreate(['key' => '_lifecycle.previous_version'], ['value' => ['raw' => $existing->version]]);
                }
                if ($upgrading) {
                    $plugin->settings()->updateOrCreate(['key' => '_lifecycle.previous_directory'], ['value' => ['raw' => $previousDirectory]]);
                }
                app(PackageIntegrity::class)->record('plugin', $plugin->slug);

                return $plugin;
            });

            return $plugin;
        } catch (\Throwable $exception) {
            if ($moved && $target) {
                File::deleteDirectory($target);
            }
            if ($previousDirectory && is_dir($previousDirectory)) {
                File::moveDirectory($previousDirectory, $target);
            }
            if ($target && is_dir($target)) {
                app(PackageIntegrity::class)->record('plugin', basename($target));
            }
            if ($exception instanceof ValidationException) {
                throw $exception;
            }
            report($exception);
            throw ValidationException::withMessages(['file' => '无法读取插件 ZIP 包，请检查包格式。']);
        } finally {
            unset($archive);
            File::deleteDirectory($work);
        }
    }
}
