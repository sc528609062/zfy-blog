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
    $onAccent = $theme['on_accent'] ?? '#101828';
    $accentHover = $theme['accent_hover'] ?? '#fbd22c';
    $accentText = $theme['accent_text'] ?? '#75600f';
    $themeTone = 'creative';
@endphp
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('themes.shared.partials.seo')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>:root{--a-primary:{{ $accent }};--a-on-primary:{{ $onAccent }};--a-primary-dark:{{ $accentHover }};--a-primary-text:{{ $accentText }};}</style>
</head>
<body class="a-body c-body">
    @include('themes.style-a-blue-gaming.partials.header')

    <main class="a-shell page-{{ $page }}">
        @if($page === 'home')
            @if(data_get($pageLayout?->schema, 'enabled'))
                {!! app(\App\Services\PageBuilderRenderer::class)->render($pageLayout->schema, compact('contents', 'rankings')) !!}
            @else
                @include('themes.style-a-blue-gaming.partials.home')
            @endif
        @elseif(in_array($page, ['content-detail', 'file-detail', 'images-detail', 'page-detail'], true))
            @include('themes.style-a-blue-gaming.partials.detail')
        @elseif($page === 'vip')
            @include('themes.shared.partials.vip')
        @elseif(str_starts_with($page, 'user-') || $page === 'author-workspace')
            @include('themes.shared.partials.account')
        @elseif($page === 'search')
            @include('themes.style-a-blue-gaming.partials.search')
        @elseif($page === 'points-store')
            @include('themes.shared.partials.points-store')
        @elseif($page === 'shop')
            @include('themes.shared.partials.shop')
        @elseif($page === 'cart')
            @include('themes.shared.partials.cart')
        @elseif(in_array($page, ['links', 'authors', 'author-profile'], true))
            @include('themes.shared.partials.directory')
        @else
            @include('themes.style-a-blue-gaming.partials.list')
        @endif
    </main>
    @include('themes.shared.partials.site-extras')

    <footer class="a-footer">
        <span>© {{ date('Y') }} {{ data_get($theme, 'settings.global.footer_text', $siteName) }}</span>
        <nav>
            <a href="/authors">创作者</a>
            <a href="/points-store">积分商城</a>
            <a href="/vip">VIP会员</a>
            <a href="/cart">购物车</a>
        </nav>
    </footer>
</body>
</html>
