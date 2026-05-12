<?php

namespace App\Services\Payment;

class AlipayOfficialGateway extends AbstractGateway
{
    public function code(): string
    {
        return 'alipay_official';
    }

    public function displayName(): string
    {
        return '支付宝官方';
    }
}
