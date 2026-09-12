<?php

namespace App\Services;

use App\Models\Plugin;
use App\Models\Theme;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ExtensionMaintenance
{
    public function run(callable $operation, ?callable $rollback = null, array $context = []): mixed
    {
        if (DB::transactionLevel() > 0) {
            return DB::transaction($operation);
        }
        $directory = storage_path('app/private/updates');
        File::ensureDirectoryExists($directory);
        $requestLock = request()->attributes->get('zfy.maintenance_lock');
        $lock = is_resource($requestLock) ? $requestLock : fopen($directory.'/requests.lock', 'c');
        $owner = fopen($directory.'/update.lock', 'c');
        if (! $lock || ! $owner) {
            throw new \RuntimeException('Cannot open extension maintenance lock.');
        }
        $pause = $directory.'/writes-paused';
        $acquired = false;
        $operationFailed = false;
        $backup = null;
        $recovery = storage_path('app/private/extensions/recovery.json');
        try {
            if (! flock($owner, LOCK_EX | LOCK_NB) || is_file($pause)) {
                throw ValidationException::withMessages(['plugin' => '已有更新任务正在执行。']);
            }
            File::put($pause, 'extension-'.bin2hex(random_bytes(12)));
            $acquired = true;
            $started = microtime(true);
            while (! flock($lock, LOCK_EX | LOCK_NB)) {
                if (microtime(true) - $started > 10) {
                    throw ValidationException::withMessages(['plugin' => '站点仍有活动请求，请稍后重试。']);
                }
                usleep(100000);
            }
            $backup = app(DatabaseBackup::class)->create();
            File::ensureDirectoryExists(dirname($recovery));
            File::put($recovery, json_encode(['backup' => $backup, 'time' => now()->toIso8601String(), 'context' => $context], JSON_THROW_ON_ERROR));
            try {
                return $operation();
            } catch (\Throwable $exception) {
                $operationFailed = true;
                try {
                    app(DatabaseBackup::class)->restore($backup);
                    if ($rollback) {
                        $rollback();
                    }
                } catch (\Throwable $restoreException) {
                    throw new \RuntimeException('Extension database recovery failed; maintenance remains active.', 0, $restoreException);
                }
                throw $exception;
            }
        } finally {
            if ($acquired && ! $operationFailed) {
                File::delete([$pause, $recovery]);
            }
            if (is_resource($requestLock)) {
                flock($lock, LOCK_SH);
            } else {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
            flock($owner, LOCK_UN);
            fclose($owner);
        }
    }

    public function recover(): void
    {
        $directory = storage_path('app/private/updates');
        $recovery = storage_path('app/private/extensions/recovery.json');
        if (! is_file($recovery)) {
            throw new \RuntimeException('No extension recovery is pending.');
        }
        $owner = fopen($directory.'/update.lock', 'c');
        $requests = fopen($directory.'/requests.lock', 'c');
        if (! $owner || ! $requests) {
            throw new \RuntimeException('Cannot open recovery locks.');
        }
        try {
            if (! flock($owner, LOCK_EX | LOCK_NB) || ! flock($requests, LOCK_EX | LOCK_NB)) {
                throw new \RuntimeException('Maintenance is still running.');
            }
            $state = json_decode(File::get($recovery), true, 64, JSON_THROW_ON_ERROR);
            File::put($directory.'/writes-paused', 'extension-recovery');
            app(DatabaseBackup::class)->restore($state['backup']);
            if ($files = $state['context']['package_files'] ?? null) {
                $this->restorePackageFiles($files);
            }
            if ($slug = $state['context']['plugin'] ?? null) {
                if ($archive = $state['context']['uninstall_archive'] ?? null) {
                    app(PluginLifecycleManager::class)->restoreUninstalledFiles($slug, $archive);
                }
                if ($plugin = Plugin::where('slug', $slug)->first()) {
                    app(PluginLifecycleManager::class)->restorePreviousFiles($plugin);
                    $plugin->update(['enabled' => false]);
                }
            }
            if ($slug = $state['context']['theme'] ?? null) {
                if ($archive = $state['context']['uninstall_archive'] ?? null) {
                    app(ThemeLifecycleManager::class)->restoreUninstalledFiles($slug, $archive);
                }
                foreach ($state['context']['theme_chain'] ?? [$slug] as $member) {
                    if ($theme = Theme::where('slug', $member)->first()) {
                        app(ThemeLifecycleManager::class)->restorePreviousFiles($theme);
                    }
                }
            }
            if ($id = $state['context']['update_id'] ?? null) {
                if (! Str::isUuid($id)) {
                    throw new \RuntimeException('Invalid update recovery identifier.');
                }
                $path = storage_path('app/private/extensions/updates/'.$id.'/state.json');
                $update = json_decode(File::get($path), true, 64, JSON_THROW_ON_ERROR);
                File::replace($path, json_encode([...$update, 'status' => 'rolled-back', 'updated_at' => now()->toIso8601String()], JSON_THROW_ON_ERROR));
            }
            File::delete([$recovery, $directory.'/writes-paused']);
        } finally {
            flock($requests, LOCK_UN);
            fclose($requests);
            flock($owner, LOCK_UN);
            fclose($owner);
        }
    }

    public function restorePackageFiles(array $state): void
    {
        $type = $state['type'] ?? '';
        $slug = $state['slug'] ?? '';
        $backup = realpath($state['backup'] ?? '');
        if (! in_array($type, ['plugin', 'theme'], true) || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) || ! $backup || dirname($backup) !== realpath(storage_path('app/private/extensions/versions'))) {
            throw new \RuntimeException('Invalid package recovery state.');
        }
        $manifest = json_decode(File::get($backup.'/'.$type.'.json'), true, 64, JSON_THROW_ON_ERROR);
        if (($manifest['slug'] ?? '') !== $slug) {
            throw new \RuntimeException('Package recovery identity mismatch.');
        }
        $target = base_path($type.'s/'.$slug);
        $stage = storage_path('app/private/extensions/versions/restore-'.bin2hex(random_bytes(12)));
        if (! File::copyDirectory($backup, $stage)) {
            throw new \RuntimeException('Cannot stage package recovery.');
        }
        if (is_dir($target) && ! File::moveDirectory($target, storage_path('app/private/extensions/versions/failed-'.bin2hex(random_bytes(12))))) {
            throw new \RuntimeException('Cannot archive failed package.');
        }
        if (! File::moveDirectory($stage, $target)) {
            throw new \RuntimeException('Cannot restore package.');
        }
        app(PackageIntegrity::class)->record($type, $slug);
        app(ThemeManager::class)->forgetActiveCache();
    }
}
