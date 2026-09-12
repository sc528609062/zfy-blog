<?php

namespace App\Services\Payment;

class WechatOfficialGateway extends OfficialGateway
{
    protected function provider(): string
    {
        return 'wechat';
    }

    public function code(): string
    {
        return 'wechat_official';
    }

    public function displayName(): string
    {
        return '微信官方';
    }
}
