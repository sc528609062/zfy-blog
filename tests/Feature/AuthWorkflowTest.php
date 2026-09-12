<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetAccountPassword as ResetPassword;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_token_is_single_use(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email])->assertRedirect();
        $token = '';
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });
        $payload = ['email' => $user->email, 'token' => $token, 'password' => 'new-password-1234', 'password_confirmation' => 'new-password-1234'];
        $this->post('/reset-password', $payload)->assertRedirect('/login');
        $this->assertTrue(Hash::check('new-password-1234', $user->fresh()->password));
        $this->post('/reset-password', $payload)->assertSessionHasErrors('email');
    }

    public function test_registration_creates_accounts_and_handles_same_email_prefix(): void
    {
        $this->seed(CoreInstallSeeder::class);
        User::factory()->create(['username' => 'reader', 'email' => 'reader@one.test']);
        $this->post('/register', ['name' => 'Reader', 'email' => 'reader@two.test', 'password' => 'password-123456', 'password_confirmation' => 'password-123456'])->assertRedirect('/user');
        $user = User::where('email', 'reader@two.test')->firstOrFail();
        $this->assertNotSame('reader', $user->username);
        $this->assertNotNull($user->wallet);
        $this->assertNotNull($user->pointsAccount);
    }
}
