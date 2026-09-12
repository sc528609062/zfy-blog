<?php

namespace App\Services\Payment;

use App\Models\Payment;

interface RefundableGateway
{
    /** Return status (refunded or processing) and a provider reference. */
    public function refund(Payment $payment, string $refundNo, string $amount): array;

    public function queryRefund(Payment $payment, string $refundNo): array;
}
