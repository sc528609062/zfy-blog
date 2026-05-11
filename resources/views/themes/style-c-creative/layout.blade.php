@php
    $page = $page ?? 'home';
    $contents = collect($contents ?? []);
    $resources = collect($resources ?? []);
    $posts = collect($posts ?? []);
    $images = collect($images ?? []);
    $categories = collect($categories ?? []);
    $rankings = collect($rankings ?? []);
    $vipLevels = collect($vipLevels ?? []);
    $featured = $content ?? $featured ?? $images->first() ?? $resources->first() ?? $contents->first() ?? $posts->first();
    $accent = $theme['accent'] ?? '#facc15';
    $themeTone = 'creative';
@endphp
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $content->title ?? ($theme['name'] ?? 'zfy-blog 创意资源站') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>:root{--a-primary:{{ $accent }};}</style>
</head>
<body class="a-body c-body">
    @include('themes.style-a-blue-gaming.partials.header')

    <main class="a-shell page-{{ $page }}">
        @if($page === 'home')
            @include('themes.style-a-blue-gaming.partials.home')
        @elseif(in_array($page, ['content-detail', 'file-detail', 'images-detail', 'page-detail'], true))
            @include('themes.style-a-blue-gaming.partials.detail')
        @elseif($page === 'vip')
            @include('themes.style-a-blue-gaming.partials.vip')
        @elseif(str_starts_with($page, 'user-') || $page === 'author-workspace')
            @include('themes.style-a-blue-gaming.partials.user')
        @else
            @include('themes.style-a-blue-gaming.partials.list')
        @endif
    </main>

    <footer class="a-footer">
        <span>© {{ date('Y') }} zfy-blog 创意资源 · 插画教程 · 设计社区</span>
        <nav>
            <a href="/authors">创作者</a>
            <a href="/points-store">积分商城</a>
            <a href="/vip">VIP会员</a>
            <a href="/admin/themes">切换主题</a>
        </nav>
    </footer>
</body>
</html>
