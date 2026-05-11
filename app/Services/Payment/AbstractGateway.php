<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;

abstract class AbstractGateway implements PaymentGateway
{
    public function createPayment(Order $order): Payment
    {
        return $order->payments()->create([
            'gateway' => $this->code(),
            'status' => 'pending',
            'amount' => $order->total_amount,
            'request_payload' => [
                'mode' => 'sandbox-placeholder',
                'order_no' => $order->order_no,
                'notify_url' => route('payments.notify', ['gateway' => $this->code()]),
            ],
            'response_payload' => [
                'message' => $this->displayName().' 已创建沙箱占位支付单，请在后台配置真实商户密钥。',
            ],
        ]);
    }

    public function verifyNotify(array $payload): bool
    {
        return filled($payload['order_no'] ?? null) && filled($payload['trade_no'] ?? null);
    }

    public function query(Payment $payment): array
    {
        return [
            'status' => $payment->status,
            'trade_no' => $payment->trade_no,
            'gateway' => $this->code(),
            'sandbox' => true,
        ];
    }
}
