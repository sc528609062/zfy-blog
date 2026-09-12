<?php

namespace App\Services\Payment;

interface ConfigurableGateway
{
    public function assertConfigured(): void;
}
