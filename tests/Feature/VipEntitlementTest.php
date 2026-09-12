<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\User;
use App\Models\VipLevel;
use App\Services\CommerceOperations;
use App\Services\OrderService;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VipEntitlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_refunding_earlier_renewal_preserves_later_purchase(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $this->travelTo(now()->startOfDay());
        $user = User::factory()->create();
        $user->wallet()->create(['balance' => 1000]);
        $level = VipLevel::first();
        $orders = app(OrderService::class);
        $first = $orders->payWithBalance($orders->createForVip($level, $user, 'monthly', 'balance'), $user);
        $this->travel(10)->days();
        $second = $orders->payWithBalance($orders->createForVip($level, $user, 'monthly', 'balance'), $user);
        $operations = app(CommerceOperations::class);
        $refund = $operations->requestRefund($user, $first, 'Refund earlier renewal');
        $operations->handleRefund($refund->id, 'refunded');
        $operations->handleRefund($refund->id, 'refunded');
        $this->assertTrue($user->vip()->first()->expires_at->equalTo($second->paid_at->copy()->addMonthNoOverflow()));
        $this->assertSame('paid', $second->fresh()->status);
        $this->assertDatabaseCount('vip_entitlements', 2);
    }

    public function test_lifetime_membership_grants_access_and_refund_revokes_it(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $user = User::factory()->create();
        $user->wallet()->create(['balance' => 1000]);
        $level = VipLevel::first();
        $level->update(['price_lifetime' => '499.99']);
        $content = Content::create(['type' => 'post', 'title' => 'VIP', 'slug' => 'vip-access', 'status' => 'published', 'pricing' => ['price' => 20], 'access_rules' => ['vip_free' => true]]);
        $orders = app(OrderService::class);
        $order = $orders->payWithBalance($orders->createForVip($level, $user, 'lifetime', 'balance'), $user);
        $this->assertNull($user->vip()->first()->expires_at);
        $this->assertTrue($orders->userCanAccessContent($content, $user));
        $refund = app(CommerceOperations::class)->requestRefund($user, $order, 'Refund');
        app(CommerceOperations::class)->handleRefund($refund->id, 'refunded');
        $this->assertFalse($orders->userCanAccessContent($content, $user));
        $this->assertSame('1000.00', $user->wallet()->first()->balance);
    }

    public function test_upgrade_uses_purchase_snapshot_when_prices_change(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $this->travelTo(now()->startOfDay());
        $user = User::factory()->create();
        $user->wallet()->create(['balance' => 1000]);
        $basic = VipLevel::orderBy('level')->first();
        $higher = VipLevel::orderByDesc('level')->first();
        $orders = app(OrderService::class);
        $orders->payWithBalance($orders->createForVip($basic, $user, 'yearly', 'balance'), $user);
        $oldExpiry = $user->vip()->first()->expires_at;
        $upgrade = $orders->createForVip($higher, $user, 'monthly', 'balance');
        $higher->update(['price_yearly' => '9999.99']);
        $orders->payWithBalance($upgrade, $user);
        $remaining = (int) bcdiv(bcmul((string) now()->diffInSeconds($oldExpiry), (string) $basic->price_yearly, 2), data_get($upgrade->meta, 'vip_snapshot.annual_price'), 0);
        $expected = now()->addMonthNoOverflow()->addSeconds($remaining);
        $this->assertTrue($user->vip()->first()->expires_at->equalTo($expected));
    }
}
