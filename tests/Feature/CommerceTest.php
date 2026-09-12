<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Order;
use App\Models\User;
use App\Models\VipLevel;
use App\Services\OrderService;
use App\Services\Payment\PaymentManager;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommerceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreInstallSeeder::class);
    }

    public function test_stale_repeated_payment_only_deducts_once_and_fulfills_vip_once(): void
    {
        $user = User::factory()->create();
        $user->wallet()->create(['balance' => 100]);
        $service = app(OrderService::class);
        $order = $service->createForVip(VipLevel::where('slug', 'vip')->first(), $user, 'monthly', 'balance');
        $stale = Order::findOrFail($order->id);
        $service->payWithBalance($order, $user);
        $expiry = $user->vip()->first()->expires_at;
        $service->payWithBalance($stale, $user);
        $service->fulfillPaidOrder($stale->fresh());
        $this->assertSame('71.00', number_format($user->wallet()->first()->balance, 2, '.', ''));
        $this->assertDatabaseCount('wallet_transactions', 1);
        $this->assertTrue($expiry->equalTo($user->vip()->first()->expires_at));
    }

    public function test_fake_notify_is_rejected_and_order_stays_pending(): void
    {
        $user = User::factory()->create();
        $order = app(OrderService::class)->createForVip(VipLevel::first(), $user);
        $result = app(PaymentManager::class)->completeByNotify('alipay_official', ['order_no' => $order->order_no, 'trade_no' => 'fake']);
        $this->assertNull($result);
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_private_order_status_requires_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = app(OrderService::class)->createForVip(VipLevel::first(), $owner);
        $this->actingAs($other)->getJson('/orders/'.$order->order_no.'/status')->assertForbidden();
        $this->actingAs($owner)->getJson('/orders/'.$order->order_no.'/status')->assertOk();
    }

    public function test_public_api_does_not_leak_paid_body(): void
    {
        $content = Content::create(['type' => 'post', 'status' => 'published', 'title' => 'Paid article', 'slug' => 'paid', 'pricing' => ['price' => 10], 'markdown_cache' => 'secret-paid-body', 'rendered_html' => '<p>secret-paid-body</p>', 'block_json' => ['secret' => 'secret-paid-body']]);
        foreach (['/api/v1/contents', '/api/v1/contents/paid', '/api/v1/home', '/api/v1/search?q=Paid'] as $url) {
            $this->getJson($url)->assertOk()->assertDontSee('secret-paid-body');
        }
        $this->get('/content/'.$content->slug)->assertOk()->assertDontSee('secret-paid-body');
    }
}
