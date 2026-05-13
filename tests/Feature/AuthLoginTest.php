<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_username(): void
    {
        $user = User::factory()->create([
            'username' => 'zfy_admin',
            'email' => 'zfy-admin@example.test',
            'password' => Hash::make('password-123456'),
        ]);

        $this->post('/login', [
            'login' => 'zfy_admin',
            'password' => 'password-123456',
        ])->assertRedirect('/user');

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_still_login_with_email(): void
    {
        $user = User::factory()->create([
            'username' => 'email_user',
            'email' => 'email-user@example.test',
            'password' => Hash::make('password-123456'),
        ]);

        $this->post('/login', [
            'login' => 'email-user@example.test',
            'password' => 'password-123456',
        ])->assertRedirect('/user');

        $this->assertAuthenticatedAs($user);
    }

    public function test_api_token_login_accepts_username(): void
    {
        User::factory()->create([
            'username' => 'api_user',
            'email' => 'api-user@example.test',
            'password' => Hash::make('password-123456'),
        ]);

        $this->postJson('/api/v1/auth/token', [
            'login' => 'api_user',
            'password' => 'password-123456',
            'device_name' => 'phpunit',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.username', 'api_user')
            ->assertJsonStructure(['data' => ['access_token']]);
    }

    public function test_api_token_login_still_accepts_email_key(): void
    {
        User::factory()->create([
            'username' => 'api_email_user',
            'email' => 'api-email-user@example.test',
            'password' => Hash::make('password-123456'),
        ]);

        $this->postJson('/api/v1/auth/token', [
            'email' => 'api-email-user@example.test',
            'password' => 'password-123456',
            'device_name' => 'phpunit',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.username', 'api_email_user')
            ->assertJsonStructure(['data' => ['access_token']]);
    }
}
