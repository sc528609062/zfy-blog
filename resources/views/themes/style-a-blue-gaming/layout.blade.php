@php
    $page = $page ?? 'home';
    $contents = collect($contents ?? []);
    $resources = collect($resources ?? []);
    $posts = collect($posts ?? []);
    $images = collect($images ?? []);
    $categories = collect($categories ?? []);
    $rankings = collect($rankings ?? []);
    $vipLevels = collect($vipLevels ?? []);
    $featured = $content ?? $featured ?? $contents->first() ?? $resources->first() ?? $posts->first() ?? $images->first();
    $accent = $theme['accent'] ?? '#1677ff';
    $onAccent = $theme['on_accent'] ?? '#ffffff';
    $accentHover = $theme['accent_hover'] ?? '#0f5ed7';
    $accentText = $theme['accent_text'] ?? '#0f5ed7';
    $themeTone = 'blue';
@endphp
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $content->title ?? ($theme['name'] ?? 'zfy-blog') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>:root{--a-primary:{{ $accent }};--a-on-primary:{{ $onAccent }};--a-primary-dark:{{ $accentHover }};--a-primary-text:{{ $accentText }};}</style>
</head>
<body class="a-body">
    @include('themes.style-a-blue-gaming.partials.header')

    <main class="a-shell page-{{ $page }}">
        @if($page === 'home')
            @include('themes.style-a-blue-gaming.partials.home')
        @elseif(in_array($page, ['content-detail', 'file-detail', 'images-detail', 'page-detail'], true))
            @include('themes.style-a-blue-gaming.partials.detail')
        @elseif($page === 'vip')
            @include('themes.style-a-blue-gaming.partials.vip')
        @elseif($page === 'search')
            @include('themes.style-a-blue-gaming.partials.search')
        @elseif($page === 'rank')
            @include('themes.style-a-blue-gaming.partials.rank')
        @elseif($page === 'points-store')
            @include('themes.style-a-blue-gaming.partials.points-store')
        @elseif(str_starts_with($page, 'user-') || $page === 'author-workspace')
            @include('themes.style-a-blue-gaming.partials.user')
        @else
            @include('themes.style-a-blue-gaming.partials.list')
        @endif
    </main>

    <footer class="a-footer">
        <span>© {{ date('Y') }} zfy-blog 游戏资源 · 攻略 · 社区</span>
        <nav>
            <a href="/p/about-zfy-blog">关于我们</a>
            <a href="/links">友情链接</a>
            <a href="/vip">会员服务</a>
            <a href="/admin">管理后台</a>
        </nav>
    </footer>
</body>
</html>
