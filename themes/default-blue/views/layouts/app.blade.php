<!DOCTYPE html>
<html lang="zh-CN" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $site['name'] ?? 'zfy-blog') - {{ $site['tagline'] ?? '' }}</title>
    <meta name="description" content="@yield('description', $site['tagline'] ?? '')">
    @stack('meta')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full flex flex-col bg-ink-50 text-ink-700">

    @include('partials.header')

    <main class="flex-1">
        @yield('content')
    </main>

    @include('partials.footer')

    @stack('scripts')
</body>
</html>
