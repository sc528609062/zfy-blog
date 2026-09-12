<?php

namespace App\Services\Payment;

use App\Models\Payment;

interface ClosableGateway
{
    public function close(Payment $payment): array;
}
