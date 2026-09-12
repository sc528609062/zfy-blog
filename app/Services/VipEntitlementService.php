<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\VipLevel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VipEntitlementService
{
    public const PERIODS = ['monthly' => 1, 'quarterly' => 3, 'yearly' => 12, 'lifetime' => 0];

    public function quote(VipLevel $level, User $user, string $period): array
    {
        if (! array_key_exists($period, self::PERIODS) || ($period === 'lifetime' && $level->price_lifetime === null)) {
            throw ValidationException::withMessages(['period' => '该会员周期不可购买。']);
        }
        $current = $user->vip()->with('level')->first();
        if ($current?->isActive() && ($current->level?->level > $level->level || ($current->expires_at === null && $current->vip_level_id === $level->id))) {
            throw ValidationException::withMessages(['level' => '当前会员已包含该套餐权益。']);
        }
        if ($current?->isActive() && $current->expires_at === null && $period !== 'lifetime') {
            throw ValidationException::withMessages(['period' => '永久会员升级请选择永久周期。']);
        }
        $price = match ($period) {
            'monthly' => $level->price_monthly,
            'quarterly' => bcmul((string) $level->price_monthly, '3', 2),
            'yearly' => $level->price_yearly,
            'lifetime' => $level->price_lifetime,
        };

        return ['level_id' => $level->id, 'name' => $level->name, 'rank' => $level->level, 'annual_price' => (string) $level->price_yearly, 'price' => (string) $price, 'period' => $period, 'months' => self::PERIODS[$period], 'benefits' => $level->benefits, 'discount_percent' => $level->discount_percent, 'fixed_discount' => (string) $level->fixed_discount];
    }

    public function grant(Order $order, User $user): void
    {
        if (DB::table('vip_entitlements')->where('order_id', $order->id)->exists()) {
            return;
        }
        $snapshot = data_get($order->meta, 'vip_snapshot');
        if (! $snapshot) {
            $level = VipLevel::where('slug', data_get($order->meta, 'vip_level'))->firstOrFail();
            $snapshot = ['level_id' => $level->id, 'name' => $level->name, 'rank' => $level->level, 'annual_price' => (string) $level->price_yearly, 'months' => self::PERIODS[data_get($order->meta, 'period', 'yearly')]];
        }
        $current = $user->vip()->with('level')->first();
        $baseline = $current ? ['level_id' => $current->vip_level_id, 'rank' => $current->level?->level ?? 0, 'annual_price' => (string) ($current->level?->price_yearly ?? 0), 'expires_at' => $current->expires_at?->toISOString(), 'started_at' => $current->started_at?->toISOString()] : null;
        DB::table('vip_entitlements')->insert(['user_id' => $user->id, 'order_id' => $order->id, 'snapshot' => json_encode($snapshot), 'baseline' => json_encode($baseline), 'applied_at' => $order->paid_at ?? now(), 'created_at' => now(), 'updated_at' => now()]);
        $this->rebuild($user);
    }

    public function revoke(Order $order, User $user): void
    {
        DB::table('vip_entitlements')->where('order_id', $order->id)->whereNull('revoked_at')->update(['revoked_at' => now(), 'updated_at' => now()]);
        $this->rebuild($user);
    }

    private function rebuild(User $user): void
    {
        // Replay purchase snapshots so refunding an older renewal preserves subsequent purchases.
        $grants = DB::table('vip_entitlements')->where('user_id', $user->id)->orderBy('applied_at')->orderBy('id')->get();
        if ($grants->isEmpty()) {
            return;
        }
        $state = json_decode($grants->first()->baseline ?? 'null', true);
        foreach ($grants as $grant) {
            if ($grant->revoked_at) {
                continue;
            }
            $plan = json_decode($grant->snapshot, true);
            $at = Carbon::parse($grant->applied_at);
            $expiry = isset($state['expires_at']) ? Carbon::parse($state['expires_at']) : null;
            $active = $state && ($expiry === null || $expiry->greaterThan($at));
            if ($active && $expiry === null && $state['rank'] >= $plan['rank']) {
                continue;
            }
            if ((int) $plan['months'] === 0) {
                $state = [...$plan, 'expires_at' => null, 'started_at' => $at->toISOString()];

                continue;
            }
            $seconds = $at->diffInSeconds($at->copy()->addMonthsNoOverflow($plan['months']));
            $remaining = $active && $expiry ? max(0, (int) $at->diffInSeconds($expiry)) : 0;
            if ($active && $state['rank'] > $plan['rank']) {
                $seconds = $this->convert((int) $seconds, $plan['annual_price'], $state['annual_price']);
                $plan = $state;
            } elseif ($active && $state['level_id'] !== $plan['level_id']) {
                $remaining = $this->convert($remaining, $state['annual_price'], $plan['annual_price']);
            }
            $state = [...$plan, 'expires_at' => $at->copy()->addSeconds($remaining + (int) $seconds)->toISOString(), 'started_at' => $at->toISOString()];
        }
        if (! $state) {
            $user->vip()->delete();

            return;
        }
        $user->vip()->updateOrCreate([], ['vip_level_id' => $state['level_id'], 'started_at' => $state['started_at'] ?? now(), 'expires_at' => $state['expires_at'], 'meta' => ['source' => 'entitlements', 'annual_price' => $state['annual_price'], 'snapshot' => $state]]);
    }

    private function convert(int $seconds, string $from, string $to): int
    {
        if (bccomp($from, '0', 2) <= 0 || bccomp($to, '0', 2) <= 0) {
            return 0;
        }

        return (int) bcdiv(bcmul((string) $seconds, $from, 2), $to, 0);
    }
}
