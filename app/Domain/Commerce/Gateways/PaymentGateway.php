<?php

namespace App\Domain\Commerce\Gateways;

use App\Models\Order;

/**
 * 统一支付网关接口。所有 driver 必须实现：
 *
 * - charge   生成支付参数（二维码 URL / 跳转 URL / 客户端调用 payload）
 * - verify   验证回调签名
 * - notify   处理异步通知，幂等执行后续动作
 * - query    主动查单（解决回调丢失）
 * - refund   退款
 */
interface PaymentGateway
{
    /** Gateway 编码：alipay/wechat/hupijiao/epay/mock */
    public function code(): string;

    /** 友好显示名 */
    public function label(): string;

    /** 是否可用（密钥已配置） */
    public function isReady(): bool;

    /**
     * 生成支付。返回结构：
     * [
     *   'order_no'   => 'ZFY...',
     *   'gateway'    => 'mock',
     *   'redirect'   => 'https://...',   // 同步跳转
     *   'qrcode'     => 'https://...',   // 二维码内容
     *   'payload'    => [...],           // 客户端 JSAPI/SDK 调用参数
     * ]
     */
    public function charge(Order $order): array;

    /** 校验回调签名 */
    public function verify(array $payload): bool;

    /**
     * 处理回调。幂等，返回 ['status' => 'paid'|'failed'|'pending']
     */
    public function notify(array $payload): array;

    /** 主动查单 */
    public function query(Order $order): array;

    /** 退款 */
    public function refund(Order $order, float $amount, string $reason = ''): array;
}
