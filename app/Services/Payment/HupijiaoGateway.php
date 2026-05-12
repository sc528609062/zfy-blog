<?php

namespace App\Services\Payment;

class HupijiaoGateway extends AbstractGateway
{
    public function code(): string
    {
        return 'hupijiao_v3';
    }

    public function displayName(): string
    {
        return '虎皮椒 V3';
    }
}
