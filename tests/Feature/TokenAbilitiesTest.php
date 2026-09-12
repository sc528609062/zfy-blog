<?php

namespace Tests\Feature;

use App\Models\PointsStoreItem;
use App\Models\User;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenAbilitiesTest extends TestCase
{
    public function test_profile_and_order_abilities_control_new_business_endpoints(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $user = User::factory()->create(['password' => 'test-password-123']);
        $token = $user->createToken('profile', ['profile'], now()->addHour())->plainTextToken;
        $this->withToken($token)->putJson('/api/v1/me', ['name' => 'Updated', 'email' => $user->email])->assertOk()->assertJsonPath('code', 0);
        $item = PointsStoreItem::create(['title' => 'Token reward', 'slug' => 'token-reward', 'points_price' => 1, 'stock' => 1]);
        $this->withToken($token)->postJson('/api/v1/points-store/'.$item->id.'/exchange', [])->assertForbidden();
        $this->withToken($token)->postJson('/api/v1/requests', ['type' => 'author', 'body' => 'Test author application'])->assertOk()->assertJsonPath('code', 0);
        $this->assertDatabaseHas('user_requests', ['user_id' => $user->id, 'type' => 'author', 'status' => 'pending']);
    }

    use RefreshDatabase;

    public function test_banned_user_with_existing_token_can_only_submit_an_appeal(): void
    {
        $user = User::factory()->create(['is_banned' => true]);
        $token = $user->createToken('profile', ['profile'])->plainTextToken;
        $this->withToken($token)->postJson('/api/v1/requests', ['type' => 'appeal', 'body' => 'Please review this account restriction.'])->assertOk();
        $this->withToken($token)->postJson('/api/v1/requests', ['type' => 'author', 'body' => 'Please grant author access.'])->assertForbidden();
        $this->withToken($token)->putJson('/api/v1/me', ['name' => 'Changed', 'email' => $user->email])->assertForbidden();
    }

    public function test_read_token_cannot_write_and_expired_tokens_are_rejected(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $user = User::factory()->create();
        $token = $user->createToken('reader', ['read'], now()->addHour())->plainTextToken;
        $this->withToken($token)->getJson('/api/v1/me')->assertOk();
        $this->withToken($token)->postJson('/api/v1/checkin')->assertForbidden();
        $expired = $user->createToken('expired', ['read'], now()->subSecond())->plainTextToken;
        auth()->forgetGuards();
        $this->withToken($expired)->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_banned_account_cannot_issue_a_token(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $user = User::factory()->create(['is_banned' => true, 'password' => 'test-password']);
        $this->postJson('/api/v1/auth/token', ['email' => $user->email, 'password' => 'test-password'])->assertUnprocessable();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
