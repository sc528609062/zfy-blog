<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

abstract class AbstractGateway implements PaymentGateway
{
    protected function rememberPayment(Order $order, array $request, array $response): Payment
    {
        return DB::transaction(function () use ($order, $request, $response) {
            Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $payment = $order->payments()->where('gateway', $this->code())->latest('id')->lockForUpdate()->first();
            if (! $payment) {
                return $order->payments()->create(['gateway' => $this->code(), 'status' => 'pending', 'amount' => $order->total_amount, 'request_payload' => $request, 'response_payload' => $response]);
            }
            if ($payment->status === 'pending') {
                $payment->update(['request_payload' => $request, 'response_payload' => $response]);
            }

            return $payment;
        });
    }

    public function createPayment(Order $order): Payment
    {
        throw ValidationException::withMessages(['gateway' => $this->displayName().'尚未配置可用商户，请选择余额或积分支付。']);
    }

    public function verifyNotify(array $payload): bool
    {
        return false;
    }

    public function query(Payment $payment): array
    {
        return [
            'status' => $payment->status,
            'trade_no' => $payment->trade_no,
            'gateway' => $this->code(),
            'source' => 'local',
        ];
    }
}
