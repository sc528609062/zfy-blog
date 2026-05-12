<?php

namespace Database\Seeders;

use App\Models\VipLevel;
use Illuminate\Database\Seeder;

class VipLevelSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('zfy.vip_defaults') as $i => $row) {
            VipLevel::updateOrCreate(
                ['slug' => $row['slug']],
                array_merge($row, [
                    'enabled'    => true,
                    'sort_order' => $i,
                    'color'      => match ($row['slug']) {
                        'vip'      => '#3b82f6',
                        'svip'     => '#8b5cf6',
                        'lifetime' => '#f59e0b',
                        default    => '#64748b',
                    },
                    'benefits' => match ($row['slug']) {
                        'vip'  => ['每日下载 10 次', '免费下载普通资源', '9 折优惠'],
                        'svip' => ['每日下载 30 次', '免费下载全站资源', '8.5 折优惠', '高级礼包'],
                        'lifetime' => ['每日下载 50 次', '永久免费下载', '7 折优惠', '专属客服'],
                        default => [],
                    },
                ])
            );
        }
    }
}
