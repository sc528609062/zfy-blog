<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ShippingTemplate;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\ProductOrderService;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CartCheckoutTest extends TestCase
{
    public function test_region_quote_matches_order_and_excluded_region_is_rejected(): void
    {
        $user = User::factory()->create();
        $template = ShippingTemplate::create(['name' => 'Regions', 'base_fee' => '3', 'base_quantity' => 1, 'additional_fee' => '0', 'regions' => [['prefix' => 'Remote', 'base_fee' => '8.50'], ['prefix' => 'Excluded', 'excluded' => true]]]);
        $product = Product::create(['title' => 'Parcel', 'slug' => 'regions-product', 'type' => 'physical', 'status' => 'published', 'price' => '10.00', 'stock_strategy' => 'unlimited', 'metadata' => ['shipping_template_id' => $template->id]]);
        app(CartService::class)->add($user, $product, 1, null);
        $address = DB::table('user_addresses')->insertGetId(['user_id' => $user->id, 'recipient' => 'Test', 'phone' => '123', 'region' => 'Remote City', 'address' => '1', 'created_at' => now(), 'updated_at' => now()]);
        $quote = app(CartService::class)->quote($user, null, $address);
        $this->assertSame('8.50', $quote['shipping_fee']);
        $order = app(CartService::class)->checkout($user, ['request_key' => (string) Str::uuid(), 'gateway' => 'balance', 'address_id' => $address]);
        $this->assertSame($quote['total'], $order->total_amount);
        app(CartService::class)->add($user, $product, 1, null);
        DB::table('user_addresses')->where('id', $address)->update(['region' => 'Excluded City']);
        $this->expectException(ValidationException::class);
        app(CartService::class)->checkout($user, ['request_key' => (string) Str::uuid(), 'gateway' => 'balance', 'address_id' => $address]);
    }

    use RefreshDatabase;

    public function test_checkout_is_idempotent_and_late_payment_uses_reserved_stock_and_price(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $user = User::factory()->create();
        $user->wallet()->create(['balance' => '100.00']);
        $product = Product::create(['title' => 'Shipped item', 'slug' => 'physical', 'type' => 'physical', 'status' => 'published', 'price' => '10.20', 'stock_strategy' => 'limited', 'metadata' => ['shipping_fee' => '2.50']]);
        DB::table('stock_items')->insert(['product_id' => $product->id, 'quantity' => 2, 'reserved' => 0, 'type' => 'inventory', 'created_at' => now(), 'updated_at' => now()]);
        $address = DB::table('user_addresses')->insertGetId(['user_id' => $user->id, 'recipient' => 'Example', 'phone' => '123456', 'region' => 'Example region', 'address' => 'Address 1', 'created_at' => now(), 'updated_at' => now()]);
        $cart = app(CartService::class);
        $cart->add($user, $product, 2, null);
        $data = ['request_key' => (string) Str::uuid(), 'gateway' => 'balance', 'address_id' => $address];
        $order = $cart->checkout($user, $data);
        $this->assertEquals('22.90', $order->total_amount);
        $this->assertSame($order->id, $cart->checkout($user, $data)->id);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('cart_items', 0);
        $product->update(['price' => '99.00', 'status' => 'archived']);
        app(OrderService::class)->payWithBalance($order, $user);
        $this->assertSame('77.10', $user->wallet->fresh()->balance);
        $this->assertDatabaseHas('stock_items', ['product_id' => $product->id, 'quantity' => 0, 'reserved' => 0]);
        $this->assertDatabaseHas('shipments', ['order_id' => $order->id, 'status' => 'pending']);
    }

    public function test_address_ownership_is_checked_before_checkout(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $user = User::factory()->create();
        $other = User::factory()->create();
        $id = DB::table('user_addresses')->insertGetId(['user_id' => $other->id, 'recipient' => 'Private', 'phone' => '123456', 'region' => 'Private', 'address' => 'Private', 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($user)->postJson('/cart/checkout', ['request_key' => (string) Str::uuid(), 'gateway' => 'balance', 'address_id' => $id])->assertNotFound();
        $this->patchJson('/user/addresses/'.$id, ['recipient' => 'Change', 'phone' => '123456', 'region' => 'Other', 'address' => 'Other'])->assertNotFound();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_shipping_fee_is_charged_once_for_multiple_variants_of_one_product(): void
    {
        $user = User::factory()->create();
        $product = Product::create(['title' => 'Shipped item', 'slug' => 'variants', 'type' => 'physical', 'status' => 'published', 'price' => '10.00', 'stock_strategy' => 'unlimited', 'metadata' => ['shipping_fee' => '2.50']]);
        $first = $product->variants()->create(['sku' => 'first', 'title' => 'First', 'price' => '10.00', 'stock' => 10, 'status' => 'active']);
        $second = $product->variants()->create(['sku' => 'second', 'title' => 'Second', 'price' => '12.00', 'stock' => 10, 'status' => 'active']);
        $order = app(ProductOrderService::class)->createMany([
            ['product_id' => $product->id, 'variant_id' => $first->id, 'quantity' => 1],
            ['product_id' => $product->id, 'variant_id' => $second->id, 'quantity' => 1],
        ], $user, ['gateway' => 'balance', 'address' => 'Example']);
        $this->assertSame('24.50', $order->total_amount);
        $this->assertSame('2.50', $order->meta['shipping_fee']);
    }

    public function test_quote_uses_shared_shipping_template_and_matches_checkout_without_reserving_inventory(): void
    {
        $user = User::factory()->create();
        $template = ShippingTemplate::create(['name' => 'Parcel', 'base_fee' => '4.00', 'base_quantity' => 2, 'additional_fee' => '1.50', 'free_threshold' => '100.00']);
        $cart = app(CartService::class);
        foreach (['first', 'second'] as $slug) {
            $product = Product::create(['title' => $slug, 'slug' => $slug, 'type' => 'physical', 'status' => 'published', 'price' => '10.00', 'stock_strategy' => 'unlimited', 'metadata' => ['shipping_template_id' => $template->id]]);
            $cart->add($user, $product, 2, null);
        }
        $quote = $cart->quote($user);
        $this->assertSame('7.00', $quote['shipping_fee']);
        $this->assertSame('47.00', $quote['total']);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('inventory_reservations', 0);
        $order = $cart->checkout($user, ['gateway' => 'balance', 'address' => 'Test address', 'request_key' => (string) Str::uuid()]);
        $this->assertSame($quote['total'], $order->total_amount);
    }
}
