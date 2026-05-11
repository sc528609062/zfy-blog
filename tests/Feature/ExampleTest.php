<?php

namespace Tests\Feature;

use App\Models\Theme;
use App\Models\PageLayout;
use App\Models\Plugin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_returns_a_successful_response(): void
    {
        $this->seed();

        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_home_api_returns_versioned_payload(): void
    {
        $this->seed();

        $response = $this->getJson('/api/v1/home');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.theme.slug', 'style-a-blue-gaming');
    }

    public function test_admin_requires_backend_role(): void
    {
        $this->seed();

        $this->get('/admin')->assertRedirect('/login');

        $user = User::where('email', 'star@zfy-blog.test')->firstOrFail();

        $this->actingAs($user)->get('/admin')->assertForbidden();

        $admin = User::where('email', 'admin@zfy-blog.test')->firstOrFail();

        $this->actingAs($admin)->get('/admin/themes')->assertOk();
    }

    public function test_admin_can_activate_builtin_theme(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@zfy-blog.test')->firstOrFail();

        $this->actingAs($admin)
            ->post('/admin/themes/activate', ['slug' => 'style-c-creative'])
            ->assertRedirect();

        $this->assertTrue(Theme::where('slug', 'style-c-creative')->firstOrFail()->is_active);
        $this->getJson('/api/v1/home')
            ->assertOk()
            ->assertJsonPath('data.theme.slug', 'style-c-creative');
    }

    public function test_sanctum_token_can_read_private_user_payload(): void
    {
        $this->seed();

        $token = $this->postJson('/api/v1/auth/token', [
            'email' => 'admin@zfy-blog.test',
            'password' => 'zfy-blog-123456',
            'device_name' => 'phpunit',
        ])->assertOk()->json('data.access_token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'admin@zfy-blog.test');
    }

    public function test_package_manifests_are_available_in_admin(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@zfy-blog.test')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/themes')
            ->assertOk()
            ->assertSee('style-a-blue-gaming')
            ->assertSee('配置：global / home / channel / detail');

        $this->actingAs($admin)
            ->get('/admin/plugins')
            ->assertOk()
            ->assertSee('payment.notify.received');
    }

    public function test_admin_can_save_page_layout_json_and_toggle_plugin(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@zfy-blog.test')->firstOrFail();
        $layout = PageLayout::where('scope', 'home')->firstOrFail();
        $plugin = Plugin::where('slug', 'payment-enhancer')->firstOrFail();

        $this->actingAs($admin)
            ->post("/admin/page-builder/{$layout->id}", [
                'title' => '首页布局测试',
                'status' => 'published',
                'schema' => json_encode(['blocks' => [['type' => 'hero', 'title' => '测试 Hero']]], JSON_UNESCAPED_UNICODE),
            ])
            ->assertRedirect();

        $this->assertSame('首页布局测试', $layout->fresh()->title);
        $this->assertSame('测试 Hero', $layout->fresh()->schema['blocks'][0]['title']);

        $this->actingAs($admin)
            ->post("/admin/plugins/{$plugin->id}/toggle")
            ->assertRedirect();

        $this->assertTrue($plugin->fresh()->enabled);
    }

    public function test_payment_query_and_order_status_are_available(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@zfy-blog.test')->firstOrFail();
        $content = \App\Models\Content::where('type', 'files')->firstOrFail();

        $response = $this->actingAs($admin)
            ->post("/buy/{$content->slug}", ['gateway' => 'alipay_official'])
            ->assertOk();

        $orderNo = \App\Models\Order::latest()->firstOrFail()->order_no;
        $payment = \App\Models\Payment::latest()->firstOrFail();

        $this->postJson("/payments/{$payment->id}/query")
            ->assertOk()
            ->assertJsonPath('data.gateway', 'alipay_official');

        $this->getJson("/orders/{$orderNo}/status")
            ->assertOk()
            ->assertJsonPath('data.order_no', $orderNo);
    }

    public function test_system_health_and_upgrade_endpoints(): void
    {
        $this->seed();

        $this->getJson('/api/v1/system/health')
            ->assertOk()
            ->assertJsonPath('data.app.name', 'zfy-blog')
            ->assertJsonPath('data.database.ok', true);

        $this->getJson('/api/v1/system/upgrade')
            ->assertOk()
            ->assertJsonPath('data.current_version', '1.0.0')
            ->assertJsonPath('data.online_upgrade.enabled', false);
    }

    public function test_api_content_order_balance_payment_comment_and_download_flow(): void
    {
        $this->seed();

        $user = User::where('email', 'admin@zfy-blog.test')->firstOrFail();
        $content = \App\Models\Content::where('type', 'files')->where('pricing->price', '>', 0)->firstOrFail();
        $token = $user->createToken('phpunit-flow')->plainTextToken;

        $orderNo = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/contents/{$content->slug}/orders", ['gateway' => 'balance'])
            ->assertOk()
            ->assertJsonPath('data.type', 'resource')
            ->json('data.order_no');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/orders/{$orderNo}/pay/balance")
            ->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.pay_channel', 'balance');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/contents/{$content->slug}/comments", ['body' => '这是一条待审核评论'])
            ->assertOk()
            ->assertJsonPath('data.status', 'pending');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/contents/{$content->slug}/downloads")
            ->assertOk()
            ->assertJsonPath('data.content_id', $content->id);
    }

    public function test_api_vip_order_points_payment_opens_membership(): void
    {
        $this->seed();

        $user = User::where('email', 'admin@zfy-blog.test')->firstOrFail();
        $vip = \App\Models\VipLevel::where('slug', 'vip')->firstOrFail();
        $token = $user->createToken('phpunit-vip')->plainTextToken;

        $orderNo = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/vip/{$vip->slug}/orders", ['period' => 'monthly', 'gateway' => 'points'])
            ->assertOk()
            ->assertJsonPath('data.type', 'vip')
            ->json('data.order_no');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/orders/{$orderNo}/pay/points")
            ->assertOk()
            ->assertJsonPath('data.status', 'paid');

        $this->assertDatabaseHas('user_vips', [
            'user_id' => $user->id,
            'vip_level_id' => $vip->id,
        ]);
    }

    public function test_download_requires_purchase_for_paid_content(): void
    {
        $this->seed();

        $user = User::where('email', 'star@zfy-blog.test')->firstOrFail();
        $content = \App\Models\Content::where('type', 'files')->where('pricing->price', '>', 39)->firstOrFail();
        $token = $user->createToken('phpunit-denied')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/contents/{$content->slug}/downloads")
            ->assertForbidden();

        $this->assertDatabaseHas('download_logs', [
            'content_id' => $content->id,
            'user_id' => $user->id,
            'status' => 'denied',
        ]);
    }
}
