<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Order;
use App\Models\PageLayout;
use App\Models\Plugin;
use App\Models\Theme;
use App\Models\User;
use App\Services\DemoContentRepository;
use App\Services\PackageManifestService;
use App\Services\ThemeManager;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(
        private readonly ThemeManager $themes,
        private readonly PackageManifestService $packages,
    ) {}

    public function page(string $section = 'dashboard')
    {
        return view('admin.shell', [
            'section' => $section,
            'theme' => $this->themes->active(),
            'stats' => [
                'contents' => Content::count(),
                'orders' => Order::count(),
                'users' => User::count(),
                'themes' => Theme::count(),
            ],
            'contents' => Content::latest()->take(12)->get(),
            'orders' => Order::latest()->take(12)->get(),
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
}
