<?php

namespace App\Services\Payment;

class AlipayOfficialGateway extends OfficialGateway
{
    protected function provider(): string
    {
        return 'alipay';
    }

    public function code(): string
    {
        return 'alipay_official';
    }

    public function displayName(): string
    {
        return '支付宝官方';
    }
}
