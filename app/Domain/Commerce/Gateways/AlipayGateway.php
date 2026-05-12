<?php

namespace App\Domain\Commerce\Gateways;

use App\Models\Order;

/**
 * 支付宝官方支付网关骨架。
 *
 * Sprint 4 (M8) 完整实现：
 *  - 应用 ID / 应用私钥 / 支付宝公钥 / 沙箱开关
 *  - 电脑网站支付 alipay.trade.page.pay
 *  - 当面付 alipay.trade.precreate
 *  - alipay.trade.query 主动查询
 *  - 异步通知签名验证 (RSA2)
 */
class AlipayGateway extends AbstractGateway
{
    public function code(): string { return 'alipay'; }
    public function label(): string { return '支付宝'; }

    public function isReady(): bool
    {
        return ! empty(config('zfy.alipay.app_id'));
    }

    public function charge(Order $order): array
    {
        throw new \LogicException('Alipay gateway will be implemented in Sprint 4 (M8). Please use mock gateway for local dev.');
    }

    public function verify(array $payload): bool
    {
        return false;
    }

    public function notify(array $payload): array
    {
        return ['status' => 'pending'];
    }

    public function query(Order $order): array
    {
        return ['status' => $order->status];
    }
}
