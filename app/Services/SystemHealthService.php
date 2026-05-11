<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;
use Throwable;

class SystemHealthService
{
    public function report(): array
    {
        return [
            'app' => [
                'name' => config('app.name'),
                'version' => config('zfy.version'),
                'environment' => app()->environment(),
                'debug' => config('app.debug'),
            ],
            'database' => $this->databaseStatus(),
            'redis' => $this->redisStatus(),
            'storage' => [
                'storage_writable' => File::isWritable(storage_path()),
                'cache_writable' => File::isWritable(base_path('bootstrap/cache')),
            ],
            'features' => [
                'themes' => true,
                'plugins' => true,
                'page_builder' => true,
                'payments' => true,
                'sanctum_api' => true,
            ],
        ];
    }

    private function databaseStatus(): array
    {
        try {
            DB::select('select 1');

            return ['ok' => true, 'connection' => config('database.default')];
        } catch (Throwable $exception) {
            return ['ok' => false, 'connection' => config('database.default'), 'error' => $exception->getMessage()];
        }
    }

    private function redisStatus(): array
    {
        try {
            if (config('database.redis.client') === 'predis' || extension_loaded('redis')) {
                Redis::connection()->ping();

                return ['ok' => true, 'client' => config('database.redis.client')];
            }
        } catch (Throwable $exception) {
            return ['ok' => false, 'client' => config('database.redis.client'), 'error' => $exception->getMessage()];
        }

        return ['ok' => null, 'client' => config('database.redis.client'), 'message' => '未配置 Redis 或当前环境跳过检测'];
    }
}
