<?php

namespace App\Services\Payment;

interface VerifiedNotificationGateway
{
    /** Verify gateway data before returning normalized order_no, trade_no, money and status. */
    public function notification(array $payload): ?array;
}
