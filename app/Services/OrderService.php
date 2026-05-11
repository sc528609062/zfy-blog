<?php

namespace App\Services;

use App\Models\Content;
use App\Models\Order;
use App\Models\User;
use App\Models\UserVip;
use App\Models\VipLevel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function createForContent(Content $content, ?User $user = null, string $gateway = 'alipay_official'): Order
    {
        $price = (float) data_get($content->pricing, 'price', 0);
        $order = Order::create([
            'user_id' => $user?->id,
            'order_no' => 'ZF'.now()->format('YmdHis').random_int(1000, 9999),
            'type' => $content->type === 'files' ? 'resource' : 'content',
            'status' => $price <= 0 ? 'paid' : 'pending',
            'pay_channel' => $gateway,
            'total_amount' => $price,
            'paid_amount' => $price <= 0 ? 0 : 0,
            'paid_at' => $price <= 0 ? now() : null,
            'expires_at' => now()->addMinutes(30),
            'buyer_snapshot' => $user ? ['id' => $user->id, 'name' => $user->name] : ['guest' => true],
            'meta' => ['content_slug' => $content->slug],
        ]);

        $order->items()->create([
            'item_type' => 'content',
            'item_id' => $content->id,
            'title' => $content->title,
            'quantity' => 1,
            'unit_price' => $price,
            'meta' => ['type' => $content->type],
        ]);

        return $order;
    }

    public function createForVip(VipLevel $level, User $user, string $period = 'yearly', string $gateway = 'alipay_official'): Order
    {
        $price = (float) ($period === 'monthly' ? $level->price_monthly : $level->price_yearly);

        return DB::transaction(function () use ($level, $user, $period, $gateway, $price) {
            $order = Order::create([
                'user_id' => $user->id,
                'order_no' => 'ZF'.now()->format('YmdHis').random_int(1000, 9999),
                'type' => 'vip',
                'status' => $price <= 0 ? 'paid' : 'pending',
                'pay_channel' => $gateway,
                'total_amount' => $price,
                'paid_amount' => $price <= 0 ? 0 : 0,
                'paid_at' => $price <= 0 ? now() : null,
                'expires_at' => now()->addMinutes(30),
                'buyer_snapshot' => ['id' => $user->id, 'name' => $user->name],
                'meta' => ['vip_level' => $level->slug, 'period' => $period],
            ]);

            $order->items()->create([
                'item_type' => 'vip',
                'item_id' => $level->id,
                'title' => $level->name.' '.($period === 'monthly' ? '月度会员' : '年度会员'),
                'quantity' => 1,
                'unit_price' => $price,
                'meta' => ['period' => $period],
            ]);

            return $order;
        });
    }

    public function payWithBalance(Order $order, User $user): Order
    {
        return DB::transaction(function () use ($order, $user) {
            $wallet = $user->wallet()->lockForUpdate()->firstOrCreate([], ['balance' => 0, 'frozen_balance' => 0]);
            $amount = (float) $order->total_amount;

            if ((float) $wallet->balance < $amount) {
                throw ValidationException::withMessages(['wallet' => '余额不足。']);
            }

            if ($order->status === 'paid') {
                return $order;
            }

            $balanceAfter = (float) $wallet->balance - $amount;
            $wallet->update(['balance' => $balanceAfter]);
            DB::table('wallet_transactions')->insert([
                'wallet_id' => $wallet->id,
                'type' => 'payment',
                'amount' => -$amount,
                'balance_after' => $balanceAfter,
                'related_type' => 'order',
                'related_id' => $order->id,
                'remark' => '余额支付订单 '.$order->order_no,
                'meta' => json_encode(['order_no' => $order->order_no], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $order->update([
                'status' => 'paid',
                'paid_amount' => $amount,
                'paid_at' => now(),
                'pay_channel' => 'balance',
            ]);
            $this->fulfillPaidOrder($order->fresh(), $user);

            return $order->fresh(['items', 'payments']);
        });
    }

    public function payWithPoints(Order $order, User $user): Order
    {
        return DB::transaction(function () use ($order, $user) {
            $account = $user->pointsAccount()->lockForUpdate()->firstOrCreate([], ['points' => 0, 'frozen_points' => 0]);
            $points = (int) round(((float) $order->total_amount) * 10);

            if ($account->points < $points) {
                throw ValidationException::withMessages(['points' => '积分不足。']);
            }

            if ($order->status === 'paid') {
                return $order;
            }

            $balanceAfter = $account->points - $points;
            $account->update(['points' => $balanceAfter]);
            DB::table('points_transactions')->insert([
                'points_account_id' => $account->id,
                'type' => 'payment',
                'points' => -$points,
                'balance_after' => $balanceAfter,
                'remark' => '积分支付订单 '.$order->order_no,
                'meta' => json_encode(['order_no' => $order->order_no], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $order->update([
                'status' => 'paid',
                'paid_amount' => $order->total_amount,
                'paid_at' => now(),
                'pay_channel' => 'points',
            ]);
            $this->fulfillPaidOrder($order->fresh(), $user);

            return $order->fresh(['items', 'payments']);
        });
    }

    public function userCanAccessContent(Content $content, ?User $user): bool
    {
        $price = (float) data_get($content->pricing, 'price', 0);

        if ($price <= 0 || (bool) data_get($content->access_rules, 'guest', false)) {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ((bool) data_get($content->access_rules, 'vip_free', false) && $user->vip?->expires_at?->isFuture()) {
            return true;
        }

        return Order::where('user_id', $user->id)
            ->where('status', 'paid')
            ->whereHas('items', fn ($query) => $query->where('item_type', 'content')->where('item_id', $content->id))
            ->exists();
    }

    public function fulfillPaidOrder(Order $order, ?User $user = null): void
    {
        $user ??= $order->user;

        if (! $user || $order->status !== 'paid') {
            return;
        }

        if ($order->type === 'vip') {
            $level = VipLevel::where('slug', data_get($order->meta, 'vip_level'))->first();
            if (! $level) {
                return;
            }

            $period = data_get($order->meta, 'period', 'yearly');
            $currentExpiry = $user->vip?->expires_at?->isFuture() ? $user->vip->expires_at : now();
            $expiresAt = $period === 'monthly' ? $currentExpiry->copy()->addMonth() : $currentExpiry->copy()->addYear();

            UserVip::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'vip_level_id' => $level->id,
                    'started_at' => now(),
                    'expires_at' => $expiresAt,
                    'meta' => ['source_order_no' => $order->order_no],
                ]
            );
        }
    }
}
