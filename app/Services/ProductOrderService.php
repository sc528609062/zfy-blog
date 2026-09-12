<?php

namespace App\Services;

use App\Models\CardCode;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductOrderService
{
    public function create(Product $product, User $user, array $data): Order
    {
        return $this->createMany([['product_id' => $product->id, 'variant_id' => $data['variant_id'] ?? null, 'quantity' => $data['quantity']]], $user, $data);
    }

    public function createMany(array $lines, User $user, array $data): Order
    {
        return DB::transaction(function () use ($lines, $user, $data) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($lines === [] || count($lines) > 100) {
                throw ValidationException::withMessages(['cart' => '购物车为空或商品过多。']);
            }
            usort($lines, fn ($left, $right) => [$left['product_id'], $left['variant_id'] ?? 0] <=> [$right['product_id'], $right['variant_id'] ?? 0]);
            $amount = '0.00';
            $shipping = '0.00';
            $items = [];
            foreach ($lines as $line) {
                $quantity = filter_var($line['quantity'], FILTER_VALIDATE_INT);
                if (! $quantity || $quantity < 1 || $quantity > 100) {
                    throw ValidationException::withMessages(['quantity' => '数量必须介于 1 和 100。']);
                }
                $product = Product::whereKey($line['product_id'])->where('status', 'published')->lockForUpdate()->firstOrFail();
                $variant = ! empty($line['variant_id']) ? $product->variants()->whereKey($line['variant_id'])->where('status', 'active')->lockForUpdate()->firstOrFail() : null;
                if (! $variant && $product->variants()->where('status', 'active')->exists()) {
                    throw ValidationException::withMessages(['variant_id' => '请选择商品规格。']);
                }
                $price = $variant?->price ?? $product->sale_price ?? $product->price;
                $amount = bcadd($amount, bcmul((string) $price, (string) $quantity, 2), 2);
                if ($product->type === 'physical') {
                    if (! filled($data['address'] ?? null)) {
                        throw ValidationException::withMessages(['address' => '请选择收货地址。']);
                    }
                }
                $items[] = compact('product', 'variant', 'price', 'quantity');
            }
            $subtotal = $amount;
            $shipping = app(ShippingCalculator::class)->calculate($items, $data['region'] ?? null);
            $discount = '0.00';
            $coupon = null;
            if (filled($data['coupon'] ?? null)) {
                $coupon = Coupon::where('code', $data['coupon'])->lockForUpdate()->first();
                $this->validateCoupon($coupon);
                $discount = $this->couponDiscount($coupon, $amount);
                $amount = bcsub($amount, (string) $discount, 2);
            }
            $amount = bcadd($amount, $shipping, 2);
            $order = Order::create([
                'user_id' => $user->id, 'order_no' => 'ZF'.Str::ulid(), 'type' => 'product', 'status' => 'pending',
                'pay_channel' => $data['gateway'], 'total_amount' => $amount, 'paid_amount' => 0, 'expires_at' => now()->addMinutes(30),
                'buyer_snapshot' => ['name' => $user->name, 'address' => $data['address'] ?? null],
                'meta' => ['coupon_id' => $coupon?->id, 'coupon_reserved' => (bool) $coupon, 'subtotal' => $subtotal, 'discount' => $discount, 'shipping_fee' => $shipping],
            ]);
            foreach ($items as $line) {
                ['product' => $product, 'variant' => $variant, 'quantity' => $quantity, 'price' => $price] = $line;
                $item = $order->items()->create(['item_type' => 'product', 'item_id' => $product->id, 'title' => $product->title, 'quantity' => $quantity, 'unit_price' => $price, 'meta' => ['type' => $product->type, 'content_id' => $product->content_id, 'variant_id' => $variant?->id, 'variant_title' => $variant?->title]]);
                app(InventoryService::class)->reserve($order, $item, $product, $variant);
            }
            if ($coupon) {
                DB::table('coupon_reservations')->insert(['coupon_id' => $coupon->id, 'order_id' => $order->id, 'status' => 'reserved', 'created_at' => now(), 'updated_at' => now()]);
            }
            zfy_after_commit('zfy_order_created', $order);

            return $order;
        });
    }

    public function fulfill(Order $order): void
    {
        if ($order->type !== 'product') {
            return;
        }
        Product::withTrashed()->whereIn('id', $order->items()->where('item_type', 'product')->pluck('item_id'))->orderBy('id')->lockForUpdate()->get();
        $reserved = app(InventoryService::class)->consume($order);
        if ($couponId = data_get($order->meta, 'coupon_id')) {
            $coupon = Coupon::whereKey($couponId)->lockForUpdate()->first();
            $reservation = DB::table('coupon_reservations')->where('order_id', $order->id)->lockForUpdate()->first();
            if ($reservation) {
                if ($reservation->status === 'reserved') {
                    $coupon->increment('used_count');
                    DB::table('coupon_reservations')->where('id', $reservation->id)->update(['status' => 'consumed', 'updated_at' => now()]);
                } elseif ($reservation->status !== 'consumed') {
                    throw ValidationException::withMessages(['coupon' => '优惠券预留已释放。']);
                }
            } else {
                $this->validateCoupon($coupon);
                $coupon->increment('used_count');
            }
        }
        foreach ($order->items as $item) {
            $product = Product::withTrashed()->whereKey($item->item_id)->lockForUpdate()->first();
            if (! $product) {
                throw ValidationException::withMessages(['product' => '商品已下架。']);
            }
            if (! $reserved && $product->stock_strategy === 'limited') {
                $stock = DB::table('stock_items')->where('product_id', $product->id)->whereNull('product_variant_id')->lockForUpdate()->first();
                if (! $stock || $stock->quantity < $item->quantity) {
                    throw ValidationException::withMessages(['stock' => '商品库存不足。']);
                }
                DB::table('stock_items')->where('id', $stock->id)->decrement('quantity', $item->quantity);
            }
            if (! $reserved && $product->type === 'card') {
                $cards = CardCode::where('product_id', $product->id)->where('status', 'available')->lockForUpdate()->limit($item->quantity)->get();
                if ($cards->count() !== $item->quantity) {
                    throw ValidationException::withMessages(['stock' => '可用卡密不足。']);
                }
                foreach ($cards as $card) {
                    $card->update(['status' => 'delivered', 'order_item_id' => $item->id, 'delivered_at' => now()]);
                }
            } elseif (data_get($item->meta, 'type', $product->type) === 'physical') {
                Shipment::firstOrCreate(['order_id' => $order->id], ['status' => 'pending', 'address_snapshot' => ['address' => data_get($order->buyer_snapshot, 'address')]]);
            }
        }
    }

    private function validateCoupon(?Coupon $coupon): void
    {
        $reserved = $coupon ? DB::table('coupon_reservations')->where('coupon_id', $coupon->id)->where('status', 'reserved')->count() : 0;
        if (! $coupon || $coupon->status !== 'active' || $coupon->starts_at?->isFuture() || $coupon->expires_at?->isPast() || ($coupon->usage_limit !== null && $coupon->used_count + $reserved >= $coupon->usage_limit)) {
            throw ValidationException::withMessages(['coupon' => '优惠码无效、已过期或已用完。']);
        }
    }

    public function couponDiscount(?Coupon $coupon, string $amount): string
    {
        $this->validateCoupon($coupon);
        $discount = $coupon->type === 'percent' ? bcmul($amount, bcdiv((string) $coupon->amount, '100', 4), 2) : (string) $coupon->amount;

        return bccomp($discount, $amount, 2) > 0 ? $amount : $discount;
    }
}
