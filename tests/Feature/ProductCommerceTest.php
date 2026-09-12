<?php

namespace Tests\Feature;

use App\Models\CardCode;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use App\Services\ProductOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductCommerceTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_failure_rolls_back_payment_and_coupon_usage(): void
    {
        $buyer = User::factory()->create();
        $buyer->wallet()->create(['balance' => 100]);
        $product = Product::create(['title' => 'Limited', 'slug' => 'limited', 'status' => 'published', 'price' => 20, 'stock_strategy' => 'limited']);
        DB::table('stock_items')->insert(['product_id' => $product->id, 'quantity' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $coupon = Coupon::create(['code' => 'SAVE', 'type' => 'fixed', 'amount' => 5, 'usage_limit' => 2]);
        $products = app(ProductOrderService::class);
        $orders = app(OrderService::class);
        $first = $products->create($product, $buyer, ['quantity' => 1, 'gateway' => 'balance', 'coupon' => 'SAVE']);
        try {
            $products->create($product, $buyer, ['quantity' => 1, 'gateway' => 'balance', 'coupon' => 'SAVE']);
            $this->fail('Expected reservation rejection');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('stock', $exception->errors());
        }
        $orders->payWithBalance($first, $buyer);
        $this->assertEquals(85, $buyer->wallet()->first()->balance);
        $this->assertEquals(1, $coupon->fresh()->used_count);
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_card_delivery_is_encrypted_and_idempotent(): void
    {
        $buyer = User::factory()->create();
        $buyer->wallet()->create(['balance' => 100]);
        $product = Product::create(['title' => 'License', 'slug' => 'license', 'type' => 'card', 'status' => 'published', 'price' => 10]);
        $card = CardCode::create(['product_id' => $product->id, 'code_hash' => hash('sha256', 'SECRET-CODE'), 'code_payload' => 'SECRET-CODE']);
        $this->assertNotSame('SECRET-CODE', DB::table('card_codes')->where('id', $card->id)->value('code_payload'));
        $order = app(ProductOrderService::class)->create($product, $buyer, ['quantity' => 1, 'gateway' => 'balance']);
        app(OrderService::class)->payWithBalance($order, $buyer);
        app(OrderService::class)->payWithBalance($order, $buyer);
        $this->assertSame('delivered', $card->fresh()->status);
        $this->assertSame('SECRET-CODE', $card->fresh()->code_payload);
        $this->assertDatabaseCount('wallet_transactions', 1);
    }
}
