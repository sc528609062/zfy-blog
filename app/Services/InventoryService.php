<?php

namespace App\Services;

use App\Models\CardCode;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function reserve(Order $order, OrderItem $item, Product $product, ?ProductVariant $variant): void
    {
        $stock = null;
        if ($variant) {
            if ($variant->stock - $variant->reserved < $item->quantity) {
                $this->insufficient();
            }
            $variant->increment('reserved', $item->quantity);
        } elseif ($product->stock_strategy === 'limited') {
            $stock = DB::table('stock_items')->where('product_id', $product->id)->whereNull('product_variant_id')->lockForUpdate()->first();
            if (! $stock || $stock->quantity - $stock->reserved < $item->quantity) {
                $this->insufficient();
            }
            DB::table('stock_items')->where('id', $stock->id)->increment('reserved', $item->quantity);
        }
        if ($product->type === 'card') {
            $cards = CardCode::where('product_id', $product->id)->where('product_variant_id', $variant?->id)->where('status', 'available')->orderBy('id')->lockForUpdate()->limit($item->quantity)->get();
            if ($cards->count() !== $item->quantity) {
                $this->insufficient();
            }
            foreach ($cards as $card) {
                $card->update(['status' => 'reserved', 'order_item_id' => $item->id]);
            }
        }
        DB::table('inventory_reservations')->insert(['order_id' => $order->id, 'order_item_id' => $item->id, 'stock_item_id' => $stock?->id, 'product_variant_id' => $variant?->id, 'quantity' => $item->quantity, 'status' => 'reserved', 'expires_at' => $order->expires_at, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function consume(Order $order): bool
    {
        $reservations = DB::table('inventory_reservations')->where('order_id', $order->id)->orderBy('id')->lockForUpdate()->get();
        if ($reservations->isEmpty()) {
            return false;
        }
        foreach ($reservations as $reservation) {
            if ($reservation->status === 'consumed') {
                continue;
            }
            if ($reservation->status !== 'reserved') {
                $this->insufficient();
            }
            if ($reservation->product_variant_id) {
                $variant = ProductVariant::whereKey($reservation->product_variant_id)->lockForUpdate()->firstOrFail();
                if ($variant->stock < $reservation->quantity || $variant->reserved < $reservation->quantity) {
                    $this->insufficient();
                }
                $variant->update(['stock' => $variant->stock - $reservation->quantity]);
                $variant->decrement('reserved', $reservation->quantity);
            }
            if ($reservation->stock_item_id) {
                $stock = DB::table('stock_items')->where('id', $reservation->stock_item_id)->lockForUpdate()->first();
                if ($stock->quantity < $reservation->quantity || $stock->reserved < $reservation->quantity) {
                    $this->insufficient();
                }
                DB::table('stock_items')->where('id', $stock->id)->update(['quantity' => $stock->quantity - $reservation->quantity, 'reserved' => $stock->reserved - $reservation->quantity]);
            }
            CardCode::where('order_item_id', $reservation->order_item_id)->where('status', 'reserved')->update(['status' => 'delivered', 'delivered_at' => now()]);
            DB::table('inventory_reservations')->where('id', $reservation->id)->update(['status' => 'consumed', 'updated_at' => now()]);
        }

        return true;
    }

    public function release(Order $order): void
    {
        DB::table('coupon_reservations')->where('order_id', $order->id)->where('status', 'reserved')->update(['status' => 'released', 'updated_at' => now()]);
        foreach (DB::table('inventory_reservations')->where('order_id', $order->id)->where('status', 'reserved')->orderBy('id')->lockForUpdate()->get() as $reservation) {
            if ($reservation->product_variant_id) {
                ProductVariant::whereKey($reservation->product_variant_id)->decrement('reserved', $reservation->quantity);
            }
            if ($reservation->stock_item_id) {
                DB::table('stock_items')->where('id', $reservation->stock_item_id)->decrement('reserved', $reservation->quantity);
            }
            CardCode::where('order_item_id', $reservation->order_item_id)->where('status', 'reserved')->update(['status' => 'available', 'order_item_id' => null]);
            DB::table('inventory_reservations')->where('id', $reservation->id)->update(['status' => 'released', 'updated_at' => now()]);
        }
    }

    public function restockRefund(Order $order, bool $returnReceived = false): void
    {
        if (data_get($order->meta, 'late_payment')) {
            return;
        }
        $shipped = DB::table('shipments')->where('order_id', $order->id)->whereIn('status', ['shipped', 'received'])->exists();
        foreach (DB::table('inventory_reservations')->where('order_id', $order->id)->where('status', 'consumed')->orderBy('id')->lockForUpdate()->get() as $reservation) {
            $item = OrderItem::findOrFail($reservation->order_item_id);
            $type = data_get($item->meta, 'type');
            // Delivered secrets cannot be made available for another customer.
            if ($type === 'card' || ($type === 'physical' && $shipped && ! $returnReceived)) {
                continue;
            }
            if ($reservation->product_variant_id) {
                ProductVariant::whereKey($reservation->product_variant_id)->increment('stock', $reservation->quantity);
            }
            if ($reservation->stock_item_id) {
                DB::table('stock_items')->where('id', $reservation->stock_item_id)->increment('quantity', $reservation->quantity);
            }
            DB::table('inventory_reservations')->where('id', $reservation->id)->update(['status' => 'restocked', 'updated_at' => now()]);
        }
    }

    private function insufficient(): never
    {
        throw ValidationException::withMessages(['stock' => '商品可用库存不足。']);
    }
}
