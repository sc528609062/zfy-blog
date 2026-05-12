<?php

namespace Database\Seeders;

use App\Services\SettingsService;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        /** @var SettingsService $s */
        $s = app(SettingsService::class);

        $s->setMany([
            'site.name'        => 'zfy-blog',
            'site.tagline'     => '精品内容 · 资源分享 · 创作者社区',
            'site.copyright'   => '© ' . date('Y') . ' zfy-blog',
            'site.icp'         => '',
            'site.logo'        => '',
            'site.favicon'     => '',
            'site.timezone'    => 'Asia/Shanghai',
            'site.locale'      => 'zh_CN',
            'reg.allow'        => '1',
            'reg.require_email_verify' => '0',
            'comment.allow_guest' => '1',
            'comment.audit'    => '0',
            'media.max_upload_mb' => '50',
            'commerce.author_share_default' => '0.70',
            'commerce.points_redeem_rate'   => '100',
        ], 'string');
    }
}
