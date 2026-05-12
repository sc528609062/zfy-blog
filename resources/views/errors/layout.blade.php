<!DOCTYPE html>
<html lang="zh-CN" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('code') - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-gradient-to-br from-primary-50 via-white to-primary-100 flex items-center justify-center px-4">
    <div class="text-center max-w-md">
        <div class="text-[120px] font-black bg-gradient-to-br from-primary-500 to-primary-700 bg-clip-text text-transparent leading-none">
            @yield('code')
        </div>
        <h1 class="text-2xl font-bold text-ink-900 mt-2">@yield('title')</h1>
        <p class="text-ink-500 mt-2 mb-8">@yield('message')</p>
        <div class="flex justify-center gap-2">
            <a href="{{ url('/') }}" class="btn-primary text-sm">返回首页</a>
            <button onclick="history.back()" class="btn-secondary text-sm">上一页</button>
        </div>
    </div>
</body>
</html>
