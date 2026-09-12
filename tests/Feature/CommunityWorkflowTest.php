<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\InviteCode;
use App\Models\User;
use App\Models\UserRequest;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CommunityWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreInstallSeeder::class);
    }

    public function test_approved_author_can_submit_own_content_for_review_without_backend_access(): void
    {
        $user = User::factory()->create(['is_author' => true, 'author_status' => 'approved']);
        $user->assignRole('USER');
        $this->actingAs($user)->get('/user/editor')->assertOk();
        $payload = ['title' => 'Author submission', 'type' => 'post', 'status' => 'pending', 'markdown_cache' => '# Submitted article'];
        $this->post('/user/contents', $payload)->assertRedirect();
        $content = Content::where('author_id', $user->id)->firstOrFail();
        $this->assertSame('pending', $content->status);
        $this->get('/content/'.$content->slug)->assertNotFound();
        $this->get('/admin')->assertForbidden();
        $this->postJson('/user/contents', [...$payload, 'status' => 'published'])->assertUnprocessable();
        $this->actingAs(User::factory()->create(['is_author' => true, 'author_status' => 'approved']))
            ->patch('/user/contents/'.$content->id, $payload)->assertForbidden();
        $this->actingAs(User::where('username', 'admin')->first())->patchJson('/admin/contents/'.$content->id.'/status', ['status' => 'published'])->assertOk();
        $this->get('/content/'.$content->slug)->assertOk()->assertSee('Submitted article');
    }

    public function test_checkin_and_reaction_are_idempotent_and_private_notices_stay_private(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/user/checkin')->assertOk()->assertJsonPath('data.already_checked_in', false);
        $this->postJson('/user/checkin')->assertOk()->assertJsonPath('data.already_checked_in', true);
        $this->assertSame(5, $user->pointsAccount->points);
        $this->assertDatabaseCount('daily_checkins', 1);
        $content = Content::create(['title' => 'Public', 'slug' => 'public', 'type' => 'post', 'status' => 'published']);
        foreach ([1, 2] as $ignored) {
            $this->postJson('/content/public/reaction', ['type' => 'like', 'active' => true])->assertOk();
        }
        $this->assertSame(1, $content->fresh()->like_count);
        $notice = DB::table('notifications')->insertGetId(['user_id' => User::factory()->create()->id, 'type' => 'private', 'title' => 'Private', 'body' => 'Private message', 'created_at' => now(), 'updated_at' => now()]);
        $this->postJson('/api/v1/notifications/'.$notice.'/read')->assertNotFound();
        $notice = DB::table('notifications')->insertGetId(['type' => 'public', 'title' => 'Public', 'body' => 'Public message', 'created_at' => now(), 'updated_at' => now()]);
        $this->postJson('/api/v1/notifications/'.$notice.'/read')->assertOk();
        $this->postJson('/api/v1/notifications/'.$notice.'/read')->assertOk();
        $this->assertDatabaseCount('notification_reads', 1);
    }

    public function test_requests_deduplicate_and_banned_users_can_only_appeal(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/user/requests', ['type' => 'author', 'body' => 'My author application'])->assertRedirect();
        $this->postJson('/user/requests', ['type' => 'author', 'body' => 'Duplicate'])->assertUnprocessable();
        $application = UserRequest::where('user_id', $user->id)->firstOrFail();
        $admin = User::where('username', 'admin')->first();
        $this->actingAs($admin)->patchJson('/admin/resources/verification/'.$application->id, ['status' => 'approved', 'reply' => 'Approved'])->assertOk();
        $this->assertTrue($user->fresh()->canSubmitContent());
        $user->update(['is_banned' => true, 'ban_reason' => 'Moderation']);
        $this->actingAs($user)->postJson('/user/checkin')->assertForbidden();
        $this->postJson('/api/v1/checkin')->assertForbidden();
        $this->post('/user/requests', ['type' => 'appeal', 'body' => 'Please review'])->assertRedirect();
        $appeal = UserRequest::where('type', 'appeal')->firstOrFail();
        $this->actingAs($admin)->patchJson('/admin/resources/ban-appeals/'.$appeal->id, ['status' => 'approved', 'reply' => 'Restored'])->assertOk();
        $this->assertFalse($user->fresh()->is_banned);
    }

    public function test_registration_validates_confirmation_and_consumes_invitation_once(): void
    {
        $invite = InviteCode::create(['code' => 'one-use', 'usage_limit' => 1, 'status' => 'active']);
        $payload = ['name' => 'Invited', 'email' => 'invited@example.com', 'password' => 'password-123456', 'password_confirmation' => 'password-123456', 'invite_code' => $invite->code];
        $this->postJson('/register', [...$payload, 'password_confirmation' => 'mismatch'])->assertUnprocessable();
        $this->assertSame(0, $invite->fresh()->used_count);
        $this->post('/register', $payload)->assertRedirect('/user');
        $this->post('/logout');
        $this->postJson('/register', [...$payload, 'email' => 'another@example.com'])->assertUnprocessable();
        $this->assertSame(1, $invite->fresh()->used_count);
    }
}
