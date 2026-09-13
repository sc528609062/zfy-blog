@php
    $page = $page ?? 'home';
    $contents = collect($contents ?? []);
    $resources = collect($resources ?? []);
    $posts = collect($posts ?? []);
    $images = collect($images ?? []);
    $categories = collect($categories ?? []);
    $rankings = collect($rankings ?? []);
    $vipLevels = collect($vipLevels ?? []);
    $featured = $content ?? $featured ?? $resources->first() ?? $contents->first() ?? $posts->first() ?? $images->first();
    $accent = $theme['accent'] ?? '#2563eb';
    $onAccent = $theme['on_accent'] ?? '#ffffff';
    $accentHover = $theme['accent_hover'] ?? '#1d4ed8';
    $accentText = $theme['accent_text'] ?? '#1d4ed8';
    $themeTone = 'market';
@endphp
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('themes.shared.partials.seo')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('themes.shared.partials.appearance')
</head>
<body class="a-body b-body site-refined" data-sidebar="{{ data_get($theme, 'settings.global.sidebar_position', 'right') }}" data-back-to-top="{{ data_get($theme, 'settings.global.back_to_top', true) ? 'true' : 'false' }}">
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

    @include('themes.shared.partials.footer')
</body>
</html>
