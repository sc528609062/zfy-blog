<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;

interface PaymentGateway
{
    public function code(): string;

    public function displayName(): string;

    public function createPayment(Order $order): Payment;

    public function verifyNotify(array $payload): bool;

    public function query(Payment $payment): array;
}
