<?php

namespace App\Services;

use App\Models\AuthorSettlementRule;
use App\Models\Content;
use App\Models\Order;
use App\Models\Refund;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommerceOperations
{
    public function authorSnapshot(Content $content): array
    {
        $rules = AuthorSettlementRule::orderByDesc('id')->get();
        $profile = DB::table('author_profiles')->where('user_id', $content->author_id)->first();
        $rule = $rules->first(fn ($rule) => $rule->scope === 'content' && (int) $rule->target_id === $content->id)
            ?? $rules->first(fn ($rule) => $rule->scope === 'author' && (int) $rule->target_id === $content->author_id);
        if (! $rule && $profile) {
            return ['author_id' => $content->author_id, 'share_percent' => (string) $profile->share_percent, 'hold_days' => max(0, (int) data_get(json_decode($profile->meta ?? '{}', true), 'hold_days', 0)), 'source' => 'author-profile'];
        }
        $rule ??= $rules->first(fn ($rule) => $rule->scope === 'category' && $content->category_id && (int) $rule->target_id === $content->category_id)
            ?? $rules->first(fn ($rule) => $rule->scope === 'default' || $rule->is_default);

        return ['author_id' => $content->author_id, 'share_percent' => (string) ($rule?->share_percent ?? '50.00'), 'hold_days' => (int) ($rule?->hold_days ?? 0), 'rule_id' => $rule?->id];
    }

    public function creditAuthor(Order $order): void
    {
        if ($order->pay_channel === 'points') {
            return;
        }
        foreach ($order->items as $item) {
            if ($item->item_type !== 'content' || $order->paid_amount <= 0) {
                continue;
            }
            $content = Content::find($item->item_id);
            $snapshot = data_get($item->meta, 'author_settlement') ?? ($content ? $this->authorSnapshot($content) : []);
            $authorId = $snapshot['author_id'] ?? null;
            if (! $authorId || $authorId === $order->user_id || DB::table('author_earnings')->where('order_id', $order->id)->where('author_id', $authorId)->exists()) {
                continue;
            }
            $author = User::whereKey($authorId)->lockForUpdate()->first();
            if (! $author?->is_author || $author->author_status !== 'approved') {
                continue;
            }
            $percentage = $snapshot['share_percent'] ?? '50.00';
            $base = bcmul((string) $item->unit_price, (string) $item->quantity, 2);
            $cashPaid = (string) data_get($order->meta, 'payment_breakdown.balance', $order->paid_amount);
            if (bccomp($base, $cashPaid, 2) > 0) {
                $base = $cashPaid;
            }
            $amount = bcmul($base, bcdiv((string) max(0, min(100, $percentage)), '100', 4), 2);
            if (bccomp($amount, '0', 2) <= 0) {
                continue;
            }
            $wallet = $author->wallet()->lockForUpdate()->firstOrCreate([], ['balance' => 0]);
            $available = now()->addDays(max(0, min(365, (int) ($snapshot['hold_days'] ?? 0))));
            if ($available->isFuture()) {
                DB::table('author_earnings')->insert(['author_id' => $author->id, 'order_id' => $order->id, 'amount' => $amount, 'status' => 'pending', 'available_at' => $available, 'meta' => json_encode($snapshot), 'created_at' => now(), 'updated_at' => now()]);

                continue;
            }
            $balance = bcadd((string) $wallet->balance, $amount, 2);
            $wallet->update(['balance' => $balance]);
            DB::table('author_earnings')->insert(['author_id' => $author->id, 'order_id' => $order->id, 'amount' => $amount, 'status' => 'settled', 'available_at' => $available, 'meta' => json_encode($snapshot), 'created_at' => now(), 'updated_at' => now()]);
            $this->walletEntry($wallet, $amount, $balance, 'author_earning', $order->id, '内容销售分成');
        }
    }

    public function settleDueEarnings(): int
    {
        $settled = 0;
        foreach (DB::table('author_earnings')->where('status', 'pending')->where('available_at', '<=', now())->orderBy('id')->limit(500)->get() as $candidate) {
            $settled += DB::transaction(function () use ($candidate) {
                $order = Order::whereKey($candidate->order_id)->lockForUpdate()->first();
                $earning = DB::table('author_earnings')->where('id', $candidate->id)->lockForUpdate()->first();
                if (! $order || $order->status !== 'paid' || ! $earning || $earning->status !== 'pending' || Refund::where('order_id', $order->id)->whereIn('status', ['pending', 'processing'])->exists()) {
                    return 0;
                }
                User::whereKey($earning->author_id)->lockForUpdate()->firstOrFail();
                $wallet = Wallet::where('user_id', $earning->author_id)->lockForUpdate()->firstOrFail();
                $balance = bcadd((string) $wallet->balance, (string) $earning->amount, 2);
                $wallet->update(['balance' => $balance]);
                DB::table('author_earnings')->where('id', $earning->id)->update(['status' => 'settled', 'updated_at' => now()]);
                $this->walletEntry($wallet, (string) $earning->amount, $balance, 'author_earning', $order->id, '内容销售分成结算');

                return 1;
            }, 3);
        }

        return $settled;
    }

    public function requestWithdrawal(User $user, string $amount, string $method, string $account): int
    {
        return DB::transaction(function () use ($user, $amount, $method, $account) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $amount) || bccomp($amount, '1', 2) < 0 || ! in_array($method, ['alipay', 'wechat', 'bank'], true)) {
                throw ValidationException::withMessages(['amount' => '提现金额或方式无效。']);
            }
            if (DB::table('author_withdrawals')->where('author_id', $user->id)->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['amount' => '已有提现申请等待处理，请勿重复提交。']);
            }
            $wallet = $user->wallet()->lockForUpdate()->firstOrCreate([], ['balance' => 0]);
            if (bccomp((string) $wallet->balance, $amount, 2) < 0) {
                throw ValidationException::withMessages(['amount' => '可提现余额不足。']);
            }
            $balance = bcsub((string) $wallet->balance, $amount, 2);
            $wallet->update(['balance' => $balance, 'frozen_balance' => bcadd((string) $wallet->frozen_balance, $amount, 2)]);
            $id = DB::table('author_withdrawals')->insertGetId(['author_id' => $user->id, 'amount' => $amount, 'status' => 'pending', 'method' => $method, 'account_snapshot' => json_encode(['account' => $account]), 'created_at' => now(), 'updated_at' => now()]);
            $this->walletEntry($wallet, '-'.$amount, $balance, 'withdrawal_hold', $id, '提现冻结');

            return $id;
        });
    }

    public function handleWithdrawal(int $id, string $status, ?string $reference = null): void
    {
        if ($status === 'paid' && ! filled($reference)) {
            throw ValidationException::withMessages(['reference' => '请填写打款凭证。']);
        }
        DB::transaction(function () use ($id, $status, $reference) {
            $withdrawal = DB::table('author_withdrawals')->where('id', $id)->lockForUpdate()->first();
            abort_unless($withdrawal, 404);
            if ($withdrawal->status === $status) {
                return;
            }
            if ($withdrawal->status !== 'pending' || ! in_array($status, ['paid', 'rejected'], true)) {
                throw ValidationException::withMessages(['status' => '提现申请已处理。']);
            }
            $wallet = Wallet::where('user_id', $withdrawal->author_id)->lockForUpdate()->firstOrFail();
            if (bccomp((string) $wallet->frozen_balance, (string) $withdrawal->amount, 2) < 0) {
                throw ValidationException::withMessages(['amount' => '冻结余额不一致，请核对流水。']);
            }
            $balance = $status === 'rejected' ? bcadd((string) $wallet->balance, (string) $withdrawal->amount, 2) : $wallet->balance;
            $wallet->update(['balance' => $balance, 'frozen_balance' => bcsub((string) $wallet->frozen_balance, (string) $withdrawal->amount, 2)]);
            DB::table('author_withdrawals')->where('id', $id)->update(['status' => $status, 'account_snapshot' => json_encode([...(json_decode($withdrawal->account_snapshot, true) ?? []), 'reference' => $reference, 'operator_id' => auth()->id()]), 'handled_at' => now(), 'updated_at' => now()]);
            $this->walletEntry($wallet, $status === 'rejected' ? $withdrawal->amount : '0', (string) $balance, 'withdrawal_'.$status, $id, $status === 'paid' ? '提现已打款' : '提现驳回退回');
        });
    }

    public function requestRefund(User $user, Order $order, string $reason, ?string $amount = null): Refund
    {
        return DB::transaction(function () use ($user, $order, $reason, $amount) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($order->user_id === $user->id, 403);
            if ($order->status !== 'paid' || $order->paid_amount <= 0) {
                throw ValidationException::withMessages(['order' => '该订单不支持自助退款申请。']);
            }
            if ($order->items()->where('meta->type', 'card')->exists()) {
                throw ValidationException::withMessages(['order' => '已交付卡密需联系管理员处理售后。']);
            }

            if ($existing = Refund::where('order_id', $order->id)->whereIn('status', ['pending', 'processing'])->first()) {
                return $existing;
            }
            $amounts = app(RefundAmounts::class);
            $amount ??= bcsub((string) $order->paid_amount, $amounts->completed($order), 2);
            $amounts->assertRemaining($order, $amount);

            return Refund::create(['order_id' => $order->id, 'status' => 'pending', 'user_id' => $user->id, 'amount' => $amount, 'reason' => $reason]);
        });
    }

    public function handleRefund(int $id, string $status): void
    {
        DB::transaction(function () use ($id, $status) {
            $refund = Refund::findOrFail($id);
            $order = Order::whereKey($refund->order_id)->lockForUpdate()->firstOrFail();
            $refund = Refund::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($refund->status === $status) {
                return;
            }
            if (! in_array($refund->status, ['pending', 'processing'], true)) {
                throw ValidationException::withMessages(['status' => '售后申请已处理。']);
            }
            if ($status === 'rejected') {
                if ($refund->status === 'processing') {
                    throw ValidationException::withMessages(['refund' => '退款处理中，不能拒绝。']);
                }
                $refund->update(['status' => 'rejected', 'handled_at' => now()]);

                return;
            }
            if (! in_array($order->status, ['paid', 'refund_pending'], true) || (! in_array($order->pay_channel, ['balance', 'points'], true) && data_get($refund->metadata, 'channel_result.status') !== 'refunded')) {
                throw ValidationException::withMessages(['order' => '该订单需要在支付渠道完成退款后对账。']);
            }
            $amounts = app(RefundAmounts::class);
            $amounts->assertRemaining($order, (string) $refund->amount);
            $completed = $amounts->completed($order);
            $fullyRefunded = bccomp(bcadd($completed, (string) $refund->amount, 2), (string) $order->paid_amount, 2) === 0;
            app(OrderParticipantLocks::class)->acquire($order);
            zfy_validate('zfy_refund_completing', $refund, $order);
            DB::table('author_earnings')->where('order_id', $order->id)->where('status', 'pending')->update(['status' => 'reversed', 'updated_at' => now()]);
            foreach (DB::table('author_earnings')->where('order_id', $order->id)->where('status', 'settled')->lockForUpdate()->get() as $earning) {
                $wallet = Wallet::where('user_id', $earning->author_id)->lockForUpdate()->firstOrFail();
                // A clawback may create a debt balance; future earnings offset it and withdrawals stay blocked.
                $balance = bcsub((string) $wallet->balance, (string) $earning->amount, 2);
                $wallet->update(['balance' => $balance]);
                $this->walletEntry($wallet, '-'.$earning->amount, $balance, 'earning_reversal', $order->id, '退款冲回分成');
                DB::table('author_earnings')->where('id', $earning->id)->update(['status' => 'reversed', 'updated_at' => now()]);
            }
            $user = User::findOrFail($order->user_id);
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $split = $amounts->split($order, (string) $refund->amount, $completed);
            if ($order->type === 'vip' && ! data_get($order->meta, 'late_payment')) {
                app(VipEntitlementService::class)->revoke($order, $user);
            }
            if ($order->type === 'recharge' && ! data_get($order->meta, 'late_payment')) {
                $wallet = $user->wallet()->lockForUpdate()->firstOrFail();
                $balance = bcsub((string) $wallet->balance, (string) $refund->amount, 2);
                $wallet->update(['balance' => $balance]);
                $this->walletEntry($wallet, bcsub('0', (string) $refund->amount, 2), $balance, 'recharge_reversal', $order->id, '充值退款冲回');
            }
            if ($order->pay_channel === 'balance') {
                $wallet = $user->wallet()->lockForUpdate()->firstOrFail();
                $cashRefund = $split['cash'];
                $balance = bcadd((string) $wallet->balance, $cashRefund, 2);
                $wallet->update(['balance' => $balance]);
                $this->walletEntry($wallet, $cashRefund, $balance, 'refund', $order->id, '订单退款');
            }
            if ($order->pay_channel === 'points' || (int) data_get($order->meta, 'payment_breakdown.points', 0) > 0) {
                $account = $user->pointsAccount()->lockForUpdate()->firstOrFail();
                $amount = $split['points'];
                $account->increment('points', $amount);
                DB::table('points_transactions')->insert(['points_account_id' => $account->id, 'type' => 'refund', 'points' => $amount, 'balance_after' => $account->fresh()->points, 'remark' => '订单退款', 'meta' => json_encode(['order_no' => $order->order_no]), 'created_at' => now(), 'updated_at' => now()]);
            }
            $refund->update(['status' => 'refunded', 'handled_at' => now(), 'metadata' => [...($refund->metadata ?? []), 'allocation' => $split]]);
            if ($fullyRefunded) {
                app(InventoryService::class)->restockRefund($order, $order->refunds()->whereNotNull('metadata->return->received_at')->exists());
                $order->update(['status' => 'refunded']);
            }
            app(ExtensionOutbox::class)->record('refund.completed:'.$refund->id, 'zfy_refund_completed', ['refund_id' => $refund->id, 'order_id' => $order->id, 'amount' => (string) $refund->amount]);
        });
    }

    public function receiveReturn(int $id, string $reference): void
    {
        if (! filled($reference)) {
            throw ValidationException::withMessages(['reference' => '请填写退货验收凭证。']);
        }
        DB::transaction(function () use ($id, $reference) {
            $refund = Refund::findOrFail($id);
            $order = Order::whereKey($refund->order_id)->lockForUpdate()->firstOrFail();
            $refund = Refund::whereKey($id)->lockForUpdate()->firstOrFail();
            if (data_get($refund->metadata, 'return.received_at')) {
                return;
            }
            if (! in_array($refund->status, ['pending', 'processing', 'refunded'], true) || ! $order->items()->where('meta->type', 'physical')->exists() || ! DB::table('shipments')->where('order_id', $order->id)->whereIn('status', ['shipped', 'received'])->exists()) {
                throw ValidationException::withMessages(['refund' => '该售后没有待验收的实物退货。']);
            }
            $refund->update(['metadata' => [...($refund->metadata ?? []), 'return' => [...(data_get($refund->metadata, 'return', [])), 'received_at' => now()->toIso8601String(), 'reference' => $reference, 'operator_id' => auth()->id()]]]);
            if ($refund->status === 'refunded' && $order->status === 'refunded') {
                app(InventoryService::class)->restockRefund($order, true);
            }
            zfy_after_commit('zfy_return_received', $refund, $order);
        });
    }

    public function submitReturn(User $user, Refund $refund, string $carrier, string $tracking): void
    {
        DB::transaction(function () use ($user, $refund, $carrier, $tracking) {
            $order = Order::whereKey($refund->order_id)->lockForUpdate()->firstOrFail();
            $refund = Refund::whereKey($refund->id)->lockForUpdate()->firstOrFail();
            abort_unless($order->user_id === $user->id, 403);
            if (! in_array($refund->status, ['pending', 'processing', 'refunded'], true) || data_get($refund->metadata, 'return.received_at') || ! $order->items()->where('meta->type', 'physical')->exists() || ! DB::table('shipments')->where('order_id', $order->id)->whereIn('status', ['shipped', 'received'])->exists()) {
                throw ValidationException::withMessages(['refund' => '该订单当前不能提交退货物流。']);
            }
            $refund->update(['metadata' => [...($refund->metadata ?? []), 'return' => ['carrier' => $carrier, 'tracking_no' => $tracking, 'submitted_at' => now()->toIso8601String()]]]);
            zfy_after_commit('zfy_return_submitted', $refund, $order);
        });
    }

    private function walletEntry(Wallet $wallet, string $amount, string $balance, string $type, int $relatedId, string $remark): void
    {
        DB::table('wallet_transactions')->insert(['wallet_id' => $wallet->id, 'type' => $type, 'amount' => $amount, 'balance_after' => $balance, 'related_type' => $type, 'related_id' => $relatedId, 'remark' => $remark, 'created_at' => now(), 'updated_at' => now()]);
    }
}
