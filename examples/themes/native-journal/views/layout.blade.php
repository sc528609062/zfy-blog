<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('themes.shared.partials.seo')
    <link rel="stylesheet" href="{{ zfy_asset('theme', 'native-journal', 'assets/journal.css') }}">
</head>
<body style="--journal-accent: {{ $theme['accent'] }}">
    <header><a href="/">{{ data_get($theme, 'settings.global.logo_text', 'Native Journal') }}</a><nav>@if(isset($navigation) && $navigation->has('primary'))@include('themes.shared.partials.navigation-items', ['items' => $navigation->get('primary')->items])@else<a href="/">首页</a><a href="/shop">商城</a><a href="/user">账户</a>@endif</nav></header>
    <main>@include('themes.shared.partials.native-content')</main>
    <footer>{{ data_get($theme, 'settings.global.footer_text', 'Native Journal') }}</footer>
</body>
</html>
