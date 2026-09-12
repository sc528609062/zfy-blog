<?php

namespace Tests\Feature;

use App\Jobs\DeliverSiteNotification;
use App\Models\PrivateMessage;
use App\Models\Setting;
use App\Models\User;
use App\Services\PrivateMessageService;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class PrivateMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_delivery_is_idempotent_and_cannot_be_read_by_other_users(): void
    {
        Queue::fake();
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $data = ['recipient_id' => $recipient->id, 'body' => 'Private message body', 'request_id' => (string) Str::uuid()];
        $this->actingAs($sender)->postJson('/user/messages', $data)->assertOk();
        $this->postJson('/user/messages', $data)->assertOk();
        $this->assertDatabaseCount('private_messages', 1);
        $message = PrivateMessage::first();
        $this->actingAs(User::factory()->create())->getJson('/api/v1/messages')->assertJsonCount(0, 'data.data');
        $this->postJson('/user/messages/'.$message->id.'/read')->assertNotFound();
        $this->actingAs($recipient)->getJson('/api/v1/messages')->assertJsonPath('data.data.0.body', $data['body']);
        $this->postJson('/user/messages/'.$message->id.'/read')->assertOk();
        $this->assertNotNull($message->fresh()->read_at);
    }

    public function test_sensitive_message_requires_review_before_delivery(): void
    {
        Queue::fake();
        $this->seed(CoreInstallSeeder::class);
        Setting::create(['key' => 'discussion.sensitive_words', 'value' => ['raw' => 'restricted-term']]);
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $message = app(PrivateMessageService::class)->send($sender, ['recipient_id' => $recipient->id, 'body' => 'restricted-term message', 'request_id' => (string) Str::uuid()]);
        $this->assertSame('pending', $message->status);
        Queue::assertNotPushed(DeliverSiteNotification::class);
        $this->actingAs($recipient)->getJson('/api/v1/messages')->assertJsonCount(0, 'data.data');
        $admin = User::where('username', 'admin')->first();
        $this->actingAs($admin)->patchJson('/admin/resources/private-messages/'.$message->id, ['status' => 'sent'])->assertOk();
        $this->assertSame('sent', $message->fresh()->status);
        $this->actingAs($recipient)->getJson('/api/v1/messages')->assertJsonCount(1, 'data.data');
    }
}
