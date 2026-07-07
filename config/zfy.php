<?php

return [
    'version' => '1.0.1',
    'installed' => env('ZFY_INSTALLED', false),
    'default_theme' => env('ZFY_DEFAULT_THEME', 'style-a-blue-gaming'),
    'themes' => [
        'style-a-blue-gaming' => [
            'name' => '蓝白游戏资源社区风',
            'accent' => '#1684ff',
            'tone' => 'blue',
            'description' => '综合内容流、资源下载、VIP、排行和后台管理效率。',
        ],
        'style-b-marketplace' => [
            'name' => '蓝白资源商城交易风',
            'accent' => '#2563eb',
            'tone' => 'market',
            'description' => '商品卡、付费下载、优惠、订单和商业化。',
        ],
        'style-c-creative' => [
            'name' => '黄粉创意资源站风',
            'accent' => '#facc15',
            'tone' => 'creative',
            'description' => '创作者社区、轻快视觉、资源瀑布流和会员转化。',
        ],
    ],
    'roles' => ['SUPER_ADMIN', 'ADMIN', 'EDITOR', 'USER'],
    'content_types' => ['post', 'images', 'files', 'page'],
    'editor' => [
        'media' => [
            'disk' => 'media',
            'storage_root' => 'media',
            'default_directory' => 'editor/images',
            'library_per_page' => 24,
            'upload_max_kb' => 20480,
            'directories' => [
                ['value' => 'editor/images', 'label' => '正文图片'],
                ['value' => 'editor/covers', 'label' => '文章封面'],
                ['value' => 'editor/files', 'label' => '资源附件'],
            ],
        ],
    ],
    'payment_gateways' => [
        'alipay_official' => '支付宝官方',
        'wechat_official' => '微信官方',
        'hupijiao_v3' => '虎皮椒 V3',
        'epay' => '易支付',
        'balance' => '余额支付',
        'points' => '积分支付',
    ],
];
