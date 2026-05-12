<?php

namespace App\Domain\Commerce;

use App\Domain\Commerce\Gateways\PaymentGateway;
use Illuminate\Contracts\Foundation\Application;

/**
 * 支付网关管理器，集中实例化各 driver。
 *
 *   app(PaymentGatewayManager::class)->driver('mock')->charge($order);
 *
 * 默认 driver 来自 config('zfy.commerce.default_gateway')。
 */
class PaymentGatewayManager
{
    /** @var array<string, PaymentGateway> */
    protected array $resolved = [];

    public function __construct(protected Application $app) {}

    public function driver(?string $code = null): PaymentGateway
    {
        $code ??= config('zfy.commerce.default_gateway');

        if (isset($this->resolved[$code])) {
            return $this->resolved[$code];
        }

        $class = config("zfy.commerce.gateways.$code");
        if (! $class || ! class_exists($class)) {
            throw new \InvalidArgumentException("Payment gateway [{$code}] not registered.");
        }

        return $this->resolved[$code] = $this->app->make($class);
    }

    public function all(): array
    {
        $list = [];
        foreach (array_keys(config('zfy.commerce.gateways', [])) as $code) {
            try {
                $list[$code] = $this->driver($code);
            } catch (\Throwable $e) {
                // 跳过有问题的 driver
            }
        }
        return $list;
    }

    public function availableForCheckout(): array
    {
        return array_filter($this->all(), fn (PaymentGateway $g) => $g->isReady());
    }
}
