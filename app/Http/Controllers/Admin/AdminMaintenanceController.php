<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RebuildContentIndex;
use App\Models\Plugin;
use App\Models\Setting;
use App\Models\Theme;
use App\Services\DatabaseBackup;
use App\Services\SiteSettings;
use App\Services\Updates\ExtensionUpdater;
use App\Services\Updates\ReleaseClient;
use App\Services\Updates\UpdateManager;
use App\Support\Zfy\FilterBus;
use App\Support\Zfy\HookBus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

class AdminMaintenanceController extends Controller
{
    public function rebuild(Request $request)
    {
        abort_unless($request->user()?->can('manage system'), 403);
        RebuildContentIndex::dispatch()->afterCommit();

        return response()->json(['message' => '搜索索引与缓存重建已加入队列']);
    }

    private function source(): array
    {
        $saved = app(SiteSettings::class)->get('updates.source', []);

        return array_replace(config('updates'), is_array($saved) ? array_intersect_key($saved, array_flip(['provider', 'repository'])) : []);
    }

    public function saveSource(Request $request)
    {
        abort_unless($request->user()?->can('manage system'), 403);
        $data = $request->validate(['provider' => ['required', 'in:github,gitee'], 'repository' => ['required', 'string', 'max:160', 'regex:~^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$~']]);
        Setting::updateOrCreate(['key' => 'updates.source'], ['value' => ['raw' => $data], 'autoload' => false]);
        Cache::store('file')->forget('zfy.available-release');

        return response()->json(['message' => '更新源已保存']);
    }

    public function offline(Request $request, UpdateManager $updates)
    {
        abort_unless($request->user()?->can('manage system'), 403);
        $request->validate(['package' => ['required', 'file', 'max:307200'], 'manifest' => ['required', 'file', 'max:8192'], 'signature' => ['required', 'file', 'max:8']]);
        $id = Cache::store('file')->lock('zfy-update-prepare', 600)->block(1, fn () => $updates->prepareOffline($request->file('package')->getRealPath(), File::get($request->file('manifest')->getRealPath()), File::get($request->file('signature')->getRealPath())));

        return response()->json(['message' => '离线包已校验，计划任务将执行更新。', 'id' => $id], 202);
    }

    public function checkUpdate(Request $request, ReleaseClient $client)
    {
        abort_unless($request->user()?->can('manage system'), 403);
        $release = $client->latest($this->source());
        Cache::store('file')->put('zfy.available-release', $release, 1800);

        return response()->json(['data' => $release]);
    }

    public function prepareUpdate(Request $request, UpdateManager $updates)
    {
        abort_unless($request->user()?->can('manage system'), 403);
        $release = Cache::store('file')->get('zfy.available-release');
        if (! $release) {
            throw ValidationException::withMessages(['update' => '请先检查新版本。']);
        }
        $id = Cache::store('file')->lock('zfy-update-prepare', 600)->block(1, fn () => $updates->prepare($release));

        return response()->json(['message' => '更新包已校验，计划任务将自动执行更新。', 'id' => $id], 202);
    }

    public function index(Request $request)
    {
        abort_unless($request->user()?->can('manage system'), 403);
        $migrator = app('migrator');
        $pending = array_values(array_diff(array_keys($migrator->getMigrationFiles(database_path('migrations'))), $migrator->getRepository()->getRan()));

        return response()->json(['data' => [
            'version' => config('zfy.version'), 'pending' => $pending,
            'update_checks' => Cache::store('file')->get('zfy.update-checks'),
            'hooks' => app(HookBus::class)->diagnostics(),
            'filters' => app(FilterBus::class)->diagnostics(),
            'extension_updates' => app(ExtensionUpdater::class)->statuses(),
            'updates' => app(UpdateManager::class)->status(),
            'update_source' => ['provider' => $this->source()['provider'], 'repository' => $this->source()['repository'], 'configured' => filled(config('updates.public_key'))],
            'themes' => Theme::get(['name', 'version', 'is_active']),
            'plugins' => Plugin::get(['name', 'version', 'enabled']),
            'logs' => DB::table('upgrade_logs')->latest()->limit(20)->get(),
            'backups' => collect(File::glob(storage_path('app/private/backups/database-*')))->map(fn ($path) => ['name' => basename($path), 'size' => filesize($path)])->values(),
        ]]);
    }

    public function backup(Request $request, DatabaseBackup $backup)
    {
        abort_unless($request->user()?->can('manage system'), 403);
        try {
            $path = Cache::store('file')->lock('zfy-maintenance', 180)->get(fn () => $backup->create());
            if (! $path) {
                throw new \RuntimeException('已有维护任务正在执行。');
            }
        } catch (\Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages(['backup' => '备份失败，请检查数据库工具和目录权限。']);
        }

        return response()->json(['message' => '数据库已备份', 'file' => basename($path)]);
    }

    public function download(Request $request, string $file)
    {
        abort_unless($request->user()?->can('manage system'), 403);
        abort_unless(preg_match('/^database-[a-zA-Z0-9-]+\.(sql|sqlite)$/', $file), 404);
        $path = storage_path('app/private/backups/'.$file);
        abort_unless(is_file($path), 404);

        return response()->download($path)->header('Cache-Control', 'private, no-store');
    }

    public function migrate(Request $request, DatabaseBackup $backup)
    {
        abort_unless($request->user()?->can('manage system'), 403);
        $result = Cache::store('file')->lock('zfy-maintenance', 180)->get(function () use ($backup) {
            $backup->create();
            $id = DB::table('upgrade_logs')->insertGetId(['from_version' => config('zfy.version'), 'to_version' => config('zfy.version'), 'status' => 'running', 'created_at' => now(), 'updated_at' => now()]);
            try {
                if (Artisan::call('migrate', ['--force' => true]) !== 0) {
                    throw new \RuntimeException('数据库更新未完成。');
                }
                DB::table('upgrade_logs')->where('id', $id)->update(['status' => 'completed', 'log' => Artisan::output(), 'updated_at' => now()]);
            } catch (\Throwable $exception) {
                DB::table('upgrade_logs')->where('id', $id)->update(['status' => 'failed', 'log' => '更新失败，请检查服务器日志。', 'updated_at' => now()]);
                report($exception);
                throw ValidationException::withMessages(['migration' => '更新失败，更新前备份已保留。']);
            }

            return true;
        });
        if (! $result) {
            throw ValidationException::withMessages(['migration' => '已有维护任务正在执行。']);
        }

        return response()->json(['message' => '数据库更新完成']);
    }
}
