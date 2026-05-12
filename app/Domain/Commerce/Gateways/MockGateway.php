<?php

namespace App\Domain\Commerce\Gateways;

use App\Models\Order;
use Illuminate\Support\Facades\URL;

/**
 * Mock 支付网关。
 *
 * 用于本地开发：跳转到一个内部模拟收银台，点击 "支付成功" 后写回订单状态。
 * 默认启用，配置 ZFY_PAYMENT_GATEWAY=mock 即生效。
 */
class MockGateway extends AbstractGateway
{
    public function code(): string { return 'mock'; }
    public function label(): string { return 'Mock 模拟支付'; }

    public function charge(Order $order): array
    {
        // 模拟收银台路由（Sprint 4 实现）
        $redirect = URL::to('/checkout/mock/' . $order->order_no);

        return [
            'order_no' => $order->order_no,
            'gateway'  => 'mock',
            'redirect' => $redirect,
            'qrcode'   => $redirect,
            'payload'  => [
                'message' => '本地 Mock 支付，点击跳转后可一键标记已支付。',
            ],
        ];
    }

    public function verify(array $payload): bool
    {
        // Mock 不做真实签名
        return true;
    }

    public function notify(array $payload): array
    {
        return [
            'status'   => 'paid',
            'order_no' => $payload['order_no'] ?? null,
            'amount'   => $payload['amount'] ?? 0,
        ];
    }

    public function query(Order $order): array
    {
        return [
            'status'   => $order->status,
            'order_no' => $order->order_no,
            'amount'   => (float) $order->amount,
        ];
    }
}
