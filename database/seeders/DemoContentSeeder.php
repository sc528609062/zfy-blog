<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Content;
use App\Models\Link;
use App\Models\LinkCategory;
use App\Models\Order;
use App\Models\Plugin;
use App\Models\PointsAccount;
use App\Models\Product;
use App\Models\Tag;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->first();
        $author = User::updateOrCreate(['email' => 'star@zfy-blog.test'], [
            'name' => '攻略组-星辰',
            'username' => 'star',
            'password' => Hash::make('zfy-blog-123456'),
            'avatar_url' => '/assets/zfy/placeholders/avatar.svg',
            'bio' => '签约创作者，专注内容和资源分享。',
            'is_author' => true,
            'author_status' => 'approved',
        ]);
        $author->syncRoles(['USER']);
        Wallet::firstOrCreate(['user_id' => $author->id], ['balance' => 128]);
        PointsAccount::firstOrCreate(['user_id' => $author->id], ['points' => 1200]);

        if ($admin) {
            $admin->wallet()->updateOrCreate([], ['balance' => 888]);
            $admin->pointsAccount()->updateOrCreate([], ['points' => 5200]);
        }

        $category = Category::updateOrCreate(['slug' => 'guides'], [
            'name' => '攻略教程',
            'type' => 'post',
            'description' => '演示内容分类',
        ]);

        $tag = Tag::updateOrCreate(['slug' => 'demo'], ['name' => '演示']);

        $content = Content::updateOrCreate(['slug' => 'zfy-demo-content'], [
            'author_id' => $admin?->id,
            'category_id' => $category->id,
            'type' => 'post',
            'status' => 'published',
            'title' => 'zfy-blog 演示文章',
            'excerpt' => '这是可选演示数据，不会在正式安装流程中默认导入。',
            'cover_url' => '/assets/zfy/placeholders/cover-blue.svg',
            'rendered_html' => '<p>演示文章内容。</p>',
            'published_at' => now(),
        ]);
        $content->tags()->sync([$tag->id]);

        $file = Content::updateOrCreate(['slug' => 'zfy-demo-resource'], [
            'author_id' => $admin?->id,
            'category_id' => $category->id,
            'type' => 'files',
            'status' => 'published',
            'title' => 'zfy-blog 演示资源',
            'excerpt' => '用于测试内容付费、订单和下载权限。',
            'cover_url' => '/assets/zfy/placeholders/cover-blue.svg',
            'rendered_html' => '<p>演示资源内容。</p>',
            'pricing' => ['price' => 69, 'vip_price' => 49, 'points' => 690],
            'access_rules' => ['guest' => false, 'vip_free' => false],
            'published_at' => now(),
        ]);
        $file->tags()->sync([$tag->id]);

        Product::updateOrCreate(['slug' => 'zfy-demo-product'], [
            'content_id' => $file->id,
            'title' => '演示数字商品',
            'type' => 'digital',
            'status' => 'published',
            'price' => 29,
            'stock_strategy' => 'unlimited',
        ]);

        $linkCategory = LinkCategory::updateOrCreate(['slug' => 'friends'], [
            'name' => '友情链接',
            'description' => '演示友链分类',
        ]);

        Link::updateOrCreate(['url' => config('app.url')], [
            'link_category_id' => $linkCategory->id,
            'name' => 'zfy-blog',
            'status' => 'approved',
            'target' => '_blank',
            'nofollow' => false,
        ]);

        Order::updateOrCreate(['order_no' => 'ZF-DEMO-0001'], [
            'user_id' => $admin?->id,
            'type' => 'demo',
            'status' => 'paid',
            'pay_channel' => 'balance',
            'total_amount' => 29,
            'paid_amount' => 29,
            'paid_at' => now(),
        ]);

        foreach ([['支付增强插件', 'payment-enhancer'], ['微信通知插件', 'wechat-notifier'], ['内容 SEO 工具', 'seo-toolkit']] as [$name, $slug]) {
            Plugin::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'version' => '1.0.0',
                'provider' => null,
                'permissions' => ['read_settings'],
                'events' => ['payment.notify.received', 'content.published', 'order.paid'],
                'enabled' => $slug === 'seo-toolkit',
            ]);
        }
    }
}
