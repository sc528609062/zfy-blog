<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function quote(User $user, ?string $couponCode = null, ?int $addressId = null): array
    {
        $items = [];
        $subtotal = '0.00';
        foreach (DB::table('cart_items')->where('user_id', $user->id)->get() as $line) {
            $product = Product::whereKey($line->product_id)->where('status', 'published')->firstOrFail();
            $variant = $line->variant_id ? $product->variants()->whereKey($line->variant_id)->where('status', 'active')->firstOrFail() : null;
            $price = $variant?->price ?? $product->sale_price ?? $product->price;
            $quantity = (int) $line->quantity;
            $subtotal = bcadd($subtotal, bcmul((string) $price, (string) $quantity, 2), 2);
            $items[] = compact('product', 'variant', 'price', 'quantity');
        }
        $region = null;
        if ($addressId) {
            $address = DB::table('user_addresses')->where('user_id', $user->id)->where('id', $addressId)->first();
            abort_unless($address, 404);
            $region = $address->region;
        }
        $shipping = app(ShippingCalculator::class)->calculate($items, $region);
        $discount = filled($couponCode) ? app(ProductOrderService::class)->couponDiscount(Coupon::where('code', $couponCode)->first(), $subtotal) : '0.00';

        return ['subtotal' => $subtotal, 'shipping_fee' => $shipping, 'discount' => $discount, 'total' => bcadd(bcsub($subtotal, $discount, 2), $shipping, 2)];
    }

    public function items(User $user): array
    {
        $items = DB::table('cart_items')->where('user_id', $user->id)->orderBy('id')->get();
        $products = Product::with('variants')->whereIn('id', $items->pluck('product_id'))->get()->keyBy('id');

        return $items->map(function ($item) use ($products) {
            $product = $products[$item->product_id] ?? null;
            $variant = $product?->variants->firstWhere('id', $item->variant_id);
            $price = $variant?->price ?? $product?->sale_price ?? $product?->price ?? '0.00';

            return [...(array) $item, 'title' => $product?->title ?? '商品已删除', 'variant' => $variant?->title, 'price' => $price, 'subtotal' => bcmul((string) $price, (string) $item->quantity, 2), 'available' => $product?->status === 'published' && (! $item->variant_id || $variant?->status === 'active')];
        })->all();
    }

    public function add(User $user, Product $product, int $quantity, ?int $variantId): void
    {
        DB::transaction(function () use ($user, $product, $quantity, $variantId) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_unless($product->status === 'published', 404);
            if ($quantity < 1 || $quantity > 100) {
                throw ValidationException::withMessages(['quantity' => '数量必须介于 1 和 100。']);
            }
            if ($variantId) {
                $product->variants()->whereKey($variantId)->where('status', 'active')->firstOrFail();
            } elseif ($product->variants()->where('status', 'active')->exists()) {
                throw ValidationException::withMessages(['variant_id' => '请选择商品规格。']);
            }
            $identity = ['user_id' => $user->id, 'product_id' => $product->id, 'variant_id' => $variantId ?? 0];
            $existing = DB::table('cart_items')->where($identity)->first();
            if (! $existing && DB::table('cart_items')->where('user_id', $user->id)->count() >= 100) {
                throw ValidationException::withMessages(['cart' => '购物车最多 100 种商品。']);
            }
            DB::table('cart_items')->updateOrInsert($identity, ['quantity' => min(100, ($existing?->quantity ?? 0) + $quantity), 'created_at' => $existing?->created_at ?? now(), 'updated_at' => now()]);
        });
    }

    public function checkout(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $previous = DB::table('checkout_requests')->where('user_id', $user->id)->where('request_key', $data['request_key'])->first();
            if ($previous) {
                return Order::findOrFail($previous->order_id);
            }
            if (! empty($data['address_id'])) {
                $address = DB::table('user_addresses')->where('user_id', $user->id)->where('id', $data['address_id'])->first();
                abort_unless($address, 404);
                $data['address'] = implode(' ', [$address->recipient, $address->phone, $address->region, $address->address, $address->postal_code]);
                $data['region'] = $address->region;
            }
            $lines = DB::table('cart_items')->where('user_id', $user->id)->orderBy('product_id')->lockForUpdate()->get()->map(fn ($row) => (array) $row)->all();
            $order = app(ProductOrderService::class)->createMany($lines, $user, $data);
            DB::table('checkout_requests')->insert(['user_id' => $user->id, 'request_key' => $data['request_key'], 'order_id' => $order->id, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('cart_items')->where('user_id', $user->id)->delete();

            return $order;
        });
    }
}
