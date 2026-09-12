<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtensionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_extension_api_checks_role_token_and_validation(): void
    {
        $this->seed(CoreInstallSeeder::class);
        zfy_register_api('sample-count', ['methods' => ['POST'], 'permission' => 'manage contents', 'ability' => 'profile', 'rules' => ['POST' => ['count' => ['required', 'integer', 'between:1,10']]], 'handler' => fn ($request, $data) => $data]);
        $this->postJson('/api/v1/extensions/sample-count', ['count' => 2])->assertUnauthorized();
        $user = User::factory()->create();
        $user->givePermissionTo('manage contents');
        $readToken = $user->createToken('Read', ['read'])->plainTextToken;
        $token = $user->createToken('Profile', ['profile'])->plainTextToken;
        $this->withToken($readToken)->postJson('/api/v1/extensions/sample-count', ['count' => 2])->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->postJson('/api/v1/extensions/sample-count', ['count' => 20])->assertUnprocessable();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->postJson('/api/v1/extensions/sample-count', ['count' => 2])->assertOk()->assertJsonPath('data.count', 2);
    }

    public function test_public_extension_api_is_read_only(): void
    {
        zfy_register_api('sample-public', ['public' => true, 'handler' => fn () => ['version' => 1]]);
        $this->getJson('/api/v1/extensions/sample-public')->assertOk()->assertJsonPath('data.version', 1);
        $this->postJson('/api/v1/extensions/sample-public')->assertStatus(405);
        $this->expectException(\InvalidArgumentException::class);
        zfy_register_api('sample-unsafe', ['public' => true, 'methods' => ['POST'], 'handler' => fn () => []]);
    }
}
