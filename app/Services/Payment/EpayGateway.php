<?php

namespace App\Services\Payment;

class EpayGateway extends AbstractGateway
{
    public function code(): string
    {
        return 'epay';
    }

    public function displayName(): string
    {
        return '易支付';
    }
}
