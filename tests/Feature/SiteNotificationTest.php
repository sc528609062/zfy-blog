<?php

namespace Tests\Feature;

use App\Jobs\DeliverSiteNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_redelivery_does_not_duplicate_private_notification(): void
    {
        $user = User::factory()->create();
        $job = new DeliverSiteNotification('review:100', $user->id, 'review', 'Review result', 'Accepted');
        $job->handle();
        $job->handle();
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'body' => 'Accepted']);
    }
}
