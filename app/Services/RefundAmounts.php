<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundAmounts
{
    public function completed(Order $order): string
    {
        return bcadd((string) Refund::where('order_id', $order->id)->where('status', 'refunded')->sum('amount'), '0', 2);
    }

    public function assertRemaining(Order $order, string $amount): void
    {
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $amount) || bccomp($amount, '0', 2) <= 0 || bccomp($amount, bcsub((string) $order->paid_amount, $this->completed($order), 2), 2) > 0) {
            throw ValidationException::withMessages(['amount' => '退款金额超过剩余可退金额。']);
        }
        if ($order->type !== 'product' && bccomp($amount, (string) $order->paid_amount, 2) !== 0) {
            throw ValidationException::withMessages(['amount' => '内容、会员和充值订单仅支持全额退款。']);
        }
    }

    public function split(Order $order, string $amount, string $completed): array
    {
        $spent = $order->pay_channel === 'points' || (int) data_get($order->meta, 'payment_breakdown.points', 0) > 0
            ? abs((int) DB::table('points_transactions')->where('points_account_id', $order->user->pointsAccount?->id)->where('type', 'payment')->where('meta->order_no', $order->order_no)->sum('points')) : 0;
        $priorPoints = (int) DB::table('points_transactions')->where('points_account_id', $order->user->pointsAccount?->id)->where('type', 'refund')->where('meta->order_no', $order->order_no)->sum('points');
        $cumulative = bcadd($completed, $amount, 2);
        $points = $spent > 0 ? (int) bcdiv(bcmul((string) $spent, $cumulative, 2), (string) $order->paid_amount, 0) - $priorPoints : 0;
        $cashTotal = (string) data_get($order->meta, 'payment_breakdown.balance', $order->pay_channel === 'points' ? '0' : $order->paid_amount);
        $priorCash = (string) DB::table('wallet_transactions')->where('wallet_id', $order->user->wallet?->id)->where('type', 'refund')->where('related_id', $order->id)->sum('amount');
        $cash = bcsub(bcdiv(bcmul($cashTotal, $cumulative, 4), (string) $order->paid_amount, 2), $priorCash, 2);

        return compact('cash', 'points');
    }
}
