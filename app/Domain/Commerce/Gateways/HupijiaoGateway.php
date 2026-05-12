<?php

namespace App\Domain\Commerce\Gateways;

use App\Models\Order;

/**
 * 虎皮椒 V3 网关骨架。
 *
 * Sprint 4 (M8) 完整实现：appid + appsecret + MD5 签名 + xunhupay.com 网关。
 */
class HupijiaoGateway extends AbstractGateway
{
    public function code(): string { return 'hupijiao'; }
    public function label(): string { return '虎皮椒'; }

    public function isReady(): bool
    {
        return ! empty(config('zfy.hupijiao.appid'));
    }

    public function charge(Order $order): array
    {
        throw new \LogicException('Hupijiao gateway will be implemented in Sprint 4 (M8).');
    }

    public function verify(array $payload): bool { return false; }
    public function notify(array $payload): array { return ['status' => 'pending']; }
    public function query(Order $order): array { return ['status' => $order->status]; }
}
