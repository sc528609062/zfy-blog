<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * 全量种子。
 *
 * 调用顺序：
 * 1. RolePermission   角色与权限
 * 2. Settings         站点全局设置
 * 3. Theme            扫描 themes 目录写入 themes 表
 * 4. VipLevel         默认 VIP / SVIP / 永久
 * 5. Menu             主导航 + 页脚
 * 6. Taxonomy         默认分类
 *
 * 安装器流程会触发该 seeder；开发环境也可 `php artisan db:seed`。
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SettingsSeeder::class,
            ThemeSeeder::class,
            VipLevelSeeder::class,
            MenuSeeder::class,
            TaxonomySeeder::class,
        ]);
    }
}
