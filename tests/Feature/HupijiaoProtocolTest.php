<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\OrderService;
use App\Services\Payment\PaymentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HupijiaoProtocolTest extends TestCase
{
    use RefreshDatabase;

    private function signed(array $payload): array
    {
        ksort($payload);
        $payload['hash'] = md5(implode('&', array_map(fn ($key) => $key.'='.$payload[$key], array_keys($payload))).'test-secret');

        return $payload;
    }

    public function test_signed_create_query_and_replayed_notification_credit_recharge_once(): void
    {
        config(['payments.hupijiao.appid' => 'test-app', 'payments.hupijiao.secret' => 'test-secret', 'payments.hupijiao.url' => 'https://api.xunhupay.com/payment']);
        $buyer = User::factory()->create();
        $order = app(OrderService::class)->createRecharge($buyer, '12.30', 'hupijiao_v3');
        $notify = ['appid' => 'test-app', 'plugins' => 'zfy_blog', 'status' => 'OD', 'trade_order_id' => $order->order_no, 'transaction_id' => 'HPJ-TEST-1', 'total_fee' => '12.30'];
        Http::preventStrayRequests();
        Http::fake([
            'https://api.xunhupay.com/payment/do.html' => Http::response($this->signed(['errcode' => 0, 'url' => 'https://api.xunhupay.com/checkout/test'])),
            'https://api.xunhupay.com/payment/query.html' => Http::response($this->signed(['errcode' => 0, ...$notify])),
        ]);
        $manager = app(PaymentManager::class);
        $payment = $manager->createPayment($order, 'hupijiao_v3');
        Http::assertSent(fn ($request) => $request['trade_order_id'] === $order->order_no && $request['total_fee'] === '12.30' && isset($request['hash']));
        $manager->queryPayment($payment);
        $manager->completeByNotify('hupijiao_v3', $this->signed($notify));
        $this->assertSame('12.30', $buyer->wallet()->first()->balance);
        $this->assertDatabaseCount('wallet_transactions', 1);
        $this->assertNull($manager->completeByNotify('hupijiao_v3', [...$this->signed($notify), 'total_fee' => '99.00']));
    }
}
