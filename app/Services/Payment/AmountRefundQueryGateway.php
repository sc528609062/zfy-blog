<?php

namespace App\Services\Payment;

use App\Models\Payment;

interface AmountRefundQueryGateway extends RefundableGateway
{
    public function queryRefundAmount(Payment $payment, string $refundNo, string $amount): array;
}
