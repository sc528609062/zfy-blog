<?php

namespace App\Domain\Commerce\Gateways;

use App\Models\Order;

/**
 * 微信支付（V3 API）网关骨架。
 *
 * Sprint 4 (M8) 完整实现：商户号 / API V3 密钥 / 商户证书 / Native 扫码 / JSAPI / H5。
 */
class WechatGateway extends AbstractGateway
{
    public function code(): string { return 'wechat'; }
    public function label(): string { return '微信支付'; }

    public function isReady(): bool
    {
        return ! empty(config('zfy.wechat.mch_id'));
    }

    public function charge(Order $order): array
    {
        throw new \LogicException('Wechat gateway will be implemented in Sprint 4 (M8).');
    }

    public function verify(array $payload): bool { return false; }
    public function notify(array $payload): array { return ['status' => 'pending']; }
    public function query(Order $order): array { return ['status' => $order->status]; }
}
