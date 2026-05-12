<?php

namespace App\Domain\Commerce\Gateways;

abstract class AbstractGateway implements PaymentGateway
{
    public function isReady(): bool
    {
        return true;
    }

    public function refund(\App\Models\Order $order, float $amount, string $reason = ''): array
    {
        return [
            'status'  => 'not_implemented',
            'message' => 'Refund not implemented for ' . $this->code() . '. Implement in Sprint 4.',
        ];
    }
}
