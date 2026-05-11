<?php

namespace App\Services\Payment;

class WechatOfficialGateway extends AbstractGateway
{
    public function code(): string
    {
        return 'wechat_official';
    }

    public function displayName(): string
    {
        return '微信官方';
    }
}
