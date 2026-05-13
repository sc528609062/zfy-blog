<?php

namespace Database\Seeders;

use App\Models\PageLayout;
use App\Models\PointsAccount;
use App\Models\Setting;
use App\Models\Theme;
use App\Models\User;
use App\Models\VipLevel;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CoreInstallSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('zfy.roles') as $role) {
            Role::findOrCreate($role);
        }

        foreach ([
            'manage system',
            'manage contents',
            'manage commerce',
            'manage themes',
            'manage plugins',
            'manage users',
            'manage links',
            'publish contents',
            'buy contents',
        ] as $permission) {
            Permission::findOrCreate($permission);
        }

        Role::findByName('SUPER_ADMIN')->syncPermissions(Permission::all());
        Role::findByName('ADMIN')->syncPermissions(['manage contents', 'publish contents', 'manage commerce', 'manage themes', 'manage plugins', 'manage links']);
        Role::findByName('EDITOR')->syncPermissions(['manage contents', 'publish contents']);
        Role::findByName('USER')->syncPermissions(['buy contents']);

        $admin = User::updateOrCreate(['email' => 'admin@zfy-blog.test'], [
            'name' => 'zfy站长',
            'username' => 'admin',
            'password' => Hash::make('zfy-blog-123456'),
            'avatar_url' => '/assets/zfy/placeholders/avatar.svg',
            'bio' => 'zfy-blog 超级管理员',
            'is_author' => true,
            'author_status' => 'approved',
        ]);
        $admin->syncRoles(['SUPER_ADMIN']);

        Wallet::firstOrCreate(['user_id' => $admin->id], ['balance' => 0]);
        PointsAccount::firstOrCreate(['user_id' => $admin->id], ['points' => 0]);

        VipLevel::updateOrCreate(['slug' => 'vip'], [
            'name' => 'VIP',
            'level' => 1,
            'price_monthly' => 29,
            'price_yearly' => 199,
            'discount_percent' => 80,
            'fixed_discount' => 5,
            'benefits' => ['专属资源', '高速下载', '会员折扣'],
        ]);

        VipLevel::updateOrCreate(['slug' => 'svip'], [
            'name' => 'SVIP',
            'level' => 2,
            'price_monthly' => 59,
            'price_yearly' => 399,
            'discount_percent' => 60,
            'fixed_discount' => 15,
            'benefits' => ['全部资源', '无限下载', '优先客服'],
        ]);

        foreach (config('zfy.themes') as $slug => $theme) {
            Theme::updateOrCreate(['slug' => $slug], [
                'name' => $theme['name'],
                'version' => '1.0.0',
                'author' => 'zfy-blog',
                'compatible' => '^1.0',
                'entry_view' => "themes.{$slug}.layout",
                'preview' => "/assets/zfy/placeholders/{$theme['tone']}.svg",
                'menus' => ['primary', 'mobile', 'footer'],
                'regions' => ['home', 'sidebar', 'footer', 'user-center'],
                'settings_schema' => [
                    'global' => ['logo_text', 'primary_color', 'nav', 'footer_text'],
                    'pages' => ['home', 'channel', 'detail', 'vip', 'user', 'admin'],
                    'builder' => ['row', 'columns', 'content-feed', 'rank', 'vip', 'ad', 'html', 'theme-component'],
                ],
                'is_active' => $slug === config('zfy.default_theme'),
            ]);
        }

        Setting::updateOrCreate(['key' => 'site.name'], ['value' => ['raw' => config('app.name', 'zfy-blog')], 'autoload' => true]);
        Setting::updateOrCreate(['key' => 'site.url'], ['value' => ['raw' => config('app.url')], 'autoload' => true]);
        Setting::updateOrCreate(['key' => 'site.active_theme'], [
            'value' => ['slug' => config('zfy.default_theme')],
            'autoload' => true,
        ]);

        PageLayout::updateOrCreate(['scope' => 'home'], [
            'title' => '首页布局',
            'schema' => [
                'blocks' => [
                    ['type' => 'hero', 'title' => config('app.name', 'zfy-blog'), 'subtitle' => '内容、资源与商城一体化平台'],
                    ['type' => 'content-feed', 'title' => '最新内容'],
                ],
            ],
            'status' => 'published',
        ]);
    }
}
