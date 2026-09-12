<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Refund;
use App\Services\Payment\AmountRefundQueryGateway;
use App\Services\Payment\PaymentManager;
use App\Services\Payment\RefundableGateway;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExternalRefundService
{
    public function process(int $id, ?string $manualReference = null): void
    {
        $orderId = Refund::findOrFail($id)->order_id;
        Cache::store('file')->lock('zfy-refund-order-'.$orderId, 120)->block(5, function () use ($id, $manualReference) {
            $refund = Refund::findOrFail($id);
            if ($refund->status === 'refunded') {
                return;
            }
            $order = Order::findOrFail($refund->order_id);
            if (Refund::where('order_id', $order->id)->where('id', '!=', $id)->where('status', 'processing')->exists()) {
                throw ValidationException::withMessages(['refund' => '该订单已有正在处理或完成的退款。']);
            }
            if (! in_array($refund->status, ['pending', 'processing'], true) || ! in_array($order->status, ['paid', 'refund_pending'], true)) {
                throw ValidationException::withMessages(['refund' => '退款状态或金额不匹配。']);
            }
            $payment = $order->payments()->where('status', 'paid')->latest('id')->firstOrFail();
            app(RefundAmounts::class)->assertRemaining($order, (string) $refund->amount);
            $driver = app(PaymentManager::class)->gateway($payment->gateway);
            if ($driver instanceof RefundableGateway && ! $driver instanceof AmountRefundQueryGateway && bccomp((string) $refund->amount, (string) $payment->amount, 2) !== 0) {
                throw ValidationException::withMessages(['refund' => '此扩展渠道未声明部分退款查单能力。']);
            }
            $refundNo = 'ZFR'.$refund->id;
            if (! $driver instanceof RefundableGateway && ! filled($manualReference)) {
                throw ValidationException::withMessages(['reference' => '此渠道需人工原路退款，请填写渠道退款凭证。']);
            }
            $alreadyStarted = DB::transaction(function () use ($order, $refund, $refundNo) {
                $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                $refund = Refund::whereKey($refund->id)->lockForUpdate()->firstOrFail();
                if (! in_array($refund->status, ['pending', 'processing'], true) || ! in_array($order->status, ['paid', 'refund_pending'], true)) {
                    throw ValidationException::withMessages(['refund' => '退款状态已改变，请重新核对。']);
                }
                app(RefundAmounts::class)->assertRemaining($order, (string) $refund->amount);
                if (Refund::where('order_id', $order->id)->where('id', '!=', $refund->id)->where('status', 'processing')->exists()) {
                    throw ValidationException::withMessages(['refund' => '另一笔退款正在处理。']);
                }
                $started = $refund->status === 'processing';
                $refund->update(['status' => 'processing', 'metadata' => [...($refund->metadata ?? []), 'refund_no' => $refundNo]]);

                return $started;
            });
            $refund->refresh();
            if ($driver instanceof RefundableGateway) {
                if (data_get($refund->metadata, 'channel_result.status') === 'refunded') {
                    app(CommerceOperations::class)->handleRefund($id, 'refunded');

                    return;
                }
                $result = $alreadyStarted
                    ? ($driver instanceof AmountRefundQueryGateway ? $driver->queryRefundAmount($payment, $refundNo, (string) $refund->amount) : $driver->queryRefund($payment, $refundNo))
                    : $driver->refund($payment, $refundNo, (string) $refund->amount);
                if ($alreadyStarted && $result['status'] === 'not_found') {
                    $result = $driver->refund($payment, $refundNo, (string) $refund->amount);
                }
            } else {
                $result = ['status' => 'refunded', 'reference' => $manualReference, 'manual' => true, 'operator_id' => auth()->id()];
            }
            $refund->refresh()->update(['metadata' => [...($refund->metadata ?? []), 'channel_result' => $result]]);
            DB::table('payment_logs')->insert(['payment_id' => $payment->id, 'gateway' => $payment->gateway, 'event' => 'refund', 'status' => $result['status'], 'payload' => json_encode(['refund_id' => $id, 'reference' => $result['reference'], 'manual' => $result['manual'] ?? false]), 'created_at' => now(), 'updated_at' => now()]);
            if ($result['status'] === 'refunded') {
                app(CommerceOperations::class)->handleRefund($id, 'refunded');
            }
        });
    }
}
