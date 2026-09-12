<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Services\ExternalRefundService;
use App\Services\Payment\OfficialGateway;
use App\Services\Payment\PaymentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Yansongda\Artful\Exception\InvalidResponseException;
use Yansongda\Pay\Exception\Exception as PayException;
use Yansongda\Supports\Collection;

class PaymentProtocolTest extends TestCase
{
    public function test_authoritative_missing_refund_allows_retry_with_the_same_refund_number(): void
    {
        $payment = $this->payment();
        $this->assertSame('not_found', $this->driver(['code' => '40004', 'sub_code' => 'ACQ.REFUND_NOT_EXIST'])->queryRefund($payment, 'RF1')['status']);
        $this->assertSame('not_found', $this->driver(['code' => 'RESOURCE_NOT_EXISTS'], 'wechat')->queryRefund($this->payment('wechat_official'), 'RF2')['status']);
    }

    public function test_sdk_missing_refund_exceptions_preserve_the_original_refund_number(): void
    {
        $alipay = new InvalidResponseException(PayException::RESPONSE_BUSINESS_CODE_WRONG, 'Business error', new Collection(['alipay_trade_fastpay_refund_query_response' => ['code' => '40004', 'sub_code' => 'ACQ.REFUND_NOT_EXIST']]));
        $wechat = new InvalidResponseException(PayException::RESPONSE_CODE_WRONG, 'Not found', new Collection(['code' => 'RESOURCE_NOT_EXISTS']));
        $this->assertSame(['status' => 'not_found', 'reference' => 'RF1'], $this->driver($alipay)->queryRefund($this->payment(), 'RF1'));
        $this->assertSame(['status' => 'not_found', 'reference' => 'RF2'], $this->driver($wechat, 'wechat')->queryRefund($this->payment('wechat_official'), 'RF2'));
    }

    public function test_other_sdk_failures_do_not_authorize_another_refund_attempt(): void
    {
        foreach ([
            new InvalidResponseException(PayException::RESPONSE_BUSINESS_CODE_WRONG, 'Business error', new Collection(['code' => '40004', 'sub_code' => 'ACQ.SYSTEM_ERROR'])),
            new InvalidResponseException(InvalidResponseException::REQUEST_RESPONSE_ERROR, 'Transport error', new Collection(['code' => '40004', 'sub_code' => 'ACQ.REFUND_NOT_EXIST'])),
            new \RuntimeException('Timeout'),
        ] as $exception) {
            try {
                $this->driver($exception)->queryRefund($this->payment(), 'RF3');
                $this->fail('An uncertain refund must remain uncertain');
            } catch (\Throwable $actual) {
                $this->assertSame($exception, $actual);
            }
        }
    }

    use RefreshDatabase;

    private function payment(string $gateway = 'alipay_official', string $status = 'paid'): Payment
    {
        $order = Order::create(['user_id' => User::factory()->create()->id, 'order_no' => 'TEST-'.uniqid(), 'type' => 'content', 'status' => $status, 'pay_channel' => $gateway, 'total_amount' => '12.30', 'paid_amount' => $status === 'paid' ? '12.30' : 0]);

        return $order->payments()->create(['gateway' => $gateway, 'status' => $status, 'amount' => '12.30', 'trade_no' => $status === 'paid' ? 'channel-123' : null]);
    }

    private function driver(array|\Throwable $response, string $provider = 'alipay'): OfficialGateway
    {
        return new class($response, $provider) extends OfficialGateway
        {
            public function __construct(private array|\Throwable $response, private string $name) {}

            protected function provider(): string
            {
                return $this->name;
            }

            public function code(): string
            {
                return $this->name.'_official';
            }

            public function displayName(): string
            {
                return $this->name;
            }

            protected function sdk(): mixed
            {
                return new class($this->response)
                {
                    public function __construct(private array|\Throwable $response) {}

                    public function query(array $payload): mixed
                    {
                        if ($this->response instanceof \Throwable) {
                            throw $this->response;
                        }

                        return collect($this->response);
                    }

                    public function refund(array $payload): mixed
                    {
                        return collect($this->response);
                    }

                    public function close(array $payload): mixed
                    {
                        return collect($this->response);
                    }
                };
            }
        };
    }

    public function test_alipay_query_requires_refund_success_and_matching_amount(): void
    {
        $payment = $this->payment();
        $response = ['code' => '10000', 'out_trade_no' => $payment->order->order_no, 'trade_no' => $payment->trade_no, 'out_request_no' => 'RF1', 'refund_amount' => '12.30'];
        $this->assertSame('processing', $this->driver($response)->queryRefund($payment, 'RF1')['status']);
        $response['refund_status'] = 'REFUND_SUCCESS';
        $this->assertSame('refunded', $this->driver($response)->queryRefund($payment, 'RF1')['status']);
        $response['refund_amount'] = '1.00';
        $this->expectException(ValidationException::class);
        $this->driver($response)->queryRefund($payment, 'RF1');
    }

