<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CardCode;
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
            ]),
        };
    }
}
