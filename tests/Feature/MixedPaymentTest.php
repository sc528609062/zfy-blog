<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Product;
use App\Models\User;
use App\Services\CommerceOperations;
use App\Services\OrderService;
use App\Services\ProductOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MixedPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_partial_refunds_return_only_the_proportional_tenders_and_restock_on_full_refund(): void
    {
        $buyer = User::factory()->create();
        $buyer->wallet()->create(['balance' => 100]);
        $buyer->pointsAccount()->create(['points' => 500]);
        $product = Product::create(['title' => 'Partial', 'slug' => 'partial', 'type' => 'physical', 'status' => 'published', 'price' => '12.30', 'stock_strategy' => 'limited']);
        DB::table('stock_items')->insert(['product_id' => $product->id, 'quantity' => 2, 'created_at' => now(), 'updated_at' => now()]);
        $order = app(ProductOrderService::class)->create($product, $buyer, ['quantity' => 1, 'gateway' => 'balance', 'address' => 'Test address']);
        app(OrderService::class)->payWithBalance($order, $buyer, 50);
        $operations = app(CommerceOperations::class);
        $first = $operations->requestRefund($buyer, $order, 'Partial', '3.00');
        $operations->handleRefund($first->id, 'refunded');
        $operations->handleRefund($first->id, 'refunded');
        $this->assertSame('paid', $order->fresh()->status);
        $this->assertEquals('94.48', $buyer->wallet->fresh()->balance);
        $this->assertEquals(462, $buyer->pointsAccount->fresh()->points);
        $this->assertEquals(1, DB::table('stock_items')->value('quantity'));
        try {
            $operations->requestRefund($buyer, $order, 'Too much', '10.00');
            $this->fail('Over-refund accepted');
        } catch (ValidationException) {
            $this->assertDatabaseCount('refunds', 1);
        }
        $second = $operations->requestRefund($buyer, $order, 'Remaining');
        $this->assertSame('9.30', $second->amount);
        $operations->handleRefund($second->id, 'refunded');
        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertEquals('100.00', $buyer->wallet->fresh()->balance);
        $this->assertEquals(500, $buyer->pointsAccount->fresh()->points);
        $this->assertEquals(2, DB::table('stock_items')->value('quantity'));
    }

    public function test_mixed_payment_and_refund_are_idempotent_and_commission_uses_cash_only(): void
    {
        $author = User::factory()->create(['is_author' => true, 'author_status' => 'approved']);
        $buyer = User::factory()->create();
        $buyer->wallet()->create(['balance' => 100]);
        $buyer->pointsAccount()->create(['points' => 500]);
        $content = Content::create(['author_id' => $author->id, 'title' => 'Mixed', 'slug' => 'mixed', 'type' => 'post', 'status' => 'published', 'pricing' => ['price' => '12.30']]);
        $service = app(OrderService::class);
        $order = $service->createForContent($content, $buyer, 'balance');
        $paid = $service->payWithBalance($order, $buyer, 50);
        $service->payWithBalance($order, $buyer, 50);
        $this->assertSame('7.30', data_get($paid->meta, 'payment_breakdown.balance'));
        $this->assertEquals('92.70', $buyer->wallet->fresh()->balance);
        $this->assertEquals(450, $buyer->pointsAccount->fresh()->points);
        $this->assertEquals('3.65', DB::table('author_earnings')->where('order_id', $order->id)->value('amount'));
        $operations = app(CommerceOperations::class);
        $refund = $operations->requestRefund($buyer, $paid, 'Mixed refund');
        $operations->handleRefund($refund->id, 'refunded');
        $operations->handleRefund($refund->id, 'refunded');
        $this->assertEquals('100.00', $buyer->wallet->fresh()->balance);
        $this->assertEquals(500, $buyer->pointsAccount->fresh()->points);
        $this->assertDatabaseCount('points_transactions', 2);
        $this->assertEquals('0.00', $author->wallet->fresh()->balance);
    }

    public function test_insufficient_balance_rolls_back_points_debit(): void
    {
        $buyer = User::factory()->create();
        $buyer->wallet()->create(['balance' => 0]);
        $buyer->pointsAccount()->create(['points' => 50]);
        $content = Content::create(['title' => 'Paid', 'slug' => 'paid', 'type' => 'post', 'status' => 'published', 'pricing' => ['price' => '12.30']]);
        $service = app(OrderService::class);
        $order = $service->createForContent($content, $buyer, 'balance');
        try {
            $service->payWithBalance($order, $buyer, 50);
            $this->fail('Insufficient balance should fail');
        } catch (ValidationException) {
            $this->assertEquals(50, $buyer->pointsAccount->fresh()->points);
            $this->assertSame('pending', $order->fresh()->status);
            $this->assertDatabaseCount('points_transactions', 0);
        }
    }
}
