<?php

namespace Tests\Feature;

use App\Models\AuthorSettlementRule;
use App\Models\Content;
use App\Models\User;
use App\Services\CommerceOperations;
use App\Services\OrderService;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_refund_reverses_author_earning_and_returns_balance_once(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $author = User::factory()->create(['is_author' => true, 'author_status' => 'approved']);
        $buyer = User::factory()->create();
        $buyer->wallet()->create(['balance' => 100]);
        $content = Content::create(['author_id' => $author->id, 'title' => 'Paid', 'slug' => 'paid', 'type' => 'post', 'status' => 'published', 'pricing' => ['price' => 20]]);
        $orders = app(OrderService::class);
        $order = $orders->payWithBalance($orders->createForContent($content, $buyer, 'balance'), $buyer);
        $operations = app(CommerceOperations::class);
        $this->assertEquals(10, $author->wallet()->first()->balance);
        $refund = $operations->requestRefund($buyer, $order, 'Refund request');
        $operations->handleRefund($refund->id, 'refunded');
        $operations->handleRefund($refund->id, 'refunded');
        $this->assertEquals(100, $buyer->wallet()->first()->balance);
        $this->assertEquals(0, $author->wallet()->first()->balance);
        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertFalse($orders->userCanAccessContent($content, $buyer));
    }

    public function test_rejected_withdrawal_restores_frozen_balance_once(): void
    {
        $user = User::factory()->create();
        $user->wallet()->create(['balance' => 100]);
        $operations = app(CommerceOperations::class);
        $id = $operations->requestWithdrawal($user, '30.00', 'alipay', 'account');
        $this->assertEquals(70, $user->wallet()->first()->balance);
        $this->assertEquals(30, $user->wallet()->first()->frozen_balance);
        $operations->handleWithdrawal($id, 'rejected');
        $operations->handleWithdrawal($id, 'rejected');
        $this->assertEquals(100, $user->wallet()->first()->balance);
        $this->assertEquals(0, $user->wallet()->first()->frozen_balance);
    }

    public function test_frozen_earnings_use_checkout_rule_snapshot_and_refunds_cancel_before_settlement(): void
    {
        $author = User::factory()->create(['is_author' => true, 'author_status' => 'approved']);
        $buyer = User::factory()->create();
        $buyer->wallet()->create(['balance' => 100]);
        $content = Content::create(['author_id' => $author->id, 'title' => 'Frozen', 'slug' => 'frozen', 'type' => 'post', 'status' => 'published', 'pricing' => ['price' => 20]]);
        $rule = AuthorSettlementRule::create(['name' => 'Content rule', 'scope' => 'content', 'target_id' => $content->id, 'share_percent' => 30, 'hold_days' => 7]);
        $orders = app(OrderService::class);
        $order = $orders->createForContent($content, $buyer, 'balance');
        $rule->update(['share_percent' => 90, 'hold_days' => 0]);
        $order = $orders->payWithBalance($order, $buyer);
        $this->assertDatabaseHas('author_earnings', ['order_id' => $order->id, 'amount' => 6, 'status' => 'pending']);
        $this->assertEquals(0, $author->wallet()->first()->balance);
        $operations = app(CommerceOperations::class);
        $this->assertSame(0, $operations->settleDueEarnings());
        $this->travel(8)->days();
        $this->assertSame(1, $operations->settleDueEarnings());
        $this->assertSame(0, $operations->settleDueEarnings());
        $this->assertEquals(6, $author->wallet()->first()->balance);
        $rule->update(['hold_days' => 7]);
        $second = $orders->payWithBalance($orders->createForContent($content, $buyer, 'balance'), $buyer);
        $refund = $operations->requestRefund($buyer, $second, 'Return');
        $operations->handleRefund($refund->id, 'refunded');
        $this->travel(8)->days();
        $this->assertSame(0, $operations->settleDueEarnings());
        $this->assertEquals(6, $author->wallet()->first()->balance);
        $this->assertDatabaseHas('author_earnings', ['order_id' => $second->id, 'status' => 'reversed']);
    }
}
