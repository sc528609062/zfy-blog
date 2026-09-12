<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class DeliverSiteNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public string $eventKey, public int $userId, public string $type, public string $title, public string $body)
    {
        $this->afterCommit();
    }

    public function handle(): void
    {
        zfy_consume_event('core.notifications', $this->eventKey, function () {
            if (! DB::table('users')->where('id', $this->userId)->exists()) {
                return;
            }
            DB::table('notifications')->insert(['user_id' => $this->userId, 'type' => $this->type, 'title' => $this->title, 'body' => $this->body, 'created_at' => now(), 'updated_at' => now()]);
        });
    }
}
