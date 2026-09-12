<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\ExtensionOutbox;
use App\Services\OrderService;
use App\Support\Zfy\ExtensionRegistry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class PaymentManager
{
    /** @var array<string, PaymentGateway> */
    private array $gateways;

    public function __construct(private readonly OrderService $orders)
    {
        $this->gateways = collect([
            new AlipayOfficialGateway,
            new WechatOfficialGateway,
            new HupijiaoGateway,
            new EpayGateway,
        ])->keyBy->code()->all();
        foreach (app(ExtensionRegistry::class)->all('payment') as $code => $definition) {
            if (isset($this->gateways[$code])) {
                throw new InvalidArgumentException('Payment driver already exists: '.$code);
            }
            $this->gateways[$code] = app($definition['driver']);
        }
    }

    public function gateway(string $code): PaymentGateway
    {
        return $this->gateways[$code] ?? throw new InvalidArgumentException("Unsupported payment gateway [{$code}].");
    }

    public function available(): array
    {
        $available = [];
        foreach ($this->gateways as $code => $driver) {
            try {
                if ($driver instanceof ConfigurableGateway) {
                    $driver->assertConfigured();
                }
                $available[$code] = $driver->displayName();
            } catch (ValidationException) {
            }
        }

        return $available;
    }

    public function createPayment(Order $order, string $gateway): Payment
    {
        return Cache::store('file')->lock('zfy-payment-create-'.$order->id, 120)->block(5, function () use ($order, $gateway) {
            $driver = $this->gateway($gateway);
            if ($driver instanceof ConfigurableGateway) {
                $driver->assertConfigured();
            }
            $attempt = DB::transaction(function () use ($order, $gateway) {
                $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                if ($order->expires_at?->isPast()) {
                    throw ValidationException::withMessages(['order' => '订单已过期。']);
                }
                if ($order->status !== 'pending' || $order->pay_channel !== $gateway) {
                    throw ValidationException::withMessages(['order' => '订单状态或支付渠道不匹配。']);
                }
                $existing = $order->payments()->where('status', 'pending')->latest('id')->first();
                if ($existing) {
                    if ($existing->gateway !== $gateway) {
                        throw ValidationException::withMessages(['gateway' => '已有未结束的支付，请先查单。']);
                    }

                    return $existing;
                }
                zfy_validate('zfy_payment_creating', $order, $gateway);

                return $order->payments()->create(['gateway' => $gateway, 'status' => 'pending', 'amount' => $order->total_amount, 'request_payload' => ['out_trade_no' => $order->order_no], 'response_payload' => []]);
            });
            if (data_get($attempt->response_payload, 'checkout_url') || data_get($attempt->response_payload, 'qr_code')) {
                return $attempt;
            }
            if (DB::table('payment_logs')->where('payment_id', $attempt->id)->where('event', 'create_failed')->exists()) {
                $driver->query($attempt);
                $attempt->refresh();
                if ($attempt->status === 'paid') {
                    return $attempt;
                }
            }
            try {
                $payment = $driver->createPayment($order->fresh());
                zfy_after_commit('zfy_payment_created', $payment);

                return $payment;
            } catch (\Throwable $exception) {
                // Keep the merchant order number queryable when the remote result is uncertain.
                DB::table('payment_logs')->insert(['payment_id' => $attempt->id, 'gateway' => $gateway, 'event' => 'create_failed', 'status' => 'uncertain', 'payload' => json_encode(['exception' => $exception::class]), 'created_at' => now(), 'updated_at' => now()]);
                throw $exception;
            }
        });
    }

    public function queryPayment(Payment $payment): array
    {
        return $this->gateway($payment->gateway)->query($payment);
    }

    public function completeByNotify(string $gateway, array $payload): ?Order
    {
        if (! isset($this->gateways[$gateway])) {
            return null;
        }
        $driver = $this->gateway($gateway);
        if ($driver instanceof VerifiedNotificationGateway) {
            $payload = $driver->notification($payload);

            return $payload ? $this->completeVerified($gateway, $payload) : null;
        }
        if (! $driver->verifyNotify($payload)) {
            return null;
        }

        return $this->completeVerified($gateway, $payload);
    }

    public function completeVerified(string $gateway, array $payload): ?Order
    {
        if (! filled($payload['trade_no'] ?? null) || ! preg_match('/^\d+(?:\.\d{1,2})?$/', (string) ($payload['money'] ?? ''))) {
            return null;
        }

        return DB::transaction(function () use ($gateway, $payload) {
            $order = Order::where('order_no', $payload['out_trade_no'] ?? $payload['order_no'] ?? '')->lockForUpdate()->first();
            if (! $order) {
                return null;
            }
            if ($order->pay_channel !== $gateway || bccomp((string) ($payload['money'] ?? '-1'), (string) $order->total_amount, 2) !== 0) {
                return null;
            }
            $payment = $order->payments()->where('gateway', $gateway)->latest('id')->lockForUpdate()->first();
            if (! $payment || ($payment->trade_no && $payment->trade_no !== $payload['trade_no'])) {
                return null;
            }
            if (in_array($order->status, ['paid', 'refund_pending', 'refunded'], true)) {
                return $payment->status === 'paid' && $payment->trade_no === $payload['trade_no'] ? $order : null;
            }
            if (! in_array($order->status, ['pending', 'cancelled', 'closed'], true) || Payment::where('gateway', $gateway)->where('trade_no', $payload['trade_no'])->where('id', '!=', $payment->id)->exists()) {
                return null;
            }

            $payment->update([
                'status' => 'paid',
                'trade_no' => $payload['trade_no'],
                'response_payload' => $payload,
                'paid_at' => now(),
            ]);

            if (in_array($order->status, ['cancelled', 'closed'], true)) {
                $order->update(['status' => 'refund_pending', 'paid_amount' => $order->total_amount, 'paid_at' => now(), 'meta' => [...($order->meta ?? []), 'late_payment' => true]]);
                Refund::firstOrCreate(['order_id' => $order->id, 'status' => 'pending'], ['user_id' => $order->user_id, 'amount' => $order->total_amount, 'reason' => '订单关闭后收到支付，等待原路退回', 'metadata' => ['late_payment' => true]]);
                app(ExtensionOutbox::class)->record('payment.late:'.$payment->id, 'zfy_payment_late', ['payment_id' => $payment->id, 'order_id' => $order->id]);

                return $order;
            }

            if ($order->status !== 'paid') {
                $order->update([
                    'status' => 'paid',
                    'paid_amount' => $order->total_amount,
                    'paid_at' => now(),
                    'pay_channel' => $gateway,
                ]);
            }
            $this->orders->fulfillPaidOrder($order->fresh());
            app(ExtensionOutbox::class)->record('payment.paid:'.$payment->id, 'zfy_payment_paid', ['payment_id' => $payment->id, 'order_id' => $order->id]);

            DB::table('payment_logs')->insert([
                'payment_id' => $payment->id,
                'gateway' => $gateway,
                'event' => 'notify_paid',
                'status' => 'handled',
                'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $order;
        });
    }
}
