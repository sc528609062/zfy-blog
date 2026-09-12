<?php

namespace Tests\Feature;

use App\Models\CardCode;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Shipment;
use App\Models\User;
use App\Services\CommerceOperations;
use App\Services\OrderService;
use App\Services\ProductOrderService;
use App\Services\ShipmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RefundInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_shipped_inventory_requires_received_return_and_is_restocked_once(): void
    {
        $buyer = User::factory()->create();
        $buyer->wallet()->create(['balance' => 100]);
        $product = Product::create(['title' => 'Book', 'slug' => 'book', 'type' => 'physical', 'status' => 'published', 'price' => 10, 'stock_strategy' => 'limited']);
        DB::table('stock_items')->insert(['product_id' => $product->id, 'quantity' => 2, 'created_at' => now(), 'updated_at' => now()]);
        $order = app(ProductOrderService::class)->create($product, $buyer, ['quantity' => 1, 'gateway' => 'balance', 'address' => ['name' => 'Buyer', 'phone' => '13800000000', 'province' => '北京', 'city' => '北京', 'district' => '海淀', 'address' => '测试街道']]);
        app(OrderService::class)->payWithBalance($order, $buyer);
        app(ShipmentService::class)->update(Shipment::where('order_id', $order->id)->firstOrFail(), ['status' => 'shipped', 'carrier' => 'Test', 'tracking_no' => 'TEST-1']);
        $operations = app(CommerceOperations::class);
        $refund = $operations->requestRefund($buyer, $order, 'Return');
        $this->actingAs(User::factory()->create())->postJson('/refunds/'.$refund->id.'/return', ['carrier' => 'Test', 'tracking_no' => 'RETURN-1'])->assertForbidden();
        $this->actingAs($buyer)->postJson('/refunds/'.$refund->id.'/return', ['carrier' => 'Test', 'tracking_no' => 'RETURN-1'])->assertOk();
        $operations->handleRefund($refund->id, 'refunded');
        $this->assertEquals(1, DB::table('stock_items')->value('quantity'));
        $operations->receiveReturn($refund->id, 'RETURN-1 inspected');
        $operations->receiveReturn($refund->id, 'RETURN-1 inspected');
        $this->assertSame('RETURN-1', data_get($refund->fresh()->metadata, 'return.tracking_no'));
        $this->actingAs($buyer)->postJson('/refunds/'.$refund->id.'/return', ['carrier' => 'Test', 'tracking_no' => 'CHANGED'])->assertUnprocessable();
        $this->assertEquals(2, DB::table('stock_items')->value('quantity'));
        $this->assertDatabaseHas('inventory_reservations', ['order_id' => $order->id, 'status' => 'restocked']);
    }

    public function test_refunded_card_is_never_reissued(): void
    {
        $buyer = User::factory()->create();
        $buyer->wallet()->create(['balance' => 100]);
        $product = Product::create(['title' => 'License', 'slug' => 'license', 'type' => 'card', 'status' => 'published', 'price' => 10, 'stock_strategy' => 'limited']);
        DB::table('stock_items')->insert(['product_id' => $product->id, 'quantity' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $card = CardCode::create(['product_id' => $product->id, 'code_hash' => hash('sha256', 'test-code'), 'code_payload' => 'test-code']);
        $order = app(ProductOrderService::class)->create($product, $buyer, ['quantity' => 1, 'gateway' => 'balance']);
        app(OrderService::class)->payWithBalance($order, $buyer);
        $refund = Refund::create(['order_id' => $order->id, 'user_id' => $buyer->id, 'status' => 'pending', 'amount' => 10, 'reason' => 'Approved support case']);
        app(CommerceOperations::class)->handleRefund($refund->id, 'refunded');
        $this->assertEquals(0, DB::table('stock_items')->value('quantity'));
        $this->assertSame('delivered', $card->fresh()->status);
    }
}
