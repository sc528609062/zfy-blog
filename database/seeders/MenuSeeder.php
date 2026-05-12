<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $primary = Menu::updateOrCreate(
            ['location' => 'primary'],
            ['name' => '主导航', 'description' => '前台顶部主菜单']
        );

        $items = [
            ['label' => '首页',   'icon' => 'home',     'target_type' => 'route', 'target_ref' => 'home'],
            ['label' => '文章',   'icon' => 'document', 'target_type' => 'route', 'target_ref' => 'channel.posts'],
            ['label' => '图集',   'icon' => 'photo',    'target_type' => 'route', 'target_ref' => 'channel.images'],
            ['label' => '资源',   'icon' => 'archive',  'target_type' => 'route', 'target_ref' => 'channel.files'],
            ['label' => '排行榜', 'icon' => 'fire',     'target_type' => 'route', 'target_ref' => 'rank'],
            ['label' => '作者',   'icon' => 'users',    'target_type' => 'route', 'target_ref' => 'authors'],
            ['label' => 'VIP',    'icon' => 'crown',    'target_type' => 'route', 'target_ref' => 'vip.index'],
        ];

        foreach ($items as $i => $row) {
            MenuItem::updateOrCreate(
                ['menu_id' => $primary->id, 'label' => $row['label']],
                array_merge($row, ['sort_order' => $i, 'enabled' => true])
            );
        }

        // 页脚
        $footer = Menu::updateOrCreate(
            ['location' => 'footer'],
            ['name' => '页脚菜单']
        );
        $footerItems = [
            ['label' => '关于我们', 'target_type' => 'url', 'target_ref' => '/p/about'],
            ['label' => '服务条款', 'target_type' => 'url', 'target_ref' => '/p/terms'],
            ['label' => '隐私政策', 'target_type' => 'url', 'target_ref' => '/p/privacy'],
            ['label' => '友情链接', 'target_type' => 'route', 'target_ref' => 'links'],
        ];
        foreach ($footerItems as $i => $row) {
            MenuItem::updateOrCreate(
                ['menu_id' => $footer->id, 'label' => $row['label']],
                array_merge($row, ['sort_order' => $i, 'enabled' => true])
            );
        }
    }
}