    public function test_official_partial_refund_query_validates_the_requested_amount(): void
    {
        $payment = $this->payment();
        $response = ['code' => '10000', 'out_trade_no' => $payment->order->order_no, 'trade_no' => $payment->trade_no, 'out_request_no' => 'PART1', 'refund_amount' => '3.00', 'refund_status' => 'REFUND_SUCCESS'];
        $this->assertSame('refunded', $this->driver($response)->queryRefundAmount($payment, 'PART1', '3.00')['status']);
        $this->expectException(ValidationException::class);
        $this->driver($response)->queryRefundAmount($payment, 'PART1', '4.00');
    }

    public function test_wechat_processing_is_not_completed_and_refund_identity_is_checked(): void
    {
        $payment = $this->payment('wechat_official');
        $response = ['out_trade_no' => $payment->order->order_no, 'out_refund_no' => 'RF2', 'refund_id' => 'WX-REFUND', 'status' => 'PROCESSING', 'amount' => ['refund' => 1230, 'currency' => 'CNY']];
        $this->assertSame('processing', $this->driver($response, 'wechat')->refund($payment, 'RF2', '12.30')['status']);
        $this->expectException(ValidationException::class);
        $this->driver($response, 'wechat')->queryRefund($payment, 'WRONG');
    }

    public function test_failed_channel_close_is_not_acknowledged(): void
    {
        $this->expectException(ValidationException::class);
        $this->driver(['code' => '40004'])->close($this->payment());
    }

    public function test_late_payment_is_refunded_once_without_fulfillment(): void
    {
        $payment = $this->payment('epay', 'closed');
        $manager = app(PaymentManager::class);
        $payload = ['out_trade_no' => $payment->order->order_no, 'trade_no' => 'late-001', 'money' => '12.30'];
        $this->assertSame('refund_pending', $manager->completeVerified('epay', $payload)->status);
        $this->assertSame('refund_pending', $manager->completeVerified('epay', $payload)->status);
        $this->assertNull(data_get($payment->order->fresh()->meta, 'fulfilled_at'));
        $this->assertDatabaseCount('refunds', 1);
        $refund = Refund::first();
        app(ExternalRefundService::class)->process($refund->id, 'manual-proof-123');
        app(ExternalRefundService::class)->process($refund->id, 'manual-proof-123');
        $this->assertSame('refunded', $payment->order->fresh()->status);
        $this->assertDatabaseCount('payment_logs', 1);
        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    public function test_manual_refund_requires_channel_evidence(): void
    {
        $payment = $this->payment('epay');
        $refund = Refund::create(['order_id' => $payment->order_id, 'user_id' => $payment->order->user_id, 'status' => 'pending', 'amount' => '12.30', 'reason' => 'Test']);
        $this->expectException(ValidationException::class);
        app(ExternalRefundService::class)->process($refund->id);
    }

    public function test_missing_configuration_creates_no_payment_and_configured_retry_reuses_attempt(): void
    {
        $order = Order::create(['user_id' => User::factory()->create()->id, 'order_no' => 'ATTEMPT-1', 'type' => 'recharge', 'status' => 'pending', 'pay_channel' => 'epay', 'total_amount' => '12.30']);
        config(['payments.epay.key' => '']);
        try {
            app(PaymentManager::class)->createPayment($order, 'epay');
            $this->fail('Expected configuration rejection');
        } catch (ValidationException) {
            $this->assertDatabaseCount('payments', 0);
        }
        config(['payments.epay.key' => 'test-only', 'payments.epay.pid' => '1', 'payments.epay.url' => 'https://payments.example.test']);
        $payment = app(PaymentManager::class)->createPayment($order, 'epay');
        $this->assertNotEmpty(data_get($payment->response_payload, 'checkout_url'));
        $this->assertSame($payment->id, app(PaymentManager::class)->createPayment($order, 'epay')->id);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('payment_logs', 0);
    }

    public function test_uncertain_attempt_is_queried_before_new_checkout_is_created(): void
    {
        $payment = $this->payment('epay', 'pending');
        config(['payments.epay.key' => 'test-only', 'payments.epay.pid' => '1', 'payments.epay.url' => 'https://payments.example.test']);
        DB::table('payment_logs')->insert(['payment_id' => $payment->id, 'gateway' => 'epay', 'event' => 'create_failed', 'status' => 'uncertain', 'payload' => '{}', 'created_at' => now(), 'updated_at' => now()]);
        Http::fake(['https://payments.example.test/api.php*' => Http::response(['code' => 1, 'status' => 1, 'out_trade_no' => $payment->order->order_no, 'trade_no' => 'recovered-123', 'money' => '12.30'])]);
        $result = app(PaymentManager::class)->createPayment($payment->order, 'epay');
        $this->assertSame('paid', $result->status);
        $this->assertSame($payment->id, $result->id);
        $this->assertNull(data_get($result->response_payload, 'checkout_url'));
        Http::assertSentCount(1);
    }
}
