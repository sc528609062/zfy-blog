<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CardCode;
use App\Models\Category;
use App\Models\Content;
use App\Models\Comment;
use App\Models\Coupon;
use App\Models\Link;
use App\Models\LinkCheck;
use App\Models\LinkSubmission;
use App\Models\Media;
use App\Models\Order;
use App\Models\PageLayout;
use App\Models\Plugin;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Shipment;
use App\Models\Theme;
use App\Models\User;
use App\Services\ContentMarkdownRenderer;
use App\Services\PackageManifestService;
use App\Services\ThemeManager;
use App\Support\Zfy\AdminRegistry;
use App\Support\Zfy\SettingsRegistry;
use App\Support\Zfy\ThemeRegistry;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(
        private readonly ThemeManager $themes,
        private readonly PackageManifestService $packages,
        private readonly AdminRegistry $admin,
        private readonly SettingsRegistry $settings,
        private readonly ThemeRegistry $themeRegistry,
        private readonly ContentMarkdownRenderer $renderer,
    ) {}

    public function page(Request $request, string $section = 'dashboard')
    {
        $page = $this->admin->pageOrFallback($section);

        return view('admin.shell', [
            'section' => $section,
            'adminMenu' => $this->admin->menuFor($request->user()),
            'currentPage' => $page,
            'settingsSchema' => $this->settings->schema(),
            'themeCapabilities' => $this->themeRegistry->payload(),
            'theme' => $this->themes->active(),
            'stats' => [
                'contents' => Content::count(),
                'orders' => Order::count(),
                'users' => User::count(),
                'themes' => Theme::count(),
                'products' => Product::count(),
                'links' => Link::count(),
            ],
            'contents' => Content::latest()->take(12)->get(),
            'orders' => Order::latest()->take(12)->get(),
            'dataRows' => $this->rowsFor($section),
            'themes' => Theme::all(),
            'plugins' => Plugin::all(),
            'layouts' => PageLayout::latest()->take(10)->get(),
            'themeManifests' => $this->packages->themes(),
            'pluginManifests' => $this->packages->plugins(),
            'editor' => $this->editorPayload($request),
        ]);
    }

    public function activateTheme(Request $request)
    {
        $data = $request->validate(['slug' => ['required', 'string', 'exists:themes,slug']]);
        $this->themes->activate($data['slug']);

        return back()->with('status', '主题已切换为 '.$data['slug']);
    }

    public function saveThemeSetting(Request $request, Theme $theme)
    {
        $data = $request->validate([
            'scope' => ['required', 'string'],
            'key' => ['required', 'string'],
            'value' => ['nullable'],
        ]);

        $theme->settings()->updateOrCreate(
            ['scope' => $data['scope'], 'key' => $data['key']],
            ['value' => ['raw' => $data['value']]]
        );

        return back()->with('status', '主题配置已保存');
    }

    public function savePageLayout(Request $request, PageLayout $layout)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'status' => ['required', 'string', 'in:draft,published'],
            'schema' => ['required', 'json'],
        ]);

        $layout->update([
            'title' => $data['title'],
            'status' => $data['status'],
            'schema' => json_decode($data['schema'], true),
        ]);

        return back()->with('status', '页面构建器配置已保存');
    }

    public function togglePlugin(Plugin $plugin)
    {
        $manifest = $this->packages->plugins()[$plugin->slug] ?? [];
        $errors = $this->packages->validatePluginPayload($manifest);

        if ($errors !== []) {
            return back()->withErrors(['plugin' => implode('；', $errors)]);
        }

        $plugin->update(['enabled' => ! $plugin->enabled]);
        zfy_emit($plugin->enabled ? 'zfy_plugin_activated' : 'zfy_plugin_deactivated', $plugin);

        return back()->with('status', $plugin->name.' 已'.($plugin->enabled ? '启用' : '禁用'));
    }

    public function savePluginSetting(Request $request, Plugin $plugin)
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:120'],
            'value' => ['nullable'],
        ]);

        $plugin->settings()->updateOrCreate(
            ['key' => $data['key']],
            ['value' => ['raw' => $data['value']]]
        );

        return back()->with('status', '插件配置已保存');
    }

    private function rowsFor(string $section)
    {
        return match ($section) {
            'orders' => Order::latest()->take(20)->get()->map(fn (Order $order) => [
                'id' => $order->id,
                'title' => $order->order_no,
                'status' => $order->status,
                'type' => $order->type,
                'created_at' => optional($order->created_at)->format('Y-m-d H:i'),
            ]),
            'users' => User::latest()->take(20)->get()->map(fn (User $user) => [
                'id' => $user->id,
                'title' => $user->name,
                'status' => $user->author_status,
                'type' => $user->email,
                'created_at' => optional($user->created_at)->format('Y-m-d H:i'),
            ]),
            'media' => Media::latest()->take(20)->get()->map(fn (Media $media) => [
                'id' => $media->id,
                'title' => $media->name,
                'status' => $media->disk,
                'type' => $media->type,
                'created_at' => optional($media->created_at)->format('Y-m-d H:i'),
            ]),
            'comments' => Comment::latest()->take(20)->get()->map(fn (Comment $comment) => [
                'id' => $comment->id,
                'title' => str($comment->body)->limit(60)->toString(),
                'status' => $comment->status,
                'type' => 'comment',
                'created_at' => optional($comment->created_at)->format('Y-m-d H:i'),
            ]),
            'products' => Product::latest()->take(20)->get()->map(fn (Product $product) => [
                'id' => $product->id,
                'title' => $product->title,
                'status' => $product->status,
                'type' => $product->type,
                'created_at' => optional($product->created_at)->format('Y-m-d H:i'),
            ]),
            'links' => Link::latest()->take(20)->get()->map(fn (Link $link) => [
                'id' => $link->id,
                'title' => $link->name,
                'status' => $link->status,
                'type' => $link->url,
                'created_at' => optional($link->created_at)->format('Y-m-d H:i'),
            ]),
            'link-submissions' => LinkSubmission::latest()->take(20)->get()->map(fn (LinkSubmission $submission) => [
                'id' => $submission->id,
                'title' => $submission->name,
                'status' => $submission->status,
                'type' => $submission->url,
                'created_at' => optional($submission->created_at)->format('Y-m-d H:i'),
            ]),
            'link-checks' => LinkCheck::latest()->take(20)->get()->map(fn (LinkCheck $check) => [
                'id' => $check->id,
                'title' => $check->message ?: '链接检测',
                'status' => $check->status,
                'type' => $check->http_code,
                'created_at' => optional($check->created_at)->format('Y-m-d H:i'),
            ]),
            'shipments' => Shipment::latest()->take(20)->get()->map(fn (Shipment $shipment) => [
                'id' => $shipment->id,
                'title' => $shipment->tracking_no ?: '待发货',
                'status' => $shipment->status,
                'type' => $shipment->carrier,
                'created_at' => optional($shipment->created_at)->format('Y-m-d H:i'),
            ]),
            'refunds' => Refund::latest()->take(20)->get()->map(fn (Refund $refund) => [
                'id' => $refund->id,
                'title' => $refund->reason ?: '售后申请',
                'status' => $refund->status,
                'type' => $refund->amount,
                'created_at' => optional($refund->created_at)->format('Y-m-d H:i'),
            ]),
            'coupons' => Coupon::latest()->take(20)->get()->map(fn (Coupon $coupon) => [
                'id' => $coupon->id,
                'title' => $coupon->code,
                'status' => $coupon->status,
                'type' => $coupon->type,
                'created_at' => optional($coupon->created_at)->format('Y-m-d H:i'),
            ]),
            'cards' => CardCode::latest()->take(20)->get()->map(fn (CardCode $card) => [
                'id' => $card->id,
                'title' => $card->code_hash,
                'status' => $card->status,
                'type' => 'card',
                'created_at' => optional($card->created_at)->format('Y-m-d H:i'),
            ]),
            default => Content::latest()->take(20)->get()->map(fn (Content $content) => [
                'id' => $content->id,
                'title' => $content->title,
                'status' => $content->status,
                'type' => $content->type,
                'created_at' => optional($content->created_at)->format('Y-m-d H:i'),
                'editable' => true,
                'edit_url' => '/admin/editor?content='.$content->id,
            ]),
        };
    }

    private function editorPayload(Request $request): array
    {
        $toolbar = zfy_apply('zfy_editor_toolbar', $this->defaultEditorToolbar(), $request->user());

        return [
            'toolbar' => is_array($toolbar) ? $toolbar : $this->defaultEditorToolbar(),
            'content_types' => collect(config('zfy.content_types', ['post', 'images', 'files', 'page']))
                ->map(fn (string $type) => [
                    'value' => $type,
                    'label' => match ($type) {
                        'post' => '文章',
                        'images' => '图集',
                        'files' => '资源',
                        'page' => '页面',
                        default => $type,
                    },
                ])
                ->values(),
            'categories' => Category::orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'type']),
            'media' => $this->editorMediaPayload(),
            'can_use_raw_html' => $this->canUseRawHtml($request),
            'default_status' => 'draft',
            'content' => $this->editorContent($request),
        ];
    }

    private function editorContent(Request $request): ?array
    {
        $id = $request->integer('content');

        if (! $id) {
            return null;
        }

        $content = Content::with('tags')->findOrFail($id);
        $user = $request->user();

        abort_unless(
            $content->author_id === $user?->id || $user?->can('manage contents'),
            403
        );

        $renderedHtml = $this->renderer->renderContent($content, true, true);

        return [
            'id' => $content->id,
            'title' => $content->title,
            'slug' => $content->slug,
            'type' => $content->type,
            'status' => $content->status,
            'category_id' => $content->category_id,
            'tags' => $content->tags->pluck('name')->implode(', '),
            'cover_url' => $content->cover_url,
            'excerpt' => $content->excerpt,
            'markdown_cache' => $content->markdown_cache,
            'rendered_html' => $renderedHtml,
            'published_at' => optional($content->published_at)->toISOString(),
            'show_url' => route('contents.show', $content->slug, false),
        ];
    }

    private function canUseRawHtml(Request $request): bool
    {
        $user = $request->user();

        return (bool) ($user?->can('manage system') || $user?->hasAnyRole(['SUPER_ADMIN', 'ADMIN']));
    }

    /**
     * @return array<string, mixed>
     */
    private function editorMediaPayload(): array
    {
        $media = config('zfy.editor.media', []);

        return [
            'disk' => (string) ($media['disk'] ?? 'media'),
            'storageRoot' => (string) ($media['storage_root'] ?? 'media'),
            'defaultDirectory' => (string) ($media['default_directory'] ?? 'editor/images'),
            'libraryPerPage' => (int) ($media['library_per_page'] ?? 24),
            'uploadMaxKb' => (int) ($media['upload_max_kb'] ?? 20480),
            'directories' => collect($media['directories'] ?? [])
                ->map(fn (array $directory) => [
                    'value' => (string) ($directory['value'] ?? ''),
                    'label' => (string) ($directory['label'] ?? ''),
                ])
                ->filter(fn (array $directory) => $directory['value'] !== '' && $directory['label'] !== '')
                ->values()
                ->all(),
        ];
    }

    private function defaultEditorToolbar(): array
    {
        return [
            ['id' => 'undo', 'label' => '撤销', 'icon' => 'Back', 'action' => 'command', 'command' => 'undo', 'group' => 'history'],
            ['id' => 'redo', 'label' => '重做', 'icon' => 'Right', 'action' => 'command', 'command' => 'redo', 'group' => 'history'],
            ['id' => 'bold', 'label' => '加粗', 'icon' => 'EditPen', 'action' => 'wrap', 'prefix' => '**', 'suffix' => '**', 'placeholder' => '加粗文字', 'group' => 'format'],
            ['id' => 'italic', 'label' => '斜体', 'icon' => 'Edit', 'action' => 'wrap', 'prefix' => '*', 'suffix' => '*', 'placeholder' => '斜体文字', 'group' => 'format'],
            ['id' => 'strike', 'label' => '删除线', 'icon' => 'Minus', 'action' => 'wrap', 'prefix' => '~~', 'suffix' => '~~', 'placeholder' => '删除文字', 'group' => 'format'],
            ['id' => 'inline-code', 'label' => '行内代码', 'icon' => 'Tickets', 'action' => 'wrap', 'prefix' => '`', 'suffix' => '`', 'placeholder' => 'code', 'group' => 'format'],
            ['id' => 'heading', 'label' => '标题', 'icon' => 'Menu', 'action' => 'dropdown', 'group' => 'block', 'children' => [
                ['id' => 'h1', 'label' => '一级标题', 'action' => 'linePrefix', 'prefix' => '# ', 'placeholder' => '一级标题'],
                ['id' => 'h2', 'label' => '二级标题', 'action' => 'linePrefix', 'prefix' => '## ', 'placeholder' => '二级标题'],
                ['id' => 'h3', 'label' => '三级标题', 'action' => 'linePrefix', 'prefix' => '### ', 'placeholder' => '三级标题'],
                ['id' => 'h4', 'label' => '四级标题', 'action' => 'linePrefix', 'prefix' => '#### ', 'placeholder' => '四级标题'],
                ['id' => 'h5', 'label' => '五级标题', 'action' => 'linePrefix', 'prefix' => '##### ', 'placeholder' => '五级标题'],
                ['id' => 'h6', 'label' => '六级标题', 'action' => 'linePrefix', 'prefix' => '###### ', 'placeholder' => '六级标题'],
            ]],
            ['id' => 'quote', 'label' => '引用', 'icon' => 'ChatLineSquare', 'action' => 'linePrefix', 'prefix' => '> ', 'placeholder' => '引用内容', 'group' => 'block'],
            ['id' => 'ordered-list', 'label' => '有序列表', 'icon' => 'Sort', 'action' => 'linePrefix', 'prefix' => '1. ', 'placeholder' => '列表项目', 'group' => 'block'],
            ['id' => 'unordered-list', 'label' => '无序列表', 'icon' => 'List', 'action' => 'linePrefix', 'prefix' => '- ', 'placeholder' => '列表项目', 'group' => 'block'],
            ['id' => 'task-list', 'label' => '任务列表', 'icon' => 'Finished', 'action' => 'blockInsert', 'snippet' => "- [ ] 待办事项\n- [x] 已完成事项", 'group' => 'block'],
            ['id' => 'hr', 'label' => '分割线', 'icon' => 'Minus', 'action' => 'blockInsert', 'snippet' => '---', 'group' => 'insert'],
            ['id' => 'link', 'label' => '链接', 'icon' => 'Link', 'action' => 'wrap', 'prefix' => '[', 'suffix' => '](https://example.com)', 'placeholder' => '链接文字', 'group' => 'insert'],
            ['id' => 'image', 'label' => '图片', 'icon' => 'Picture', 'action' => 'blockInsert', 'snippet' => '![图片描述](/assets/zfy/placeholders/blue.svg)', 'group' => 'insert'],
            ['id' => 'table', 'label' => '表格', 'icon' => 'Grid', 'action' => 'blockInsert', 'snippet' => "| 标题 | 内容 |\n| --- | --- |\n| 示例 | 文本 |", 'group' => 'insert'],
            ['id' => 'code-block', 'label' => '代码块', 'icon' => 'DocumentCopy', 'action' => 'blockWrap', 'prefix' => "```\n", 'suffix' => "\n```", 'placeholder' => '代码内容', 'group' => 'insert'],
            ['id' => 'html', 'label' => 'HTML', 'icon' => 'Collection', 'action' => 'blockInsert', 'snippet' => '<div class="zfy-custom-html">HTML 内容</div>', 'requiresRawHtml' => true, 'group' => 'insert'],
            ['id' => 'time', 'label' => '当前时间', 'icon' => 'Timer', 'action' => 'insert', 'snippet' => '{zfy-time label="{date}" /}', 'group' => 'insert'],
            ['id' => 'indent', 'label' => '缩进', 'icon' => 'DArrowRight', 'action' => 'linePrefix', 'prefix' => '    ', 'placeholder' => '缩进内容', 'group' => 'insert'],
            ['id' => 'characters', 'label' => '符号', 'icon' => 'Star', 'action' => 'insert', 'snippet' => '★ ☆ ✓ ✕ → ← ↑ ↓', 'group' => 'insert'],
            ['id' => 'emoji', 'label' => '表情', 'icon' => 'Sunny', 'action' => 'insert', 'snippet' => ':smile: :rocket: :sparkles:', 'group' => 'insert'],
            ['id' => 'shortcodes', 'label' => '组件', 'icon' => 'SetUp', 'action' => 'dropdown', 'group' => 'shortcode', 'children' => [
                ['id' => 'zfy-alert', 'label' => '提示框', 'action' => 'blockWrap', 'prefix' => '{zfy-alert type="info" title="提示"}'."\n", 'suffix' => "\n".'{/zfy-alert}', 'placeholder' => '提示内容'],
                ['id' => 'zfy-callout', 'label' => '标注', 'action' => 'blockWrap', 'prefix' => '{zfy-callout color="#f0ad4e"}'."\n", 'suffix' => "\n".'{/zfy-callout}', 'placeholder' => '标注内容'],
                ['id' => 'zfy-quote', 'label' => '彩色引用', 'action' => 'blockWrap', 'prefix' => '{zfy-quote color="#af870d"}'."\n", 'suffix' => "\n".'{/zfy-quote}', 'placeholder' => '引用内容'],
                ['id' => 'zfy-mtitle', 'label' => '居中标题', 'action' => 'blockInsert', 'snippet' => '{zfy-mtitle title="居中标题" /}'],
                ['id' => 'zfy-card-default', 'label' => '默认卡片', 'action' => 'blockWrap', 'prefix' => '{zfy-card-default title="卡片标题"}'."\n", 'suffix' => "\n".'{/zfy-card-default}', 'placeholder' => '卡片内容'],
                ['id' => 'zfy-card-list', 'label' => '卡片列表', 'action' => 'blockInsert', 'snippet' => "{zfy-card-list}\n{zfy-card-list-item}\n列表一内容\n{/zfy-card-list-item}\n{zfy-card-list-item}\n列表二内容\n{/zfy-card-list-item}\n{/zfy-card-list}"],
                ['id' => 'zfy-card-describe', 'label' => '描述卡片', 'action' => 'blockInsert', 'snippet' => "{zfy-card-describe title=\"卡片描述\"}\n卡片内容\n{/zfy-card-describe}"],
                ['id' => 'zfy-message', 'label' => '消息条', 'action' => 'blockWrap', 'prefix' => '{zfy-message type="warning"}'."\n", 'suffix' => "\n".'{/zfy-message}', 'placeholder' => '消息内容'],
                ['id' => 'zfy-progress', 'label' => '进度条', 'action' => 'blockInsert', 'snippet' => '{zfy-progress value="60" /}'],
                ['id' => 'zfy-collapse', 'label' => '折叠块', 'action' => 'blockInsert', 'snippet' => "{zfy-collapse}\n{zfy-collapse-item label=\"折叠标题一\" open}\n折叠内容一\n{/zfy-collapse-item}\n{zfy-collapse-item label=\"折叠标题二\"}\n折叠内容二\n{/zfy-collapse-item}\n{/zfy-collapse}"],
                ['id' => 'zfy-tabs', 'label' => '标签页', 'action' => 'blockInsert', 'snippet' => "{zfy-tabs}\n{zfy-tabs-pane label=\"标签一\"}\n标签一内容\n{/zfy-tabs-pane}\n{zfy-tabs-pane label=\"标签二\"}\n标签二内容\n{/zfy-tabs-pane}\n{/zfy-tabs}"],
                ['id' => 'zfy-bilibili', 'label' => '哔哩哔哩', 'action' => 'blockInsert', 'snippet' => '{zfy-bilibili title="视频" bvid="BV1xx411c7mD" page="1" /}'],
                ['id' => 'zfy-dplayer', 'label' => '视频播放器', 'action' => 'blockInsert', 'snippet' => '{zfy-dplayer title="视频" url="/video/demo.mp4" /}'],
                ['id' => 'zfy-music-list', 'label' => '网易云列表', 'action' => 'blockInsert', 'snippet' => '{zfy-music-list id="歌单ID" color="#1989fa" /}'],
                ['id' => 'zfy-music', 'label' => '网易云单首', 'action' => 'blockInsert', 'snippet' => '{zfy-music id="歌曲ID" color="#1989fa" /}'],
                ['id' => 'zfy-mp3', 'label' => '音频', 'action' => 'blockInsert', 'snippet' => '{zfy-mp3 title="音频" url="/audio/demo.mp3" /}'],
                ['id' => 'zfy-cloud', 'label' => '网盘', 'action' => 'blockInsert', 'snippet' => '{zfy-cloud title="下载资源" url="https://example.com" /}'],
                ['id' => 'zfy-button', 'label' => '按钮', 'action' => 'blockInsert', 'snippet' => '{zfy-button title="访问链接" url="https://example.com" /}'],
                ['id' => 'zfy-abtn', 'label' => '多彩按钮', 'action' => 'blockInsert', 'snippet' => '{zfy-abtn title="按钮内容" url="https://example.com" color="#ff6800" radius="8px" /}'],
                ['id' => 'zfy-anote', 'label' => '便条按钮', 'action' => 'blockInsert', 'snippet' => '{zfy-anote title="按钮内容" url="https://example.com" type="secondary" /}'],
                ['id' => 'zfy-dotted', 'label' => '彩色虚线', 'action' => 'blockInsert', 'snippet' => '{zfy-dotted startColor="#ff6c6c" endColor="#1989fa" /}'],
                ['id' => 'zfy-timeline', 'label' => '时间线', 'action' => 'blockInsert', 'snippet' => "{zfy-timeline}\n{zfy-timeline-item color=\"#19be6b\"}\n2026-05-13：版本发布\n{/zfy-timeline-item}\n{zfy-timeline-item color=\"#2d8cf0\"}\n继续优化编辑器\n{/zfy-timeline-item}\n{/zfy-timeline}"],
                ['id' => 'zfy-copy', 'label' => '复制块', 'action' => 'blockWrap', 'prefix' => '{zfy-copy title="复制内容"}'."\n", 'suffix' => "\n".'{/zfy-copy}', 'placeholder' => '可复制文本'],
                ['id' => 'zfy-lamp', 'label' => '高亮灯', 'action' => 'blockInsert', 'snippet' => '{zfy-lamp title="灵感提示" /}'],
                ['id' => 'zfy-grid', 'label' => '宫格', 'action' => 'blockInsert', 'snippet' => "{zfy-grid column=\"3\" gap=\"15\"}\n{zfy-grid-item}\n宫格项目一\n{/zfy-grid-item}\n{zfy-grid-item}\n宫格项目二\n{/zfy-grid-item}\n{zfy-grid-item}\n宫格项目三\n{/zfy-grid-item}\n{/zfy-grid}"],
                ['id' => 'zfy-hide', 'label' => '隐藏内容', 'action' => 'blockWrap', 'prefix' => '{zfy-hide title="登录后可见"}'."\n", 'suffix' => "\n".'{/zfy-hide}', 'placeholder' => '隐藏内容'],
            ]],
            ['id' => 'clean', 'label' => '清空', 'icon' => 'Delete', 'action' => 'clean', 'group' => 'actions'],
            ['id' => 'download', 'label' => '下载', 'icon' => 'Download', 'action' => 'download', 'group' => 'actions'],
            ['id' => 'fullscreen', 'label' => '全屏', 'icon' => 'FullScreen', 'action' => 'fullscreen', 'group' => 'actions'],
            ['id' => 'preview', 'label' => '预览', 'icon' => 'View', 'action' => 'preview', 'group' => 'actions'],
        ];
    }
}
