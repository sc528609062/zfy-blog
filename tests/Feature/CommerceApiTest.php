<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommerceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_and_financial_apis_respect_visibility_and_token_abilities(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $product = Product::create(['title' => 'Public', 'slug' => 'public-product', 'status' => 'published', 'price' => 10]);
        $product->variants()->create(['sku' => 'PUB', 'title' => 'Visible', 'status' => 'active', 'price' => 10, 'stock' => 2]);
        $product->variants()->create(['sku' => 'HID', 'title' => 'Hidden', 'status' => 'inactive', 'price' => 12, 'stock' => 2]);
        $this->getJson('/api/v1/products')->assertOk()->assertJsonCount(1, 'data.data.0.variants');
        $user = User::factory()->create();
        $read = $user->createToken('read', ['read'])->plainTextToken;
        $this->withToken($read)->postJson('/api/v1/wallet/recharge', ['amount' => '12.30', 'gateway' => 'epay'])->assertForbidden();
        $token = $user->createToken('orders', ['read', 'orders'])->plainTextToken;
        auth()->forgetGuards();
        $this->withToken($token)->postJson('/api/v1/wallet/recharge', ['amount' => '12.30', 'gateway' => 'epay'])->assertOk()->assertJsonPath('code', 0);
        $order = Order::latest('id')->firstOrFail();
        $this->withToken($token)->postJson('/api/v1/orders/'.$order->order_no.'/cancel')->assertOk();
        $this->assertSame('cancelled', $order->fresh()->status);
        $other = User::factory()->create()->createToken('orders', ['orders'])->plainTextToken;
        auth()->forgetGuards();
        $this->withToken($other)->postJson('/api/v1/orders/'.$order->order_no.'/cancel')->assertForbidden();
    }
}
