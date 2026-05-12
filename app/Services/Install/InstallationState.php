<?php

namespace App\Services\Install;

use Illuminate\Support\Facades\File;

class InstallationState
{
    public function installed(): bool
    {
        return $this->envInstalled() && File::exists($this->lockPath());
    }

    public function envInstalled(): bool
    {
        $value = config('zfy.installed', env('ZFY_INSTALLED', false));

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function markInstalled(array $payload = []): void
    {
        File::ensureDirectoryExists(dirname($this->lockPath()));
        File::put($this->lockPath(), json_encode(array_merge([
            'installed_at' => now()->toIso8601String(),
            'version' => config('zfy.version'),
        ], $payload), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function lockPath(): string
    {
        return storage_path('app/zfy/install.lock');
    }
}
