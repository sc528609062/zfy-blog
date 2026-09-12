<?php

namespace App\Providers;

use App\Services\AdminResourceRegistry;
use App\Services\ContentModeration;
use App\Services\CoreBlockRegistry;
use App\Services\Install\InstallationState;
use App\Services\ThemePackageLoader;
use App\Support\Zfy\AdminRegistry;
use App\Support\Zfy\ExtensionManager;
use App\Support\Zfy\ExtensionRegistry;
use App\Support\Zfy\FilterBus;
use App\Support\Zfy\HookBus;
use App\Support\Zfy\SettingsRegistry;
use App\Support\Zfy\ThemeRegistry;
use App\Support\Zfy\UpdateQueueBarrier;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class ZfyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HookBus::class);
        $this->app->singleton(FilterBus::class);
        $this->app->singleton(AdminRegistry::class);
        $this->app->singleton(SettingsRegistry::class);
        $this->app->singleton(ThemeRegistry::class);
        $this->app->singleton(ExtensionManager::class);
        $this->app->singleton(ExtensionRegistry::class);
        $this->app->singleton(UpdateQueueBarrier::class);
    }

    public function boot(): void
    {
        Queue::looping(fn () => ! is_file(storage_path('app/private/updates/writes-paused')));
        Queue::before(fn ($event) => app(UpdateQueueBarrier::class)->acquire($event->job));
        Queue::after(fn ($event) => app(UpdateQueueBarrier::class)->release($event->job));
        Queue::exceptionOccurred(fn ($event) => app(UpdateQueueBarrier::class)->release($event->job));
        $this->app->make(ThemePackageLoader::class)->register();
        $this->registerDefaultAdmin();
        $this->registerDefaultSettings();
        $this->registerDefaultThemeCapabilities();
        $this->app->make(CoreBlockRegistry::class)->register();
        zfy_filter('zfy_content_payload', function (array $attributes, $payload, $request, $content) {
            $text = ($attributes['title'] ?? $content?->title ?? '').' '.($attributes['markdown_cache'] ?? $content?->markdown_cache ?? '').' '.json_encode($attributes['block_json'] ?? []);
            if (app(ContentModeration::class)->check($text, 'markdown_cache') && in_array($attributes['status'] ?? '', ['published', 'scheduled'], true)) {
                $attributes['status'] = 'pending';
            }

            return $attributes;
        }, 1000, 4);

        if ($this->app->make(InstallationState::class)->installed() && Schema::hasTable('plugins')) {
            $this->app->booted(function () {
                $this->app->make(ExtensionManager::class)->boot();
                $this->app->make(ThemePackageLoader::class)->bootActive();
            });
        }
    }

    private function registerDefaultAdmin(): void
    {
        $registry = $this->app->make(AdminRegistry::class);

        foreach ([
            ['key' => 'dashboard', 'label' => '仪表盘', 'icon' => 'Monitor', 'position' => 10],
            ['key' => 'content', 'label' => '内容', 'icon' => 'Document', 'position' => 20],
            ['key' => 'pages', 'label' => '页面', 'icon' => 'Files', 'position' => 30],
            ['key' => 'media', 'label' => '媒体', 'icon' => 'Picture', 'position' => 40],
            ['key' => 'comments', 'label' => '评论', 'icon' => 'ChatDotRound', 'position' => 50],
            ['key' => 'links', 'label' => '链接', 'icon' => 'Link', 'position' => 60],
            ['key' => 'appearance', 'label' => '外观', 'icon' => 'Brush', 'position' => 70],
            ['key' => 'plugins', 'label' => '插件', 'icon' => 'Connection', 'position' => 80],
            ['key' => 'commerce', 'label' => '商城', 'icon' => 'ShoppingCart', 'position' => 90],
            ['key' => 'users', 'label' => '用户', 'icon' => 'User', 'position' => 100],
            ['key' => 'settings', 'label' => '设置', 'icon' => 'Setting', 'position' => 110],
        ] as $group) {
            $registry->group($group);
        }

        foreach ($this->defaultPages() as $page) {
            $resource = match ($page['key']) {
                'links-create' => 'links', 'users-create' => 'users', default => $page['key'],
            };
            if (isset(app(AdminResourceRegistry::class)->definitions()[$resource])) {
                $page = array_replace($page, ['kind' => 'resource', 'status' => 'ready', 'resource' => $resource]);
            }
            $kind = match ($page['key']) {
                'pages' => 'table', 'pages-create' => 'editor', 'media', 'media-upload' => 'media-library',
                'profiles' => 'profile', default => null,
            };
            if ($kind) {
                $page = array_replace($page, ['kind' => $kind, 'status' => 'ready']);
            }
            if ($page['key'] === 'installer') {
                $page = array_replace($page, ['kind' => 'plugin-tools', 'status' => 'ready']);
            }
            if (in_array($page['key'], ['orders', 'refunds', 'commissions', 'withdrawals'], true)) {
                $page = array_replace($page, ['kind' => 'commerce', 'status' => 'ready']);
            }
            $registry->page($page);
        }
    }

    private function registerDefaultSettings(): void
    {
        $settings = $this->app->make(SettingsRegistry::class);

        foreach ([
            ['key' => 'general', 'label' => '常规设置', 'description' => '站点标题、地址和基础显示。', 'position' => 10],
            ['key' => 'writing', 'label' => '撰写设置', 'description' => '内容发布默认值。', 'position' => 20],
            ['key' => 'reading', 'label' => '阅读设置', 'description' => '首页、列表和订阅输出。', 'position' => 30],
            ['key' => 'discussion', 'label' => '讨论设置', 'description' => '评论审核和互动规则。', 'position' => 40],
            ['key' => 'media', 'label' => '媒体设置', 'description' => '上传、尺寸和资源处理。', 'position' => 50],
            ['key' => 'permalink', 'label' => '固定链接', 'description' => 'URL 结构和跳转规则。', 'position' => 60],
            ['key' => 'links', 'label' => '外链设置', 'description' => '链接访问策略。', 'position' => 70],
        ] as $group) {
            $settings->group($group);
        }

        foreach ([
            ['key' => 'site.name', 'group' => 'general', 'label' => '站点名称', 'type' => 'text', 'default' => 'zfy-blog', 'position' => 10],
            ['key' => 'site.description', 'group' => 'general', 'label' => '站点描述', 'type' => 'textarea', 'default' => '', 'rules' => ['nullable', 'string', 'max:300'], 'position' => 11],
            ['key' => 'reading.search_visible', 'group' => 'reading', 'label' => '允许搜索引擎收录', 'type' => 'boolean', 'default' => true, 'position' => 20],
            ['key' => 'site.url', 'group' => 'general', 'label' => '站点地址', 'type' => 'url', 'default' => url('/'), 'position' => 20],
            ['key' => 'content.default_status', 'group' => 'writing', 'label' => '默认发布状态', 'type' => 'select', 'default' => 'draft', 'options' => ['draft' => '草稿', 'published' => '发布'], 'position' => 10],
            ['key' => 'reading.page_size', 'group' => 'reading', 'label' => '每页内容数', 'type' => 'number', 'default' => 12, 'position' => 10],
            ['key' => 'discussion.require_approval', 'group' => 'discussion', 'label' => '评论需要审核', 'type' => 'boolean', 'default' => true, 'position' => 10],
            ['key' => 'discussion.sensitive_words', 'group' => 'discussion', 'label' => '限制词（每行一个）', 'type' => 'textarea', 'default' => '', 'rules' => ['nullable', 'string', 'max:10000'], 'position' => 11],
            ['key' => 'discussion.sensitive_action', 'group' => 'discussion', 'label' => '命中限制词', 'type' => 'select', 'options' => ['review' => '转为待审核', 'reject' => '拒绝提交'], 'default' => 'review', 'rules' => ['required', 'in:review,reject'], 'position' => 12],
            ['key' => 'community.checkin_enabled', 'group' => 'discussion', 'label' => '每日签到', 'type' => 'boolean', 'default' => true, 'position' => 20],
            ['key' => 'community.messages_enabled', 'group' => 'discussion', 'label' => '站内私信', 'type' => 'boolean', 'default' => true, 'position' => 21],
            ['key' => 'community.checkin_points', 'group' => 'discussion', 'label' => '签到积分', 'type' => 'number', 'default' => 5, 'rules' => ['required', 'integer', 'between:0,1000'], 'position' => 30],
            ['key' => 'registration.invite_required', 'group' => 'general', 'label' => '注册需要邀请码', 'type' => 'boolean', 'default' => false, 'position' => 30],
            ['key' => 'media.max_upload_mb', 'group' => 'media', 'label' => '最大上传 MB', 'type' => 'number', 'default' => 20, 'position' => 10],
            ['key' => 'media.iframe_hosts', 'group' => 'media', 'label' => '允许嵌入的域名（逗号分隔）', 'type' => 'text', 'default' => 'player.bilibili.com', 'rules' => ['required', 'string', 'max:2000', 'regex:/^[a-z0-9.-]+(?:,[a-z0-9.-]+)*$/'], 'position' => 20],
            ['key' => 'permalink.content_base', 'group' => 'permalink', 'label' => '内容前缀', 'type' => 'text', 'default' => 'content', 'position' => 10],
            ['key' => 'links.redirect', 'group' => 'links', 'label' => '使用站内跳转', 'type' => 'boolean', 'default' => false, 'position' => 10],
            ['key' => 'links.nofollow', 'group' => 'links', 'label' => '所有友链添加 nofollow', 'type' => 'boolean', 'default' => false, 'position' => 20],
            ['key' => 'links.new_window', 'group' => 'links', 'label' => '所有友链在新窗口打开', 'type' => 'boolean', 'default' => false, 'position' => 30],
        ] as $field) {
            $settings->setting($field);
        }
    }

    private function registerDefaultThemeCapabilities(): void
    {
        $theme = $this->app->make(ThemeRegistry::class);
        $theme->support('menus');
        $theme->support('widgets');
        $theme->support('page-builder');
        $theme->support('commerce');

        $theme->navArea('primary', ['label' => '主导航', 'description' => '桌面端主菜单']);
        $theme->navArea('mobile', ['label' => '移动端导航', 'description' => '移动端抽屉菜单']);
        $theme->navArea('footer', ['label' => '页脚导航', 'description' => '页脚链接']);

        $theme->widgetArea('sidebar', ['label' => '侧边栏', 'description' => '内容页和列表页侧栏']);
        $theme->widgetArea('footer', ['label' => '页脚区域', 'description' => '页脚小工具']);
        $theme->widgetArea('user-center', ['label' => '用户中心', 'description' => '用户中心侧栏']);
    }

    private function defaultPages(): array
    {
        return [
            ['key' => 'points-store', 'label' => '积分商品', 'description' => '管理积分兑换商品', 'group' => 'commerce', 'kind' => 'resource', 'permission' => 'manage commerce', 'status' => 'ready', 'position' => 95],
            ['key' => 'points-exchanges', 'label' => '积分兑换', 'description' => '处理积分兑换发放', 'group' => 'commerce', 'kind' => 'commerce', 'permission' => 'manage commerce', 'status' => 'ready', 'position' => 96],
            ['key' => 'dashboard', 'label' => '首页', 'description' => '站点运营、内容和商城概览', 'group' => 'dashboard', 'kind' => 'dashboard', 'status' => 'ready', 'position' => 10],
            ['key' => 'updater', 'label' => '更新', 'description' => '版本、数据库更新和备份', 'group' => 'dashboard', 'kind' => 'maintenance', 'permission' => 'manage system', 'status' => 'ready', 'position' => 20],
            ['key' => 'contents', 'label' => '所有文章', 'description' => '管理网站文章和资源内容', 'group' => 'content', 'kind' => 'table', 'permission' => 'manage contents', 'status' => 'ready', 'position' => 10],
            ['key' => 'editor', 'label' => '写文章', 'description' => '新增文章、图集或资源内容', 'group' => 'content', 'kind' => 'editor', 'permission' => 'publish contents', 'status' => 'ready', 'position' => 20],
            ['key' => 'categories', 'label' => '分类目录', 'description' => '管理内容分类层级', 'group' => 'content', 'kind' => 'placeholder', 'permission' => 'manage contents', 'position' => 30],
            ['key' => 'tags', 'label' => '标签', 'description' => '管理内容标签', 'group' => 'content', 'kind' => 'placeholder', 'permission' => 'manage contents', 'position' => 40],
            ['key' => 'topics', 'label' => '专题', 'description' => '管理专题聚合页', 'group' => 'content', 'kind' => 'placeholder', 'permission' => 'manage contents', 'position' => 50],
            ['key' => 'pages', 'label' => '所有页面', 'description' => '管理关于、联系等独立页面', 'group' => 'pages', 'kind' => 'placeholder', 'permission' => 'manage contents', 'position' => 10],
            ['key' => 'pages-create', 'label' => '添加页面', 'description' => '新增独立页面', 'group' => 'pages', 'kind' => 'placeholder', 'permission' => 'manage contents', 'position' => 20],
            ['key' => 'media', 'label' => '媒体库', 'description' => '管理上传图片、视频和附件', 'group' => 'media', 'kind' => 'table', 'permission' => 'manage contents', 'status' => 'ready', 'position' => 10],
            ['key' => 'media-upload', 'label' => '添加媒体', 'description' => '上传新媒体文件', 'group' => 'media', 'kind' => 'placeholder', 'permission' => 'manage contents', 'position' => 20],
            ['key' => 'comments', 'label' => '评论', 'description' => '审核和管理用户评论', 'group' => 'comments', 'kind' => 'table', 'permission' => 'manage contents', 'status' => 'ready', 'position' => 10],
            ['key' => 'links', 'label' => '全部链接', 'description' => '管理友情链接和推荐入口', 'group' => 'links', 'kind' => 'table', 'permission' => 'manage links', 'status' => 'ready', 'position' => 10],
            ['key' => 'links-create', 'label' => '添加链接', 'description' => '新增友情链接', 'group' => 'links', 'kind' => 'placeholder', 'permission' => 'manage links', 'position' => 20],
            ['key' => 'link-categories', 'label' => '链接分类', 'description' => '管理链接分类', 'group' => 'links', 'kind' => 'placeholder', 'permission' => 'manage links', 'position' => 30],
            ['key' => 'link-submissions', 'label' => '提交审核', 'description' => '处理前台友链提交', 'group' => 'links', 'kind' => 'table', 'permission' => 'manage links', 'status' => 'ready', 'position' => 40],
            ['key' => 'link-redirects', 'label' => '跳转设置', 'description' => '外链访问策略', 'group' => 'links', 'kind' => 'settings', 'permission' => 'manage links', 'status' => 'ready', 'position' => 50],
            ['key' => 'link-checks', 'label' => '链接检测', 'description' => '链接可用性检测记录', 'group' => 'links', 'kind' => 'link-checks', 'permission' => 'manage links', 'status' => 'ready', 'position' => 60],
            ['key' => 'themes', 'label' => '主题', 'description' => '切换和管理主题', 'group' => 'appearance', 'kind' => 'themes', 'permission' => 'manage themes', 'status' => 'ready', 'position' => 10],
            ['key' => 'menus', 'label' => '菜单', 'description' => '管理导航菜单和位置', 'group' => 'appearance', 'kind' => 'placeholder', 'permission' => 'manage themes', 'position' => 20],
            ['key' => 'widgets', 'label' => '小工具', 'description' => '管理主题小工具区域', 'group' => 'appearance', 'kind' => 'placeholder', 'permission' => 'manage themes', 'position' => 30],
            ['key' => 'page-builder', 'label' => '页面构建器', 'description' => '管理可视化页面布局', 'group' => 'appearance', 'kind' => 'builder', 'permission' => 'manage themes', 'status' => 'ready', 'position' => 40],
            ['key' => 'plugins', 'label' => '已安装', 'description' => '管理已安装插件', 'group' => 'plugins', 'kind' => 'plugins', 'permission' => 'manage plugins', 'status' => 'ready', 'position' => 10],
            ['key' => 'installer', 'label' => '安装插件', 'description' => '安装本地插件包', 'group' => 'plugins', 'kind' => 'placeholder', 'permission' => 'manage plugins', 'position' => 20],
            ['key' => 'plugin-settings', 'label' => '插件设置', 'description' => '统一管理插件设置', 'group' => 'plugins', 'kind' => 'settings', 'permission' => 'manage plugins', 'status' => 'ready', 'position' => 30],
            ['key' => 'products', 'label' => '商品', 'description' => '管理商城商品和内容关联', 'group' => 'commerce', 'kind' => 'table', 'permission' => 'manage commerce', 'status' => 'ready', 'position' => 10],
            ['key' => 'orders', 'label' => '订单', 'description' => '查看订单和支付状态', 'group' => 'commerce', 'kind' => 'table', 'permission' => 'manage commerce', 'status' => 'ready', 'position' => 20],
            ['key' => 'shipments', 'label' => '发货', 'description' => '处理实物商品发货', 'group' => 'commerce', 'kind' => 'table', 'permission' => 'manage commerce', 'status' => 'ready', 'position' => 30],
            ['key' => 'refunds', 'label' => '售后', 'description' => '处理退款和售后申请', 'group' => 'commerce', 'kind' => 'table', 'permission' => 'manage commerce', 'status' => 'ready', 'position' => 40],
            ['key' => 'coupons', 'label' => '优惠码', 'description' => '管理优惠券和兑换码', 'group' => 'commerce', 'kind' => 'table', 'permission' => 'manage commerce', 'status' => 'ready', 'position' => 50],
            ['key' => 'cards', 'label' => '卡密', 'description' => '管理虚拟商品卡密', 'group' => 'commerce', 'kind' => 'table', 'permission' => 'manage commerce', 'status' => 'ready', 'position' => 60],
            ['key' => 'commissions', 'label' => '分成', 'description' => '查看作者和分销分成', 'group' => 'commerce', 'kind' => 'placeholder', 'permission' => 'manage commerce', 'position' => 70],
            ['key' => 'settlement-rules', 'label' => '结算规则', 'group' => 'commerce', 'kind' => 'resource', 'permission' => 'manage commerce', 'position' => 71],
            ['key' => 'shipping-templates', 'label' => '运费模板', 'group' => 'commerce', 'kind' => 'resource', 'permission' => 'manage commerce', 'position' => 72],
            ['key' => 'withdrawals', 'label' => '提现', 'description' => '处理用户提现申请', 'group' => 'commerce', 'kind' => 'placeholder', 'permission' => 'manage commerce', 'position' => 80],
            ['key' => 'vip-settings', 'label' => '会员', 'description' => '维护会员等级和权益', 'group' => 'commerce', 'kind' => 'placeholder', 'permission' => 'manage commerce', 'position' => 90],
            ['key' => 'users', 'label' => '所有用户', 'description' => '管理注册用户', 'group' => 'users', 'kind' => 'table', 'permission' => 'manage users', 'status' => 'ready', 'position' => 10],
            ['key' => 'users-create', 'label' => '添加用户', 'description' => '创建新用户', 'group' => 'users', 'kind' => 'placeholder', 'permission' => 'manage users', 'position' => 20],
            ['key' => 'profiles', 'label' => '个人资料', 'description' => '编辑当前管理员资料', 'group' => 'users', 'kind' => 'placeholder', 'position' => 30],
            ['key' => 'invite-codes', 'label' => '邀请码', 'description' => '管理用户注册邀请码', 'group' => 'users', 'kind' => 'placeholder', 'permission' => 'manage users', 'position' => 40],
            ['key' => 'verification', 'label' => '身份认证', 'description' => '处理用户认证申请', 'group' => 'users', 'kind' => 'placeholder', 'permission' => 'manage users', 'position' => 50],
            ['key' => 'ban-appeals', 'label' => '举报/申诉', 'description' => '处理举报和封禁申诉', 'group' => 'users', 'kind' => 'placeholder', 'permission' => 'manage users', 'position' => 60],
            ['key' => 'private-messages', 'label' => '私信审核', 'group' => 'users', 'kind' => 'resource', 'permission' => 'manage users', 'position' => 61],
            ['key' => 'roles', 'label' => '角色权限', 'group' => 'users', 'kind' => 'resource', 'permission' => 'manage system', 'position' => 62],
            ['key' => 'settings-general', 'label' => '常规', 'description' => '站点标题、地址和基础显示', 'group' => 'settings', 'kind' => 'settings', 'permission' => 'manage system', 'status' => 'ready', 'position' => 10],
            ['key' => 'settings-writing', 'label' => '撰写', 'description' => '内容发布相关设置', 'group' => 'settings', 'kind' => 'settings', 'permission' => 'manage system', 'status' => 'ready', 'position' => 20],
            ['key' => 'settings-reading', 'label' => '阅读', 'description' => '首页显示和列表设置', 'group' => 'settings', 'kind' => 'settings', 'permission' => 'manage system', 'status' => 'ready', 'position' => 30],
            ['key' => 'settings-discussion', 'label' => '讨论', 'description' => '评论和互动规则', 'group' => 'settings', 'kind' => 'settings', 'permission' => 'manage system', 'status' => 'ready', 'position' => 40],
            ['key' => 'settings-media', 'label' => '媒体', 'description' => '图片尺寸和上传设置', 'group' => 'settings', 'kind' => 'settings', 'permission' => 'manage system', 'status' => 'ready', 'position' => 50],
            ['key' => 'settings-permalinks', 'label' => '固定链接', 'description' => 'URL 结构设置', 'group' => 'settings', 'kind' => 'settings', 'permission' => 'manage system', 'status' => 'ready', 'position' => 60],
        ];
    }
}
