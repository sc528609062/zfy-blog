<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentManager
{
    /** @var array<string, PaymentGateway> */
    private array $gateways;

    public function __construct(private readonly OrderService $orders)
    {
        $this->gateways = collect([
            new AlipayOfficialGateway(),
            new WechatOfficialGateway(),
            new HupijiaoGateway(),
            new EpayGateway(),
        ])->keyBy->code()->all();
    }

    public function gateway(string $code): PaymentGateway
    {
        return $this->gateways[$code] ?? throw new InvalidArgumentException("Unsupported payment gateway [{$code}].");
    }

    public function createPayment(Order $order, string $gateway): Payment
    {
        return $this->gateway($gateway)->createPayment($order);
    }

    public function queryPayment(Payment $payment): array
    {
        return $this->gateway($payment->gateway)->query($payment);
    }

    public function completeByNotify(string $gateway, array $payload): ?Order
    {
        $driver = $this->gateway($gateway);
        if (! $driver->verifyNotify($payload)) {
            return null;
        }

        return DB::transaction(function () use ($gateway, $payload) {
            $order = Order::where('order_no', $payload['order_no'])->lockForUpdate()->first();
            if (! $order) {
                return null;
            }

            $payment = $order->payments()->firstOrCreate(
                ['gateway' => $gateway, 'trade_no' => $payload['trade_no']],
                ['amount' => $order->total_amount, 'status' => 'pending']
            );

            $payment->update([
                'status' => 'paid',
                'response_payload' => $payload,
                'paid_at' => now(),
            ]);

            if ($order->status !== 'paid') {
                $order->update([
                    'status' => 'paid',
                    'paid_amount' => $order->total_amount,
                    'paid_at' => now(),
                    'pay_channel' => $gateway,
                ]);
            }
            $this->orders->fulfillPaidOrder($order->fresh());

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
