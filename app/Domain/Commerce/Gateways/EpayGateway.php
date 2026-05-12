<?php

namespace App\Domain\Commerce\Gateways;

use App\Models\Order;

/**
 * 易支付（彩虹易支付/类彩虹）网关骨架。
 *
 * Sprint 4 (M8) 完整实现：商户 ID + 密钥 + MD5 签名 + 类 epay 接口。
 */
class EpayGateway extends AbstractGateway
{
    public function code(): string { return 'epay'; }
    public function label(): string { return '易支付'; }

    public function isReady(): bool
    {
        return ! empty(config('zfy.epay.pid'));
    }

    public function charge(Order $order): array
    {
        throw new \LogicException('Epay gateway will be implemented in Sprint 4 (M8).');
    }

    public function verify(array $payload): bool { return false; }
    public function notify(array $payload): array { return ['status' => 'pending']; }
    public function query(Order $order): array { return ['status' => $order->status]; }
}
