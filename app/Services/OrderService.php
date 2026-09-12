<?php

namespace App\Services;

use App\Models\Content;
use App\Models\Order;
use App\Models\User;
use App\Models\VipLevel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function createForContent(Content $content, ?User $user = null, string $gateway = 'alipay_official'): Order
    {
        abort_unless($user && Content::published()->whereKey($content->id)->exists(), 404);

        return DB::transaction(function () use ($content, $user, $gateway) {
            User::whereIn('id', array_filter([$user->id, $content->author_id]))->orderBy('id')->lockForUpdate()->get();
            $price = bcadd((string) data_get($content->pricing, 'price', '0'), '0', 2);
            if (bccomp($price, '0', 2) < 0) {
                $price = '0.00';
            }
            $vip = $user->vip()->with('level')->first();
            if ($vip?->isActive() && $vip->level) {
                $percentage = data_get($vip->meta, 'snapshot.discount_percent', $vip->level->discount_percent);
                $fixed = data_get($vip->meta, 'snapshot.fixed_discount', $vip->level->fixed_discount);
                $price = data_get($content->access_rules, 'vip_free', false) ? '0.00' : bcsub(bcdiv(bcmul($price, (string) $percentage, 4), '100', 2), (string) $fixed, 2);
                if (bccomp($price, '0', 2) < 0) {
                    $price = '0.00';
                }
            }
            $order = Order::create([
                'user_id' => $user?->id,
                'order_no' => 'ZF'.strtoupper((string) Str::ulid()),
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
                'meta' => ['type' => $content->type, 'author_settlement' => app(CommerceOperations::class)->authorSnapshot($content)],
            ]);

            if ($order->status === 'paid') {
                $this->fulfillPaidOrder($order, $user);
            }
            zfy_after_commit('zfy_order_created', $order);

            return $order;
        });
    }

    public function createForVip(VipLevel $level, User $user, string $period = 'yearly', string $gateway = 'alipay_official'): Order
    {
        $snapshot = app(VipEntitlementService::class)->quote($level, $user, $period);
        $price = $snapshot['price'];

        return DB::transaction(function () use ($level, $user, $period, $gateway, $price, $snapshot) {
            $order = Order::create([
                'user_id' => $user->id,
                'order_no' => 'ZF'.strtoupper((string) Str::ulid()),
                'type' => 'vip',
                'status' => $price <= 0 ? 'paid' : 'pending',
                'pay_channel' => $gateway,
                'total_amount' => $price,
                'paid_amount' => $price <= 0 ? 0 : 0,
                'paid_at' => $price <= 0 ? now() : null,
                'expires_at' => now()->addMinutes(30),
                'buyer_snapshot' => ['id' => $user->id, 'name' => $user->name],
                'meta' => ['vip_level' => $level->slug, 'period' => $period, 'vip_snapshot' => $snapshot],
            ]);

            $order->items()->create([
                'item_type' => 'vip',
                'item_id' => $level->id,
                'title' => $level->name.' '.(['monthly' => '月度会员', 'quarterly' => '季度会员', 'yearly' => '年度会员', 'lifetime' => '永久会员'][$period]),
                'quantity' => 1,
                'unit_price' => $price,
                'meta' => $snapshot,
            ]);

            if ($order->status === 'paid') {
                $this->fulfillPaidOrder($order, $user);
            }
            zfy_after_commit('zfy_order_created', $order);

            return $order;
        });
    }

    public function payWithBalance(Order $order, User $user, int $points = 0): Order
    {
        return DB::transaction(function () use ($order, $user, $points) {
            $order = $this->lockPayableOrder($order, $user);
            abort_if($order->type === 'recharge', 422, '充值订单需要通过外部支付渠道支付。');
            if ($order->status === 'paid') {
                return $order;
            }
            $wallet = $user->wallet()->lockForUpdate()->firstOrCreate([], ['balance' => 0, 'frozen_balance' => 0]);
            $amount = (string) $order->total_amount;
            if ($points < 0 || $points > 100000000) {
                throw ValidationException::withMessages(['points' => '积分数量无效。']);
            }
            $pointsDiscount = bcdiv((string) $points, '10', 2);
            if (bccomp($pointsDiscount, $amount, 2) > 0) {
                throw ValidationException::withMessages(['points' => '抵扣积分不能超过订单金额。']);
            }
            if ($points > 0) {
                $account = $user->pointsAccount()->lockForUpdate()->firstOrCreate([], ['points' => 0]);
                if ($account->points < $points) {
                    throw ValidationException::withMessages(['points' => '积分不足。']);
                }
                $account->decrement('points', $points);
                DB::table('points_transactions')->insert(['points_account_id' => $account->id, 'type' => 'payment', 'points' => -$points, 'balance_after' => $account->fresh()->points, 'remark' => '积分抵扣订单 '.$order->order_no, 'meta' => json_encode(['order_no' => $order->order_no]), 'created_at' => now(), 'updated_at' => now()]);
                $amount = bcsub($amount, $pointsDiscount, 2);
            }

            if (bccomp((string) $wallet->balance, $amount, 2) < 0) {
                throw ValidationException::withMessages(['wallet' => '余额不足。']);
            }

            if ($order->status === 'paid') {
                return $order;
            }

            $balanceAfter = bcsub((string) $wallet->balance, $amount, 2);
            $wallet->update(['balance' => $balanceAfter]);
            DB::table('wallet_transactions')->insert([
                'wallet_id' => $wallet->id,
                'type' => 'payment',
                'amount' => bcsub('0', $amount, 2),
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
                'paid_amount' => $order->total_amount,
                'paid_at' => now(),
                'pay_channel' => 'balance',
                'meta' => [...($order->meta ?? []), 'payment_breakdown' => ['balance' => $amount, 'points' => $points, 'points_discount' => $pointsDiscount, 'points_per_yuan' => 10]],
            ]);
            $this->fulfillPaidOrder($order->fresh(), $user);

            return $order->fresh(['items', 'payments']);
        });
    }

    public function payWithPoints(Order $order, User $user): Order
    {
        return DB::transaction(function () use ($order, $user) {
            $order = $this->lockPayableOrder($order, $user);
            abort_if($order->type === 'recharge', 422, '充值订单不能使用积分支付。');
            if ($order->status === 'paid') {
                return $order;
            }
            $account = $user->pointsAccount()->lockForUpdate()->firstOrCreate([], ['points' => 0, 'frozen_points' => 0]);
            $points = (int) bcdiv(bcadd(bcmul((string) $order->total_amount, '100', 0), '9', 0), '10', 0);

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
        if ($user?->is_banned) {
            return false;
        }
        if ($user && ($content->author_id === $user->id || $user->can('manage contents'))) {
            return true;
        }
        if (! Content::published()->whereKey($content->id)->exists()) {
            return false;
        }
        $visibility = data_get($content->access_rules, 'visibility', 'public');
        if ($visibility !== 'public' && ! app(ContentVisibility::class)->allows($visibility, $content, $user)) {
            return false;
        }
        $price = (string) data_get($content->pricing, 'price', '0');

        if (bccomp($price, '0', 2) <= 0 || (bool) data_get($content->access_rules, 'guest', false)) {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ((bool) data_get($content->access_rules, 'vip_free', false) && $user->vip()->first()?->isActive()) {
            return true;
        }

        return Order::where('user_id', $user->id)
            ->where('status', 'paid')
            ->whereHas('items', fn ($query) => $query->where(function ($query) use ($content) {
                $query->where(fn ($query) => $query->where('item_type', 'content')->where('item_id', $content->id))
                    ->orWhere(fn ($query) => $query->where('item_type', 'product')->where('meta->content_id', $content->id));
            }))
            ->exists();
    }

    public function fulfillPaidOrder(Order $order, ?User $user = null): void
    {
        DB::transaction(function () use ($order, $user) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $user ??= $order->user;

            if (! $user || $order->status !== 'paid' || data_get($order->meta, 'fulfilled_at')) {
                return;
            }
            app(OrderParticipantLocks::class)->acquire($order);
            $user = User::findOrFail($user->id);

            if ($order->type === 'recharge') {
                $wallet = $user->wallet()->lockForUpdate()->firstOrCreate([], ['balance' => 0]);
                $balance = bcadd((string) $wallet->balance, (string) $order->paid_amount, 2);
                $wallet->update(['balance' => $balance]);
                DB::table('wallet_transactions')->insert(['wallet_id' => $wallet->id, 'type' => 'recharge', 'amount' => $order->paid_amount, 'balance_after' => $balance, 'related_type' => 'order', 'related_id' => $order->id, 'remark' => '余额充值', 'created_at' => now(), 'updated_at' => now()]);
            }
            if ($order->type === 'vip') {
                app(VipEntitlementService::class)->grant($order, $user);
            }
            app(ProductOrderService::class)->fulfill($order);
            app(CommerceOperations::class)->creditAuthor($order);
            $order->update(['meta' => [...($order->meta ?? []), 'fulfilled_at' => now()->toISOString()]]);
            app(ExtensionOutbox::class)->record('order.paid:'.$order->id, 'zfy_order_paid', ['order_id' => $order->id, 'order_no' => $order->order_no, 'user_id' => $user->id, 'amount' => (string) $order->paid_amount]);
            if ($order->type === 'vip') {
                app(ExtensionOutbox::class)->record('vip.activated:'.$order->id, 'zfy_vip_activated', ['order_id' => $order->id, 'user_id' => $user->id]);
            }
        });
    }

    private function lockPayableOrder(Order $order, User $user): Order
    {
        $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
        abort_unless($order->user_id === $user->id, 403);
        app(OrderParticipantLocks::class)->acquire($order);
        if ($order->status === 'pending' && $order->payments()->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages(['gateway' => '该订单已有外部支付，请先确认渠道状态。']);
        }
        if ($order->status !== 'paid' && ($order->status !== 'pending' || $order->expires_at?->isPast() || $order->total_amount < 0)) {
            throw ValidationException::withMessages(['order' => '订单已关闭或过期，不能支付。']);
        }

        return $order;
    }

    public function createRecharge(User $user, string $amount, string $gateway): Order
    {
        if (! in_array($gateway, ['epay', 'alipay_official', 'wechat_official', 'hupijiao_v3'], true) || ! preg_match('/^\d+(?:\.\d{1,2})?$/', $amount) || bccomp($amount, '1', 2) < 0 || bccomp($amount, '10000', 2) > 0) {
            throw ValidationException::withMessages(['amount' => '充值金额或支付方式无效。']);
        }

        return DB::transaction(function () use ($user, $amount, $gateway) {
            $order = Order::create(['user_id' => $user->id, 'order_no' => 'ZF'.Str::ulid(), 'type' => 'recharge', 'status' => 'pending', 'pay_channel' => $gateway, 'total_amount' => $amount, 'paid_amount' => 0, 'expires_at' => now()->addMinutes(30), 'buyer_snapshot' => ['name' => $user->name]]);
            $order->items()->create(['item_type' => 'recharge', 'title' => '余额充值', 'quantity' => 1, 'unit_price' => $amount]);
            zfy_after_commit('zfy_order_created', $order);

            return $order;
        });
    }
}
