<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 全局站点设置（settings 表）。
 *
 * 用法：
 *   app(SettingsService::class)->get('site.name', 'zfy-blog');
 *   app(SettingsService::class)->set('site.name', 'zfy-blog');
 *
 * 支持的 type：string, int, float, bool, json, text。
 */
class SettingsService
{
    protected const CACHE_KEY = 'zfy.settings.all';

    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, function () {
            if (! Schema::hasTable('settings')) {
                return [];
            }

            return DB::table('settings')->get()->mapWithKeys(fn ($row) => [
                $row->key => $this->cast($row->value, $row->type),
            ])->all();
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function set(string $key, mixed $value, string $type = 'string'): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            [
                'value' => $this->serialize($value, $type),
                'type'  => $type,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $this->flush();
    }

    public function setMany(array $kv, string $type = 'string'): void
    {
        foreach ($kv as $k => $v) {
            $this->set($k, $v, $type);
        }
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected function cast(mixed $value, ?string $type): mixed
    {
        return match ($type) {
            'int'   => (int) $value,
            'float' => (float) $value,
            'bool'  => filter_var($value, FILTER_VALIDATE_BOOL),
            'json'  => json_decode((string) $value, true),
            default => (string) $value,
        };
    }

    protected function serialize(mixed $value, string $type): string
    {
        return match ($type) {
            'json'  => json_encode($value, JSON_UNESCAPED_UNICODE),
            'bool'  => $value ? '1' : '0',
            default => (string) $value,
        };
    }
}
