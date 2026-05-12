<?php

/**
 * zfy-blog 全局配置
 *
 * 集中管理与开发计划书对应的核心常量、目录约定、内容类型、订单状态、收益状态、
 * VIP 默认数据、支付 driver 注册、可见性策略等。
 *
 * 通过 config('zfy.*') 在全工程访问。
 */
return [
    /*
    |--------------------------------------------------------------------------
    | 站点信息
    |--------------------------------------------------------------------------
    */
    'version' => env('ZFY_VERSION', '3.0.0'),
    'name' => env('APP_NAME', 'zfy-blog'),
    'tagline' => env('ZFY_TAGLINE', '精品内容 · 资源分享 · 创作者社区'),

    /*
    |--------------------------------------------------------------------------
    | 安装与升级
    |--------------------------------------------------------------------------
    */
    'install' => [
        'lock_file' => storage_path('app/installed.lock'),
        'min_php' => '8.3.0',
        'required_extensions' => [
            'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml',
            'ctype', 'json', 'fileinfo', 'gd', 'bcmath',
        ],
        'recommended_extensions' => ['exif', 'redis', 'intl', 'zip', 'curl'],
        'required_writable' => [
            'storage',
            'bootstrap/cache',
            'public',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 内容类型
    |--------------------------------------------------------------------------
    */
    'content_types' => [
        'post'   => ['label' => '文章',   'icon' => 'document-text', 'channel' => '/posts',  'feed' => true,  'priceable' => true,  'commentable' => true],
        'images' => ['label' => '图集',   'icon' => 'photo',         'channel' => '/images', 'feed' => true,  'priceable' => true,  'commentable' => true],
        'files'  => ['label' => '资源',   'icon' => 'archive-box',   'channel' => '/files',  'feed' => true,  'priceable' => true,  'commentable' => true],
        'page'   => ['label' => '独立页', 'icon' => 'window',        'channel' => null,      'feed' => false, 'priceable' => false, 'commentable' => false],
    ],

    'content_statuses' => [
        'draft'     => '草稿',
        'pending'   => '待审核',
        'published' => '已发布',
        'private'   => '私密',
        'scheduled' => '定时发布',
        'rejected'  => '审核拒绝',
        'trash'     => '回收站',
    ],

    'visibilities' => [
        'public'    => '公开',
        'logged_in' => '登录可见',
        'vip'       => 'VIP 可见',
        'paid'      => '购买可见',
        'commented' => '评论后可见',
        'password'  => '密码可见',
        'points'    => '积分兑换可见',
    ],

    /*
    |--------------------------------------------------------------------------
    | 后台角色
    |--------------------------------------------------------------------------
    | spatie/laravel-permission 使用 web guard。
    */
    'roles' => [
        'SUPER_ADMIN' => '超级管理员',
        'ADMIN'       => '管理员',
        'EDITOR'      => '内容编辑',
        'USER'        => '普通用户',
    ],

    /*
    |--------------------------------------------------------------------------
    | 主题
    |--------------------------------------------------------------------------
    */
    'theme' => [
        'default' => env('ZFY_THEME', 'default-blue'),
        'fallback' => 'default-blue',
        'path' => base_path('themes'),
        'public_url' => '/themes',
        'cache_key' => 'zfy.theme.active',
    ],

    /*
    |--------------------------------------------------------------------------
    | 插件
    |--------------------------------------------------------------------------
    | M12 实现，先留位。
    */
    'plugins' => [
        'path' => base_path('plugins'),
        'cache_key' => 'zfy.plugins.enabled',
    ],

    /*
    |--------------------------------------------------------------------------
    | 商业化
    |--------------------------------------------------------------------------
    */
    'commerce' => [
        'order_types' => [
            'vip_purchase', 'content_purchase', 'wallet_recharge',
            'points_purchase', 'points_exchange', 'author_withdrawal',
        ],
        'order_statuses' => [
            'pending', 'paid', 'closed', 'refunded', 'failed', 'cancelled',
        ],
        'gateways' => [
            'mock'    => \App\Domain\Commerce\Gateways\MockGateway::class,
            'alipay'  => \App\Domain\Commerce\Gateways\AlipayGateway::class,
            'wechat'  => \App\Domain\Commerce\Gateways\WechatGateway::class,
            'hupijiao' => \App\Domain\Commerce\Gateways\HupijiaoGateway::class,
            'epay'    => \App\Domain\Commerce\Gateways\EpayGateway::class,
        ],
        'default_gateway' => env('ZFY_PAYMENT_GATEWAY', 'mock'),
        'order_timeout_minutes' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | VIP 默认套餐（安装器 seed）
    |--------------------------------------------------------------------------
    */
    'vip_defaults' => [
        ['name' => 'VIP 会员',  'slug' => 'vip',  'level' => 1, 'duration_days' => 30,   'price' => 18.00, 'discount_rate' => 0.90, 'download_limit_daily' => 10],
        ['name' => 'SVIP 会员', 'slug' => 'svip', 'level' => 2, 'duration_days' => 30,   'price' => 35.00, 'discount_rate' => 0.85, 'download_limit_daily' => 30],
        ['name' => '永久会员',  'slug' => 'lifetime', 'level' => 9, 'duration_days' => null, 'price' => 888.00, 'discount_rate' => 0.70, 'download_limit_daily' => 50],
    ],

    /*
    |--------------------------------------------------------------------------
    | 收益与提现
    |--------------------------------------------------------------------------
    */
    'earnings' => [
        'default_author_share' => 0.70,  // 全站默认作者分成
        'statuses' => ['pending', 'available', 'withdrawing', 'paid', 'cancelled'],
    ],

    /*
    |--------------------------------------------------------------------------
    | 积分
    |--------------------------------------------------------------------------
    */
    'points' => [
        'sources' => [
            'sign_in'        => 5,
            'comment'        => 2,
            'submit_post'    => 10,
            'publish_post'   => 20,
            'buy_content'    => 5,
            'buy_vip'        => 50,
        ],
        'redeem_rate' => 100,  // 100 积分 = 1 元
    ],

    /*
    |--------------------------------------------------------------------------
    | 下载与资源风控
    |--------------------------------------------------------------------------
    */
    'downloads' => [
        'sign_ttl_minutes' => 10,
        'max_concurrent' => 3,
        'guest_per_day' => 1,
        'user_per_day' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | 媒体库
    |--------------------------------------------------------------------------
    */
    'media' => [
        'max_upload_mb' => 50,
        'image_mime' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        'archive_mime' => ['application/zip', 'application/x-7z-compressed', 'application/x-rar-compressed'],
        'document_mime' => ['application/pdf', 'application/msword', 'text/plain', 'text/markdown'],
        'disallow_svg' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    */
    'api' => [
        'prefix' => 'api/v1',
        'rate_limit' => env('ZFY_API_RATE_LIMIT', '60,1'),
    ],
];
