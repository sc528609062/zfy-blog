<?php

namespace Tests\Unit;

use App\Support\Zfy\UpdateQueueBarrier;
use App\Support\Zfy\UpdateTaskBarrier;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class UpdateBarrierTest extends TestCase
{
    public function test_paused_maintenance_blocks_commands_and_nested_jobs_hold_the_lock(): void
    {
        $original = storage_path();
        $storage = $original.'/framework/testing/barrier-'.bin2hex(random_bytes(8));
        app()->useStoragePath($storage);
        File::ensureDirectoryExists($storage.'/app/private/updates');
        try {
            File::put($storage.'/app/private/updates/writes-paused', 'test');
            $this->assertSame(0, app(UpdateTaskBarrier::class)->run(fn () => throw new \RuntimeException('Must not run')));
            File::delete($storage.'/app/private/updates/writes-paused');
            $this->assertSame(42, app(UpdateTaskBarrier::class)->run(fn () => 42));
            $barrier = new UpdateQueueBarrier;
            $parent = $this->createMock(Job::class);
            $child = $this->createMock(Job::class);
            $barrier->acquire($parent);
            $barrier->acquire($child);
            $barrier->release($child);
            $exclusive = fopen($storage.'/app/private/updates/requests.lock', 'c');
            $this->assertFalse(flock($exclusive, LOCK_EX | LOCK_NB));
            $barrier->release($parent);
            $this->assertTrue(flock($exclusive, LOCK_EX | LOCK_NB));
            flock($exclusive, LOCK_UN);
            fclose($exclusive);
        } finally {
            app()->useStoragePath($original);
            File::deleteDirectory($storage);
        }
    }
}
