<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\CardCode;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Content;
use App\Models\Coupon;
use App\Models\Link;
use App\Models\LinkCategory;
use App\Models\Media;
use App\Models\Menu;
use App\Models\Order;
use App\Models\PointsStoreItem;
use App\Models\Product;
use App\Models\Tag;
use App\Models\Topic;
use App\Models\User;
use App\Models\VipLevel;
use App\Models\Widget;
use App\Services\ContentMarkdownRenderer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CmsDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $authors = collect([
                ['demo-editor', '星辰编辑部', '专注建站实践、内容创作与资源整理。'],
                ['demo-designer', '林间设计', '分享界面设计、摄影与视觉素材。'],
                ['demo-reader', '示例读者', '热爱学习与分享的社区成员。'],
            ])->map(function ($profile, $index) {
                [$username, $name, $bio] = $profile;
                $user = User::firstOrCreate(['email' => $username.'@example.invalid'], [
                    'username' => User::where('username', $username)->exists() ? $username.'-'.Str::lower(Str::random(6)) : $username,
                    'name' => $name, 'bio' => $bio, 'password' => Str::random(64),
                    'avatar_url' => '/theme-assets/a-avatar.png', 'is_author' => $index < 2,
                    'author_status' => $index < 2 ? 'approved' : 'none', 'meta' => ['demo' => true],
                ]);
                if ($user->wasRecentlyCreated) {
                    $user->assignRole('USER');
                }
                $user->wallet()->firstOrCreate([], ['balance' => 0]);
                $user->pointsAccount()->firstOrCreate([], ['points' => 0]);

                return $user;
            });
            $categories = collect([
                ['demo-guides', '建站教程', 'post'], ['demo-design', '设计灵感', 'images'],
                ['demo-resources', '资源下载', 'files'], ['demo-news', '社区动态', 'post'],
            ])->map(fn ($row, $index) => Category::firstOrCreate(['slug' => $row[0]], ['name' => $row[1], 'type' => $row[2], 'sort_order' => $index, 'description' => $row[1].'精选内容']));
            $tags = collect([['demo', '演示'], ['demo-laravel', 'Laravel'], ['demo-design', '设计'], ['demo-free', '免费资源']])->map(fn ($row) => Tag::firstOrCreate(['slug' => $row[0]], ['name' => $row[1], 'color' => '#21886e']));
            $definitions = [
                ['post', '从零搭建内容网站：栏目与导航规划', '梳理内容类型、栏目层级和导航，建立清晰的站点结构。', 0],
                ['post', 'Markdown 排版实践：让长文更易读', '标题、列表、引用和代码块的组合示例。', 0],
                ['post', 'Laravel 内容发布流程与权限设计', '草稿、审核与公开发布的完整流程。', 0],
                ['post', '网站图片优化与媒体库整理', '从封面比例到图片命名，整理常用的素材管理方法。', 0],
                ['post', '内容社区每周精选：创作与分享', '本周精选教程、设计灵感和资源下载。', 3],
                ['post', '会员内容的定价与权益说明', '介绍付费内容、会员折扣和购买记录。', 3],
                ['images', '界面灵感图集：轻量内容社区', '一组用于演示图集排版的界面与素材。', 1],
                ['images', '创作者作品集：封面与视觉语言', '封面构图、颜色搭配和信息层级参考。', 1],
                ['images', '设计素材集：卡片布局与留白', '观察不同卡片尺寸与页面密度的关系。', 1],
                ['images', '资源展示图集：多种展示比例', '用于查看列表缩略图与详情图集的展示效果。', 1],
                ['files', '免费建站检查清单', '包含栏目、发布、权限和备份检查项的文本文件。', 2],
                ['files', '内容创作模板套装', '文章结构、选题记录与发布检查清单。', 2],
                ['files', '网站运营记录表', '用于整理内容更新计划和运营记录。', 2],
                ['files', '设计项目交付清单', '演示会员免费资源与普通用户购买流程。', 2],
            ];
            $contents = collect();
            foreach ($definitions as $index => [$type, $title, $excerpt, $categoryIndex]) {
                $cover = '/theme-assets/'.($type === 'images' ? 'c-card-'.($index % 8 + 1).'.png' : 'b-product-'.($index % 6 + 1).'.png');
                $body = '# '.$title."\n\n".$excerpt."\n\n## 内容概览\n\n这是用于预览站点效果的演示内容，可在后台编辑或移除。\n\n- 整理清晰的内容结构\n- 检查发布状态和访问权限\n- 保存素材与变更记录\n\n## 实践步骤\n\n1. 明确本次内容的读者与目标。\n2. 选择对应栏目，填写标题、摘要和正文。\n3. 检查预览效果，再提交审核或发布。\n\n> 良好的内容组织有助于读者快速找到需要的信息。\n\n";
                if ($type === 'images') {
                    $body .= '![图集封面]('.$cover.")\n\n![图集详情](/theme-assets/c-card-".(($index + 2) % 8 + 1).".png)\n";
                }
                if ($type === 'files') {
                    $body .= "## 文件说明\n\n附件为站点自带的演示文本，用于验证下载流程，不包含第三方商业素材。\n";
                }
                $content = Content::withTrashed()->firstOrCreate(['slug' => 'demo-content-'.($index + 1)], [
                    'author_id' => $authors[$type === 'images' ? 1 : 0]->id, 'category_id' => $categories[$categoryIndex]->id,
                    'type' => $type, 'status' => 'published', 'title' => $title, 'excerpt' => $excerpt,
                    'cover_url' => $cover, 'markdown_cache' => $body, 'rendered_html' => app(ContentMarkdownRenderer::class)->render($body),
                    'pricing' => ['price' => $type === 'files' && $index > 10 ? 12 + $index : 0],
                    'access_rules' => ['vip_free' => $index === 13], 'published_at' => now()->subHours($index * 8), 'seo' => ['demo' => true],
                ]);
                if ($content->trashed()) {
                    continue;
                }
                if ($content->wasRecentlyCreated) {
                    $content->tags()->sync([$tags[0]->id, $tags[$type === 'images' ? 2 : 1]->id]);
                }
                $contents->push($content);
                if ($type === 'files') {
                    $path = 'demo/downloads/checklist-'.($index + 1).'.txt';
                    if (! Storage::disk('local')->exists($path)) {
                        Storage::disk('local')->put($path, $body);
                    }
                    $media = Media::firstOrCreate(['disk' => 'local', 'path' => $path], ['user_id' => $authors[0]->id, 'name' => $title.'.txt', 'type' => 'file', 'mime' => 'text/plain', 'size' => strlen($body), 'metadata' => ['demo' => true]]);
                    Attachment::firstOrCreate(['content_id' => $content->id, 'media_id' => $media->id], ['role' => 'download', 'meta' => ['demo' => true]]);
                }
            }
            foreach ([['about-zfy-blog', '关于本站', '这里是一个关注内容创作、技术分享与资源交流的网站。'], ['contact', '联系与反馈', '你可以通过内容评论提交建议，或登录用户中心反馈问题。']] as [$slug, $title, $body]) {
                Content::withTrashed()->firstOrCreate(['slug' => $slug], ['author_id' => $authors[0]->id, 'type' => 'page', 'status' => 'published', 'title' => $title, 'excerpt' => $body, 'markdown_cache' => $body, 'rendered_html' => app(ContentMarkdownRenderer::class)->render($body), 'published_at' => now(), 'seo' => ['demo' => true]]);
            }
            Content::withTrashed()->firstOrCreate(['slug' => 'demo-review-draft'], ['author_id' => $authors[0]->id, 'type' => 'post', 'status' => 'pending', 'title' => '待审核：创作者投稿示例', 'markdown_cache' => '这篇内容用于演示后台审核流程。']);
            foreach ([['demo-getting-started', '建站入门', $contents->take(6)], ['demo-creative', '创意资源合集', $contents->slice(6)]] as [$slug, $name, $items]) {
                $topic = Topic::firstOrCreate(['slug' => $slug], ['name' => $name, 'description' => $name.'精选内容', 'status' => 'published']);
                if ($topic->wasRecentlyCreated) {
                    $topic->contents()->sync($items->pluck('id')->all());
                }
            }
            foreach ($contents->take(5) as $content) {
                $comment = Comment::where('content_id', $content->id)->where('user_id', $authors[2]->id)->where('meta->demo', true)->first();
                if (! $comment) {
                    Comment::create(['content_id' => $content->id, 'user_id' => $authors[2]->id, 'body' => '内容结构很清晰，期待更多相关的实践分享。', 'status' => 'approved', 'meta' => ['demo' => true]]);
                    $content->update(['comment_count' => $content->comments()->where('status', 'approved')->count()]);
                }
            }
            foreach ([['demo-vip', '标准会员', 1, 19, 169, 80], ['demo-svip', '高级会员', 2, 39, 299, 60]] as [$slug, $name, $level, $monthly, $yearly, $discount]) {
                VipLevel::firstOrCreate(['slug' => $slug], ['name' => $name, 'level' => $level, 'price_monthly' => $monthly, 'price_yearly' => $yearly, 'discount_percent' => $discount, 'benefits' => ['会员标记资源免费', '付费内容按 '.$discount.'% 计价', '购买记录与下载管理']]);
            }
            foreach ([['demo-template', '内容创作模板套装', 'digital', 29], ['demo-card', '示例授权码', 'card', 9], ['demo-notebook', '创作笔记本（演示）', 'physical', 39]] as [$slug, $title, $type, $price]) {
                $product = Product::withTrashed()->firstOrCreate(['slug' => $slug], ['title' => $title, 'type' => $type, 'status' => 'published', 'price' => $price, 'content_id' => $type === 'digital' ? $contents->firstWhere('slug', 'demo-content-12')?->id : null, 'stock_strategy' => 'limited', 'metadata' => ['demo' => true]]);
                if ($product->trashed()) {
                    continue;
                }
                if ($type === 'physical' && data_get($product->metadata, 'demo')) {
                    foreach ([['DEMO-NOTE-GREEN', '松绿', '39.00'], ['DEMO-NOTE-GRAY', '浅灰', '42.00']] as [$sku, $color, $variantPrice]) {
                        $product->variants()->firstOrCreate(['sku' => $sku], ['title' => $color, 'price' => $variantPrice, 'stock' => 15, 'status' => 'active', 'attributes' => ['color' => $color]]);
                    }
                }
                if (! DB::table('stock_items')->where('product_id', $product->id)->whereNull('product_variant_id')->exists()) {
                    DB::table('stock_items')->insert(['product_id' => $product->id, 'type' => 'inventory', 'quantity' => $type === 'card' ? 5 : 30, 'created_at' => now(), 'updated_at' => now()]);
                }
                if ($type === 'card') {
                    for ($index = 1; $index <= 5; $index++) {
                        $code = 'DEMO-NOT-A-REAL-LICENSE-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT);
                        CardCode::firstOrCreate(['code_hash' => hash('sha256', $code)], ['product_id' => $product->id, 'code_payload' => $code, 'status' => 'available', 'metadata' => ['demo' => true]]);
                    }
                }
            }
            PointsStoreItem::firstOrCreate(['slug' => 'demo-checklist'], ['title' => '创作资料包（演示）', 'points_price' => 50, 'stock' => 20, 'status' => 'active']);
            Coupon::firstOrCreate(['code' => 'DEMO10'], ['type' => 'percent', 'amount' => 10, 'usage_limit' => 100, 'status' => 'active', 'starts_at' => now()->subDay(), 'expires_at' => now()->addMonth()]);
            $category = LinkCategory::firstOrCreate(['slug' => 'demo-tools'], ['name' => '开发与创作']);
            foreach ([['Laravel', 'https://laravel.com'], ['WordPress', 'https://wordpress.org'], ['Vue', 'https://vuejs.org']] as [$name, $url]) {
                Link::withTrashed()->firstOrCreate(['url' => $url], ['link_category_id' => $category->id, 'name' => $name, 'status' => 'active', 'target' => '_blank', 'nofollow' => false]);
            }
            $nav = ['/' => '首页', '/posts' => '文章', '/images' => '图集', '/files' => '资源', '/shop' => '商城', '/topic/demo-getting-started' => '专题', '/vip' => '会员'];
            foreach (['primary' => $nav, 'mobile' => $nav, 'footer' => ['/p/about-zfy-blog' => '关于本站', '/p/contact' => '联系反馈', '/links' => '友情链接']] as $location => $items) {
                $menu = Menu::firstOrCreate(['location' => $location], ['name' => '示例'.($location === 'footer' ? '页脚' : '导航')]);
                if ($menu->wasRecentlyCreated) {
                    foreach ($items as $url => $title) {
                        $menu->items()->create(['title' => $title, 'url' => $url, 'sort_order' => array_search($url, array_keys($items))]);
                    }
                }
            }
            Widget::firstOrCreate(['title' => '最新内容', 'region' => 'sidebar'], ['type' => 'recent-posts', 'enabled' => true, 'sort_order' => 10]);
            if (! DB::table('notifications')->where('type', 'demo-welcome')->exists()) {
                DB::table('notifications')->insert(['type' => 'demo-welcome', 'title' => '欢迎来到内容社区', 'body' => '本站已上线文章、图集、资源与会员内容，欢迎浏览并参与讨论。', 'created_at' => now(), 'updated_at' => now()]);
            }
            $order = Order::firstOrCreate(['order_no' => 'ZF-DEMO-PENDING-001'], ['user_id' => $authors[2]->id, 'type' => 'content', 'status' => 'pending', 'pay_channel' => 'balance', 'total_amount' => 23, 'paid_amount' => 0, 'expires_at' => now()->addDay(), 'meta' => ['demo' => true]]);
            if ($order->wasRecentlyCreated) {
                $order->items()->create(['item_type' => 'content', 'item_id' => $contents->firstWhere('slug', 'demo-content-12')?->id, 'title' => '演示待支付订单', 'quantity' => 1, 'unit_price' => 23, 'meta' => ['demo' => true]]);
            }
        });
    }
}
