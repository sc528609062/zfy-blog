@php
    $slug = $theme['slug'] ?? 'style-a-blue-gaming';
    $settings = $theme['settings'] ?? [];
    $nav = data_get($settings, 'global.nav', ['首页', '资源', '教程', '社区', '活动']);
    $accent = $theme['accent'] ?? '#1684ff';
    $onAccent = $theme['on_accent'] ?? '#ffffff';
    $accentHover = $theme['accent_hover'] ?? '#0f5ed7';
    $accentText = $theme['accent_text'] ?? '#0f5ed7';
    $isMarket = $slug === 'style-b-marketplace';
    $isCreative = $slug === 'style-c-creative';
    $heroTitle = $isCreative ? '创意有趣，资源无限' : ($isMarket ? '优质资源 · 一站购齐' : '幻境之旅 · 新版本上线');
    $heroSub = $isCreative ? '加入 zfy-blog，发现优质资源，连接有趣的创作者！' : ($isMarket ? '网站源码 / 设计素材 / 教程课程 / 插件扩展' : '全新地图开放，隐藏剧情与强力装备等你探索');
@endphp
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $content->title ?? ($theme['name'] ?? 'zfy-blog') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>:root{--theme-accent:{{ $accent }};--a-primary:{{ $accent }};--a-on-primary:{{ $onAccent }};--a-primary-dark:{{ $accentHover }};--a-primary-text:{{ $accentText }};}</style>
</head>
<body class="theme {{ $slug }}">
    <header class="site-header">
        <a class="brand" href="/">
            <span class="brand-mark">{{ $isCreative ? '✦' : ($isMarket ? 'Z' : '🎮') }}</span>
            <strong>zfy-blog</strong>
        </a>
        <nav class="top-nav">
            @foreach($nav as $item)
                <a href="{{ $loop->first ? '/' : '#' }}" class="{{ $loop->first ? 'active' : '' }}">{{ $item }}</a>
            @endforeach
        </nav>
        <form class="search" action="/search">
            <input name="q" placeholder="搜索资源、教程、文章..." value="{{ request('q') }}">
        </form>
        <a class="publish" href="/admin/editor">发布</a>
        <a class="vip-pill" href="/vip">开通VIP</a>
    </header>

    <main class="shell page-{{ $page ?? 'home' }}">
        @if(($page ?? 'home') === 'home')
            @include('themes.shared.partials.home')
        @elseif(str_contains($page ?? '', 'detail'))
            @include('themes.shared.partials.detail')
        @elseif(str_starts_with($page ?? '', 'user-') || ($page ?? '') === 'author-workspace')
            @include('themes.shared.partials.user')
        @elseif(($page ?? '') === 'vip')
            @include('themes.shared.partials.vip')
        @else
            @include('themes.shared.partials.list')
        @endif
    </main>

    <footer class="site-footer">
        <div>© {{ date('Y') }} zfy-blog · Laravel CMS / 内容商业化平台</div>
        <div>主题：{{ $theme['name'] ?? $slug }} · <a href="/admin/themes">后台切换</a></div>
    </footer>
</body>
</html>
