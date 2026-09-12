<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\User;
use App\Services\ShipmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_owner_can_receive_shipped_paid_order(): void
    {
        $owner = User::factory()->create();
        $order = Order::create(['user_id' => $owner->id, 'order_no' => 'SHIP-TEST', 'type' => 'product', 'status' => 'paid', 'pay_channel' => 'balance', 'total_amount' => 10, 'paid_amount' => 10]);
        $shipment = Shipment::create(['order_id' => $order->id, 'status' => 'pending']);
        $this->actingAs($owner)->postJson('/orders/SHIP-TEST/receive')->assertUnprocessable();
        app(ShipmentService::class)->update($shipment, ['status' => 'shipped', 'carrier' => 'Test', 'tracking_no' => 'TRACK-1']);
        $this->actingAs(User::factory()->create())->postJson('/orders/SHIP-TEST/receive')->assertForbidden();
        $this->actingAs($owner)->postJson('/orders/SHIP-TEST/receive')->assertOk();
        $received = $shipment->fresh()->received_at;
        $this->postJson('/orders/SHIP-TEST/receive')->assertOk();
        $this->assertTrue($shipment->fresh()->received_at->equalTo($received));
    }
}
