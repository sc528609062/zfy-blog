<?php

namespace App\Services;

use App\Models\Content;
use App\Models\Order;
use App\Models\User;

class OrderService
{
    public function createForContent(Content $content, ?User $user = null, string $gateway = 'alipay_official'): Order
    {
        $price = (float) data_get($content->pricing, 'price', 0);
        $order = Order::create([
            'user_id' => $user?->id,
            'order_no' => 'ZF'.now()->format('YmdHis').random_int(1000, 9999),
            'type' => $content->type === 'files' ? 'resource' : 'content',
            'status' => $price <= 0 ? 'paid' : 'pending',
            'pay_channel' => $gateway,
            'total_amount' => $price,
            'paid_amount' => $price <= 0 ? 0 : 0,
            'paid_at' => $price <= 0 ? now() : null,
            'expires_at' => now()->addMinutes(30),
            'buyer_snapshot' => $user ? ['id' => $user->id, 'name' => $user->name] : ['guest' => true],
            'meta' => ['content_slug' => $content->slug],
        ]);

        $order->items()->create([
            'item_type' => 'content',
            'item_id' => $content->id,
            'title' => $content->title,
            'quantity' => 1,
            'unit_price' => $price,
            'meta' => ['type' => $content->type],
        ]);

        return $order;
    }
}
