<?php

namespace App\Support\Zfy;

use Illuminate\Support\Facades\File;

class UpdateTaskBarrier
{
    public function run(callable $callback): mixed
    {
        $directory = storage_path('app/private/updates');
        File::ensureDirectoryExists($directory);
        $lock = fopen($directory.'/requests.lock', 'c');
        if (! $lock) {
            throw new \RuntimeException('Cannot open maintenance lock.');
        }
        try {
            if (! flock($lock, LOCK_SH | LOCK_NB) || is_file($directory.'/writes-paused')) {
                return 0;
            }

            return $callback();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
