<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\User;
use App\Services\OrderService;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ContentPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_set_metadata_but_cannot_change_commerce_pricing(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('EDITOR');
        $content = Content::create(['title' => 'Metadata', 'slug' => 'metadata-test', 'type' => 'post', 'status' => 'draft', 'pricing' => ['price' => '9.99']]);
        $this->actingAs($user)->putJson('/admin/contents/'.$content->id.'/access', ['seo_title' => 'Editorial title', 'visibility' => 'member'])->assertOk();
        $this->assertSame('Editorial title', $content->fresh()->seo['title']);
        $this->assertSame('9.99', $content->fresh()->pricing['price']);
        $this->putJson('/admin/contents/'.$content->id.'/access', ['price' => '0', 'vip_free' => true])->assertUnprocessable();
    }

    public function test_password_grant_is_bound_to_viewer_and_password_revision(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $content = Content::create(['title' => 'Protected', 'slug' => 'password-test', 'type' => 'post', 'status' => 'published', 'markdown_cache' => 'PASSWORD PRIVATE BODY', 'access_rules' => ['visibility' => 'password', 'password_hash' => Hash::make('test-content-password')]]);
        $user = User::factory()->create();
        $orders = app(OrderService::class);
        $this->assertFalse($orders->userCanAccessContent($content, $user));
        $this->actingAs($user)->get('/content/password-test')->assertOk()->assertDontSee('PASSWORD PRIVATE BODY');
        $this->postJson('/content/password-test/unlock', ['password' => 'wrong'])->assertUnprocessable();
        $this->postJson('/content/password-test/unlock', ['password' => 'test-content-password'])->assertOk();
        $this->assertTrue($orders->userCanAccessContent($content, $user));
        $this->assertFalse($orders->userCanAccessContent($content, User::factory()->create()));
        $this->get('/content/password-test')->assertSee('PASSWORD PRIVATE BODY');
        $this->assertArrayNotHasKey('password_hash', $content->toArray()['access_rules']);
        $content->update(['access_rules' => ['visibility' => 'password', 'password_hash' => Hash::make('changed-password')]]);
        $this->assertFalse($orders->userCanAccessContent($content, $user));
    }
}
