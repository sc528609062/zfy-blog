<?php

return [
    'hupijiao' => ['appid' => env('HUPIJIAO_APP_ID'), 'secret' => env('HUPIJIAO_APP_SECRET'), 'payment' => env('HUPIJIAO_PAYMENT', 'wechat'), 'url' => env('HUPIJIAO_URL', 'https://api.xunhupay.com/payment')],
    'alipay' => [
        'app_id' => env('ALIPAY_APP_ID'), 'app_secret_cert' => env('ALIPAY_PRIVATE_KEY'),
        'app_public_cert_path' => env('ALIPAY_APP_CERT'), 'alipay_public_cert_path' => env('ALIPAY_PUBLIC_CERT'),
        'alipay_root_cert_path' => env('ALIPAY_ROOT_CERT'), 'mode' => 0,
    ],
    'wechat' => [
        'mch_id' => env('WECHAT_MCH_ID'), 'mch_secret_key' => env('WECHAT_API_V3_KEY'),
        'mch_secret_cert' => env('WECHAT_PRIVATE_KEY'), 'mch_public_cert_path' => env('WECHAT_MCH_CERT'),
        'mp_app_id' => env('WECHAT_APP_ID'), 'wechat_public_cert_path' => env('WECHAT_PLATFORM_SERIAL') && env('WECHAT_PLATFORM_CERT') ? [env('WECHAT_PLATFORM_SERIAL') => env('WECHAT_PLATFORM_CERT')] : [], 'mode' => 0,
    ],
    'epay' => [
        'url' => env('EPAY_URL'),
        'pid' => env('EPAY_PID'),
        'key' => env('EPAY_KEY'),
        'type' => env('EPAY_TYPE', 'alipay'),
    ],
];
