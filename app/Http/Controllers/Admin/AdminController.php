<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CardCode;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Content;
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
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $data = $this->pageData($request, $section);
        $payload = $this->adminPayload($data);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'payload' => $payload,
            ]);
        }

        return view('admin.shell', [
            ...$data,
            'payload' => $payload,
        ]);
    }

    private function pageData(Request $request, string $section): array
    {
        $page = $this->admin->pageOrFallback($section);

        return [
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
            'contents' => $this->contentRows(Content::with($this->contentListRelations())->latest()->take(12)->get()),
            'orders' => Order::latest()->take(12)->get(),
            'dataRows' => $this->rowsFor($section),
            'themes' => Theme::all(),
            'plugins' => Plugin::all(),
            'layouts' => PageLayout::latest()->take(10)->get(),
            'themeManifests' => $this->packages->themes(),
            'pluginManifests' => $this->packages->plugins(),
            'editor' => $this->editorPayload($request),
        ];
    }

    private function adminPayload(array $data): array
    {
        $section = $data['section'] ?? 'dashboard';

        return [
            'section' => $section,
            'csrf' => csrf_token(),
            'today' => now()->format('Y-m-d'),
            'current_user' => auth()->user()?->only(['id', 'name', 'username', 'email', 'avatar_url']),
            'admin_menu' => $data['adminMenu'] ?? [],
            'current_page' => $data['currentPage'] ?? null,
            'settings_schema' => $data['settingsSchema'] ?? [],
            'theme_capabilities' => $data['themeCapabilities'] ?? [],
            'stats' => $data['stats'] ?? [],
            'contents' => collect($data['contents'] ?? [])->values(),
            'orders' => collect($data['orders'] ?? [])->map(fn ($order) => [
                'id' => $order->id,
                'title' => $order->order_no,
                'status' => $order->status,
                'type' => $order->pay_channel,
                'created_at' => optional($order->created_at)->format('Y-m-d H:i'),
            ])->values(),
            'data_rows' => collect($data['dataRows'] ?? [])->values(),
            'themes' => collect($data['themes'] ?? [])->map(fn ($themeItem) => [
                'id' => $themeItem->id,
                'name' => $themeItem->name,
                'slug' => $themeItem->slug,
                'version' => $themeItem->version,
                'preview' => $themeItem->preview,
                'is_active' => (bool) $themeItem->is_active,
                'settings_url' => route('admin.themes.settings', $themeItem, false),
            ])->values(),
            'plugins' => collect($data['plugins'] ?? [])->map(fn ($plugin) => [
                'id' => $plugin->id,
                'name' => $plugin->name,
                'slug' => $plugin->slug,
                'version' => $plugin->version,
                'enabled' => (bool) $plugin->enabled,
                'permissions' => $plugin->permissions ?? [],
                'toggle_url' => route('admin.plugins.toggle', $plugin, false),
                'settings_url' => route('admin.plugins.settings', $plugin, false),
            ])->values(),
            'layouts' => collect($data['layouts'] ?? [])->map(fn ($layout) => [
                'id' => $layout->id,
                'title' => $layout->title,
                'status' => $layout->status,
                'schema' => json_encode($layout->schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                'save_url' => route('admin.page-builder.save', $layout, false),
            ])->values(),
            'editor' => $data['editor'] ?? [],
            'theme_manifests' => $data['themeManifests'] ?? [],
            'plugin_manifests' => $data['pluginManifests'] ?? [],
            'routes' => [
                'theme_activate' => route('admin.themes.activate', [], false),
                'content_store' => route('admin.contents.store', [], false),
                'content_update' => '/admin/contents/__CONTENT__',
                'content_settings' => '/admin/contents/__CONTENT__/settings',
                'content_status' => '/admin/contents/__CONTENT__/status',
                'content_destroy' => '/admin/contents/__CONTENT__',
                'content_preview' => route('admin.contents.preview', [], false),
                'media_library' => route('admin.media.library', [], false),
                'media_upload' => route('admin.media.upload', [], false),
                'media_destroy' => '/admin/media/__MEDIA__',
            ],
        ];
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
        $this->themes->forgetActiveCache();

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
            'contents' => $this->contentRows(Content::with($this->contentListRelations())->latest()->take(20)->get()),
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
            default => $this->contentRows(Content::with($this->contentListRelations())->latest()->take(20)->get()),
        };
    }

    private function contentListRelations(): array
    {
        return [
            'author:id,name,username,avatar_url',
            'category:id,name,slug',
            'tags:id,name,slug,color',
        ];
    }

    private function contentRows(EloquentCollection $contents)
    {
        $purchaseCounts = $this->contentPurchaseCounts($contents->pluck('id')->all());

        return $contents->map(fn (Content $content) => $this->contentRow($content, $purchaseCounts));
    }

    private function contentPurchaseCounts(array $contentIds): array
    {
        if ($contentIds === []) {
            return [];
        }

        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'paid')
            ->where('order_items.item_type', 'content')
            ->whereIn('order_items.item_id', $contentIds)
            ->groupBy('order_items.item_id')
            ->selectRaw('order_items.item_id, SUM(order_items.quantity) as purchases')
            ->pluck('purchases', 'item_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    private function contentRow(Content $content, array $purchaseCounts = []): array
    {
        $authorName = $content->author?->name ?: $content->author?->username;
        $favoriteCount = (int) data_get($content->meta, 'favorite_count', 0);
        $purchaseCount = (int) ($purchaseCounts[$content->id] ?? 0);
        $topicName = $this->contentTopicName($content);

        return [
            'id' => $content->id,
            'title' => $content->title,
            'status' => $content->status,
            'status_label' => $this->contentStatusLabel($content->status),
            'status_tag_type' => match ($content->status) {
                'published' => 'success',
                'draft', 'pending' => 'warning',
                default => 'info',
            },
            'type' => $content->type,
            'type_label' => $this->contentTypeLabel($content->type),
            'cover_url' => $content->cover_url ?: '/assets/zfy/placeholders/cover-blue.svg',
            'cover_url_raw' => $content->cover_url,
            'excerpt' => str($content->excerpt ?: '暂无摘要')->limit(96)->toString(),
            'excerpt_raw' => $content->excerpt,
            'author_name' => $authorName ?: '未设置作者',
            'author_avatar' => $content->author?->avatar_url ?: '/assets/zfy/placeholders/avatar.svg',
            'category_id' => $content->category_id,
            'category_name' => $content->category?->name ?: '未分类',
            'topic_name' => $topicName,
            'tags' => $content->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'color' => $tag->color,
            ])->values(),
            'tags_text' => $content->tags->pluck('name')->implode(', '),
            'view_count' => $content->view_count,
            'comment_count' => $content->comment_count,
            'like_count' => $content->like_count,
            'favorite_count' => $favoriteCount,
            'download_count' => $content->download_count,
            'purchase_count' => $purchaseCount,
            'metrics' => [
                ['key' => 'view_count', 'label' => '阅读', 'value' => $content->view_count],
                ['key' => 'comment_count', 'label' => '评论', 'value' => $content->comment_count],
                ['key' => 'like_count', 'label' => '点赞', 'value' => $content->like_count],
                ['key' => 'favorite_count', 'label' => '收藏', 'value' => $favoriteCount],
                ['key' => 'download_count', 'label' => '下载', 'value' => $content->download_count],
                ['key' => 'purchase_count', 'label' => '购买', 'value' => $purchaseCount],
            ],
            'published_at' => optional($content->published_at)->format('Y-m-d H:i'),
            'created_at' => optional($content->created_at)->format('Y-m-d H:i'),
            'editable' => true,
            'edit_url' => '/admin/editor?content='.$content->id,
            'settings_url' => '/admin/editor?content='.$content->id.'&panel=settings',
        ];
    }

    private function contentTopicName(Content $content): string
    {
        $topic = data_get($content->block_json, 'topic')
            ?? data_get($content->block_json, 'topics.0')
            ?? data_get($content->seo, 'topic')
            ?? data_get($content->seo, 'topics.0');

        if (is_array($topic)) {
            $topic = $topic['name'] ?? $topic['title'] ?? $topic['label'] ?? null;
        }

        $topic = trim((string) $topic);

        return $topic !== '' ? $topic : '未归入专题';
    }

    private function contentStatusLabel(?string $status): string
    {
        return match ($status) {
            'published' => '已发布',
            'draft' => '草稿',
            'pending' => '待审核',
            'archived' => '已归档',
            default => $status ?: '未知',
        };
    }

    private function contentTypeLabel(?string $type): string
    {
        return match ($type) {
            'post' => '文章',
            'images' => '图集',
            'files' => '资源',
            'page' => '页面',
            default => $type ?: '内容',
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
            ['id' => 'quote', 'label' => '彩色引用', 'icon' => 'ChatLineSquare', 'action' => 'blockWrap', 'prefix' => '{zfy-quote color="#af870d"}'."\n", 'suffix' => "\n".'{/zfy-quote}', 'placeholder' => '引用内容', 'group' => 'block'],
            ['id' => 'ordered-list', 'label' => '有序列表', 'icon' => 'Sort', 'action' => 'linePrefix', 'prefix' => '1. ', 'placeholder' => '列表项目', 'group' => 'block'],
            ['id' => 'unordered-list', 'label' => '无序列表', 'icon' => 'List', 'action' => 'linePrefix', 'prefix' => '- ', 'placeholder' => '列表项目', 'group' => 'block'],
            ['id' => 'task-list', 'label' => '任务列表', 'icon' => 'Finished', 'action' => 'blockInsert', 'snippet' => "- [ ] 待办事项\n- [x] 已完成事项", 'group' => 'block'],
            ['id' => 'zfy-card-list', 'label' => '卡片列表', 'icon' => 'Tickets', 'action' => 'blockInsert', 'snippet' => "{zfy-card-list}\n{zfy-card-list-item}\n列表一内容\n{/zfy-card-list-item}\n{zfy-card-list-item}\n列表二内容\n{/zfy-card-list-item}\n{/zfy-card-list}", 'group' => 'block'],
            ['id' => 'hr', 'label' => '分割线', 'icon' => 'Minus', 'action' => 'blockInsert', 'snippet' => '---', 'group' => 'insert'],
            ['id' => 'link', 'label' => '链接', 'icon' => 'Link', 'action' => 'wrap', 'prefix' => '[', 'suffix' => '](https://example.com)', 'placeholder' => '链接文字', 'group' => 'insert'],
            ['id' => 'image', 'label' => '媒体库', 'icon' => 'Picture', 'action' => 'blockInsert', 'snippet' => '![图片描述](/assets/zfy/placeholders/blue.svg)', 'group' => 'insert'],
            ['id' => 'table', 'label' => '表格', 'icon' => 'Grid', 'action' => 'blockInsert', 'snippet' => "| 标题 | 内容 |\n| --- | --- |\n| 示例 | 文本 |", 'group' => 'insert'],
            ['id' => 'code-block', 'label' => '代码块', 'icon' => 'DocumentCopy', 'action' => 'blockWrap', 'prefix' => "```\n", 'suffix' => "\n```", 'placeholder' => '代码内容', 'group' => 'insert'],
            ['id' => 'html', 'label' => 'HTML', 'icon' => 'Collection', 'action' => 'blockInsert', 'snippet' => "{zfy-html}\n<div class=\"zfy-custom-html\">HTML 内容</div>\n{/zfy-html}", 'requiresRawHtml' => true, 'group' => 'insert'],
            ['id' => 'time', 'label' => '当前时间', 'icon' => 'Timer', 'action' => 'blockInsert', 'snippet' => '{zfy-time format="YYYY-MM-DD HH:mm:ss" /}', 'group' => 'insert'],
            ['id' => 'indent', 'label' => '缩进', 'icon' => 'DArrowRight', 'action' => 'linePrefix', 'prefix' => '&emsp;&emsp;', 'placeholder' => '缩进内容', 'group' => 'insert'],
            ['id' => 'characters', 'label' => '符号', 'icon' => 'Star', 'action' => 'insert', 'snippet' => '★ ☆ ✓ ✕ → ← ↑ ↓', 'group' => 'insert'],
            ['id' => 'emoji', 'label' => '表情包', 'icon' => 'Sunny', 'action' => 'insert', 'snippet' => '😀 🚀 ✨', 'group' => 'insert'],
            ...array_map(fn (array $tool) => [...$tool, 'group' => 'shortcode'], [
                ['id' => 'zfy-alert', 'label' => '提示框', 'icon' => 'Warning', 'action' => 'blockWrap', 'prefix' => '{zfy-alert color="blue" icon="info"}'."\n", 'suffix' => "\n".'{/zfy-alert}', 'placeholder' => '测试提醒框'],
                ['id' => 'zfy-callout', 'label' => '标注', 'icon' => 'InfoFilled', 'action' => 'blockWrap', 'prefix' => '{zfy-callout color="#f0ad4e"}'."\n", 'suffix' => "\n".'{/zfy-callout}', 'placeholder' => '标注内容'],
                ['id' => 'zfy-mtitle', 'label' => '居中标题', 'icon' => 'DataLine', 'action' => 'blockInsert', 'snippet' => '{zfy-mtitle title="居中标题" /}'],
                ['id' => 'zfy-card-default', 'label' => '默认卡片', 'icon' => 'Postcard', 'action' => 'blockWrap', 'prefix' => '{zfy-card-default title="卡片标题"}'."\n", 'suffix' => "\n".'{/zfy-card-default}', 'placeholder' => '卡片内容'],
                ['id' => 'zfy-card-describe', 'label' => '描述卡片', 'icon' => 'Document', 'action' => 'blockInsert', 'snippet' => "{zfy-card-describe title=\"卡片描述\"}\n卡片内容\n{/zfy-card-describe}"],
                ['id' => 'zfy-message', 'label' => '消息条', 'icon' => 'Message', 'action' => 'blockWrap', 'prefix' => '{zfy-message type="warning"}'."\n", 'suffix' => "\n".'{/zfy-message}', 'placeholder' => '消息内容'],
                ['id' => 'zfy-progress', 'label' => '进度条', 'icon' => 'Histogram', 'action' => 'blockInsert', 'snippet' => '{zfy-progress value="60" /}'],
                ['id' => 'zfy-collapse', 'label' => '折叠块', 'icon' => 'Fold', 'action' => 'blockInsert', 'snippet' => "{zfy-collapse}\n{zfy-collapse-item label=\"折叠标题一\" open}\n折叠内容一\n{/zfy-collapse-item}\n{zfy-collapse-item label=\"折叠标题二\"}\n折叠内容二\n{/zfy-collapse-item}\n{/zfy-collapse}"],
                ['id' => 'zfy-tabs', 'label' => '标签页', 'icon' => 'Operation', 'action' => 'blockInsert', 'snippet' => "{zfy-tabs}\n{zfy-tabs-pane label=\"标签一\"}\n标签一内容\n{/zfy-tabs-pane}\n{zfy-tabs-pane label=\"标签二\"}\n标签二内容\n{/zfy-tabs-pane}\n{/zfy-tabs}"],
                ['id' => 'zfy-bilibili', 'label' => '哔哩哔哩', 'icon' => 'VideoCamera', 'action' => 'blockInsert', 'snippet' => '{zfy-bilibili title="视频" bvid="BV1xx411c7mD" page="1" /}'],
                ['id' => 'zfy-dplayer', 'label' => '视频播放器', 'icon' => 'VideoPlay', 'action' => 'blockInsert', 'snippet' => '{zfy-dplayer title="视频" url="/video/demo.mp4" /}'],
                ['id' => 'zfy-music-list', 'label' => '网易云列表', 'icon' => 'Headset', 'action' => 'blockInsert', 'snippet' => '{zfy-music-list id="歌单ID" color="#1989fa" /}'],
                ['id' => 'zfy-music', 'label' => '网易云单首', 'icon' => 'Mic', 'action' => 'blockInsert', 'snippet' => '{zfy-music id="歌曲ID" color="#1989fa" /}'],
                ['id' => 'zfy-mp3', 'label' => '音频', 'icon' => 'Microphone', 'action' => 'blockInsert', 'snippet' => '{zfy-mp3 title="音频" url="/audio/demo.mp3" /}'],
                ['id' => 'zfy-cloud', 'label' => '网盘', 'icon' => 'Cloudy', 'action' => 'blockInsert', 'snippet' => '{zfy-cloud title="下载资源" url="https://example.com" /}'],
                ['id' => 'zfy-button', 'label' => '按钮', 'icon' => 'Pointer', 'action' => 'blockInsert', 'snippet' => '{zfy-button title="访问链接" url="https://example.com" /}'],
                ['id' => 'zfy-abtn', 'label' => '多彩按钮', 'icon' => 'MagicStick', 'action' => 'blockInsert', 'snippet' => '{zfy-abtn title="按钮内容" url="https://example.com" color="#ff6800" radius="8px" /}'],
                ['id' => 'zfy-anote', 'label' => '便条按钮', 'icon' => 'CollectionTag', 'action' => 'blockInsert', 'snippet' => '{zfy-anote title="按钮内容" url="https://example.com" type="secondary" /}'],
                ['id' => 'zfy-dotted', 'label' => '彩色虚线', 'icon' => 'More', 'action' => 'blockInsert', 'snippet' => '{zfy-dotted startColor="#ff6c6c" endColor="#1989fa" /}'],
                ['id' => 'zfy-timeline', 'label' => '时间线', 'icon' => 'Stopwatch', 'action' => 'blockInsert', 'snippet' => "{zfy-timeline}\n{zfy-timeline-item color=\"#19be6b\"}\n2026-05-13：版本发布\n{/zfy-timeline-item}\n{zfy-timeline-item color=\"#2d8cf0\"}\n继续优化编辑器\n{/zfy-timeline-item}\n{/zfy-timeline}"],
                ['id' => 'zfy-copy', 'label' => '复制块', 'icon' => 'CopyDocument', 'action' => 'blockWrap', 'prefix' => '{zfy-copy title="复制内容"}'."\n", 'suffix' => "\n".'{/zfy-copy}', 'placeholder' => '可复制文本'],
                ['id' => 'zfy-lamp', 'label' => '高亮灯', 'icon' => 'ReadingLamp', 'action' => 'blockInsert', 'snippet' => '{zfy-lamp title="灵感提示" /}'],
                ['id' => 'zfy-grid', 'label' => '宫格', 'icon' => 'Grid', 'action' => 'blockInsert', 'snippet' => "{zfy-grid column=\"3\" gap=\"15\"}\n{zfy-grid-item}\n宫格项目一\n{/zfy-grid-item}\n{zfy-grid-item}\n宫格项目二\n{/zfy-grid-item}\n{zfy-grid-item}\n宫格项目三\n{/zfy-grid-item}\n{/zfy-grid}"],
                ['id' => 'zfy-hide', 'label' => '隐藏内容', 'icon' => 'Hide', 'action' => 'blockWrap', 'prefix' => '{zfy-hide title="登录后可见"}'."\n", 'suffix' => "\n".'{/zfy-hide}', 'placeholder' => '隐藏内容'],
            ]),
            ['id' => 'clean', 'label' => '清空', 'icon' => 'Delete', 'action' => 'clean', 'group' => 'actions'],
            ['id' => 'download', 'label' => '下载', 'icon' => 'Download', 'action' => 'download', 'group' => 'actions'],
            ['id' => 'fullscreen', 'label' => '全屏', 'icon' => 'FullScreen', 'action' => 'fullscreen', 'group' => 'actions'],
            ['id' => 'preview', 'label' => '预览', 'icon' => 'View', 'action' => 'preview', 'group' => 'actions'],
        ];
    }
}
