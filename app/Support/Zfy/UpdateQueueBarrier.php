<?php

namespace App\Support\Zfy;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Support\Facades\File;

class UpdateQueueBarrier
{
    private array $locks = [];

    public function acquire(Job $job): void
    {
        $directory = storage_path('app/private/updates');
        File::ensureDirectoryExists($directory);
        $key = spl_object_id($job);
        if (isset($this->locks[$key])) {
            return;
        }
        $lock = fopen($directory.'/requests.lock', 'c');
        if (! $lock) {
            throw new \RuntimeException('Cannot open job maintenance lock.');
        }
        if (! flock($lock, LOCK_SH | LOCK_NB) || is_file($directory.'/writes-paused')) {
            fclose($lock);
            $job->release(60);
            throw new \RuntimeException('Update maintenance is active; job released for retry.');
        }
        $this->locks[$key] = $lock;
    }

    public function release(Job $job): void
    {
        $key = spl_object_id($job);
        $lock = $this->locks[$key] ?? null;
        if (is_resource($lock)) {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
        unset($this->locks[$key]);
    }
}
