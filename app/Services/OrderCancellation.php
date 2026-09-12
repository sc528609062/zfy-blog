<?php

namespace App\Services;

use App\Models\Order;
use App\Services\Payment\ClosableGateway;
use App\Services\Payment\PaymentManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderCancellation
{
    public function cancel(Order $order, string $status = 'cancelled'): bool
    {
        return Cache::store('file')->lock('zfy-payment-create-'.$order->id, 120)->block(5, function () use ($order, $status) {
            $order->refresh();
            if ($order->status !== 'pending') {
                return false;
            }
            foreach ($order->payments()->where('status', 'pending')->get() as $payment) {
                $driver = app(PaymentManager::class)->gateway($payment->gateway);
                $driver->query($payment);
                if ($order->fresh()->status !== 'pending') {
                    return false;
                }
                if (! $driver instanceof ClosableGateway) {
                    throw ValidationException::withMessages(['payment' => '该渠道不能自动关单，已保留订单及库存，请先完成渠道对账。']);
                }
                $driver->close($payment);
            }

            return DB::transaction(function () use ($order, $status) {
                $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                if ($order->status !== 'pending') {
                    return false;
                }
                zfy_validate('zfy_order_cancelling', $order);
                app(InventoryService::class)->release($order);
                $order->payments()->where('status', 'pending')->update(['status' => 'closed']);
                $order->update(['status' => $status]);
                zfy_after_commit('zfy_order_cancelled', $order);

                return true;
            });
        });
    }
}
