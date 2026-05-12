<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Content;
use App\Models\Order;
use App\Models\PageLayout;
use App\Models\Plugin;
use App\Models\PointsAccount;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\Theme;
use App\Models\ThemeSetting;
use App\Models\User;
use App\Models\VipLevel;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('zfy.roles') as $role) {
            Role::findOrCreate($role);
        }

        foreach (['manage system', 'manage contents', 'manage commerce', 'manage themes', 'manage plugins', 'publish contents', 'buy contents'] as $permission) {
            Permission::findOrCreate($permission);
        }

        Role::findByName('SUPER_ADMIN')->syncPermissions(Permission::all());
        Role::findByName('ADMIN')->syncPermissions(['manage contents', 'manage commerce', 'manage themes', 'manage plugins']);
        Role::findByName('EDITOR')->syncPermissions(['manage contents', 'publish contents']);
        Role::findByName('USER')->syncPermissions(['buy contents']);

        $admin = User::updateOrCreate(['email' => 'admin@zfy-blog.test'], [
            'name' => 'zfy站长',
            'username' => 'admin',
            'password' => Hash::make('zfy-blog-123456'),
            'avatar_url' => 'https://api.dicebear.com/8.x/adventurer/svg?seed=zfy',
            'bio' => 'zfy-blog 超级管理员',
            'is_author' => true,
            'author_status' => 'approved',
        ]);
        $admin->syncRoles(['SUPER_ADMIN']);
        Wallet::firstOrCreate(['user_id' => $admin->id], ['balance' => 888.00]);
        PointsAccount::firstOrCreate(['user_id' => $admin->id], ['points' => 5200]);

        $authors = collect([
            ['name' => '攻略组-星辰', 'email' => 'star@zfy-blog.test'],
            ['name' => '设计师阿之', 'email' => 'design@zfy-blog.test'],
            ['name' => '摄影小白', 'email' => 'photo@zfy-blog.test'],
            ['name' => 'MOD达人', 'email' => 'mod@zfy-blog.test'],
        ])->map(function ($row) {
            $user = User::updateOrCreate(['email' => $row['email']], [
                'name' => $row['name'],
                'username' => str($row['email'])->before('@')->slug(),
                'password' => Hash::make('zfy-blog-123456'),
                'avatar_url' => 'https://api.dicebear.com/8.x/adventurer/svg?seed='.$row['email'],
                'bio' => '签约创作者，专注内容和资源分享。',
                'is_author' => true,
                'author_status' => 'approved',
            ]);
            $user->syncRoles(['USER']);
            Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 128.00]);
            PointsAccount::firstOrCreate(['user_id' => $user->id], ['points' => 1200]);

            return $user;
        });

        $categories = collect([
            ['游戏资源', 'game-resources', 'files'],
            ['攻略教程', 'guides', 'post'],
            ['MOD社区', 'mods', 'files'],
            ['设计素材', 'design-assets', 'files'],
            ['插画绘画', 'illustration', 'images'],
            ['编程开发', 'coding', 'post'],
            ['学习成长', 'learning', 'post'],
        ])->map(fn ($row, $index) => Category::updateOrCreate(['slug' => $row[1]], [
            'name' => $row[0],
            'type' => $row[2],
            'icon' => ['gamepad-2', 'book-open', 'wrench', 'gem', 'palette', 'code-2', 'graduation-cap'][$index] ?? null,
            'description' => $row[0].'精选内容聚合',
            'sort_order' => $index,
        ]));

        $tags = collect(['VIP免费', '付费资源', '高赞', '编辑推荐', '新手入门', '商业授权', '下载热门', '原创'])->map(
            fn ($name) => Tag::updateOrCreate(['slug' => str($name)->slug('-')], ['name' => $name, 'color' => '#1684ff'])
        );

        VipLevel::updateOrCreate(['slug' => 'vip'], [
            'name' => 'VIP',
            'level' => 1,
            'price_monthly' => 29,
            'price_yearly' => 199,
            'discount_percent' => 80,
            'fixed_discount' => 5,
            'benefits' => ['专属资源', '高速下载', '会员折扣', '专属标识'],
        ]);
        VipLevel::updateOrCreate(['slug' => 'svip'], [
            'name' => 'SVIP',
            'level' => 2,
            'price_monthly' => 59,
            'price_yearly' => 399,
            'discount_percent' => 60,
            'fixed_discount' => 15,
            'benefits' => ['全部资源', '无限下载', '优先客服', '作者分成加速'],
        ]);

        $covers = [
            'https://images.unsplash.com/photo-1511512578047-dfb367046420?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=900&q=80',
        ];

        $items = [
            ['post', '幻境之旅全剧情流程图文攻略', 'guides', 0],
            ['post', '2026 年 Web 前端开发趋势与技术解析', 'coding', 0],
            ['files', '幻境之旅 Ver1.4.2 豪华版资源包', 'game-resources', 69],
            ['files', '通用后台管理系统模板', 'design-assets', 129],
            ['files', '商业插画素材包 vol.3', 'design-assets', 39],
            ['files', 'WordPress 优化插件资源合集', 'mods', 59],
            ['images', '治愈系插画图集：温暖日常', 'illustration', 0],
            ['images', '清新日系色调 Lightroom 预设展示', 'illustration', 19],
            ['page', '关于 zfy-blog', 'learning', 0],
        ];

        foreach ($items as $index => [$type, $title, $categorySlug, $price]) {
            $content = Content::updateOrCreate(['slug' => str($title)->slug('-')], [
                'author_id' => $authors[$index % $authors->count()]->id ?? $admin->id,
                'category_id' => $categories->firstWhere('slug', $categorySlug)?->id,
                'type' => $type,
                'status' => 'published',
                'title' => $title,
                'subtitle' => $type === 'files' ? '支持 VIP 折扣与付费下载' : '精选中文内容',
                'excerpt' => '这是 zfy-blog 演示内容，用于展示内容流、付费权限、主题样式和 API 返回结构。',
                'cover_url' => $covers[$index % count($covers)],
                'block_json' => ['blocks' => [['type' => 'paragraph', 'text' => '内容块演示']]],
                'rendered_html' => '<p>这里是编辑器渲染后的内容。支持隐藏内容、下载块、提示块、代码块、图集和视频等复杂结构。</p>',
                'markdown_cache' => '这里是 Markdown 缓存内容。',
                'seo' => ['title' => $title, 'description' => 'zfy-blog 演示 SEO 描述'],
                'pricing' => ['price' => $price, 'vip_price' => max(0, $price - 20), 'points' => $price * 10],
                'access_rules' => ['guest' => $price <= 0, 'vip_free' => $price <= 39, 'comment_visible' => false],
                'view_count' => random_int(800, 9800),
                'comment_count' => random_int(8, 260),
                'like_count' => random_int(20, 1200),
                'download_count' => random_int(60, 3200),
                'published_at' => now()->subDays($index),
            ]);
            $content->tags()->sync($tags->random(min(3, $tags->count()))->pluck('id'));
        }

        foreach (config('zfy.themes') as $slug => $theme) {
            $model = Theme::updateOrCreate(['slug' => $slug], [
                'name' => $theme['name'],
                'version' => '1.0.0',
                'author' => 'zfy-blog',
                'compatible' => '^1.0',
                'entry_view' => "themes.{$slug}.layout",
                'preview' => "ui-mockups/gpt-image-2/complete-system/{$slug}/home.png",
                'menus' => ['primary', 'mobile', 'footer'],
                'regions' => ['home', 'sidebar', 'footer', 'user-center'],
                'settings_schema' => [
                    'global' => ['logo_text', 'primary_color', 'nav', 'footer_text'],
                    'pages' => ['home', 'channel', 'detail', 'vip', 'user', 'admin'],
                    'builder' => ['row', 'columns', 'content-feed', 'rank', 'vip', 'ad', 'html', 'theme-component'],
                ],
                'is_active' => $slug === config('zfy.default_theme'),
            ]);

            ThemeSetting::updateOrCreate(
                ['theme_id' => $model->id, 'scope' => 'global', 'key' => 'primary_color'],
                ['value' => ['raw' => $theme['accent']]]
            );
        }

        Setting::updateOrCreate(['key' => 'site.active_theme'], [
            'value' => ['slug' => config('zfy.default_theme')],
            'autoload' => true,
        ]);

        PageLayout::updateOrCreate(['scope' => 'home'], [
            'title' => '首页可视化布局',
            'schema' => [
                'blocks' => [
                    ['type' => 'hero', 'title' => '优质内容与资源一站聚合', 'subtitle' => '文章、图集、资源、作者和会员体系完整联动'],
                    ['type' => 'content-feed', 'title' => '最新内容'],
                    ['type' => 'vip', 'title' => 'zfy-blog VIP'],
                ],
            ],
            'status' => 'published',
        ]);

        foreach ([['支付增强插件', 'payment-enhancer'], ['微信通知插件', 'wechat-notifier'], ['内容 SEO 工具', 'seo-toolkit']] as [$name, $slug]) {
            Plugin::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'version' => '1.0.0',
                'provider' => null,
                'permissions' => ['read_settings'],
                'events' => ['content.published', 'order.paid'],
                'enabled' => $slug === 'seo-toolkit',
            ]);
        }

        Order::updateOrCreate(['order_no' => 'ZF202605110001'], [
            'user_id' => $admin->id,
            'type' => 'vip',
            'status' => 'paid',
            'pay_channel' => 'alipay_official',
            'total_amount' => 199,
            'paid_amount' => 199,
            'paid_at' => now(),
            'expires_at' => now()->addYear(),
            'buyer_snapshot' => ['id' => $admin->id, 'name' => $admin->name],
            'meta' => ['gateway' => 'sandbox-placeholder'],
        ]);
    }
}
