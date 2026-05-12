<!DOCTYPE html>
<html lang="zh-CN" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', '安装 zfy-blog')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-gradient-to-br from-primary-50 via-white to-primary-100 text-ink-700">
<div class="max-w-5xl mx-auto px-4 py-10">

    <header class="text-center mb-8">
        <div class="inline-flex items-center gap-2 text-2xl font-bold text-primary-700">
            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="currentColor"><path d="M4 4h16v4H4zM4 10h10v4H4zM4 16h16v4H4z"/></svg>
            zfy-blog 安装向导
        </div>
        <p class="text-sm text-ink-500 mt-1">v{{ config('zfy.version') }} · Laravel {{ app()->version() }}</p>
    </header>

    @php
        $steps = [
            ['key' => 'index',       'name' => '欢迎'],
            ['key' => 'environment', 'name' => '环境检查'],
            ['key' => 'database',    'name' => '数据库'],
            ['key' => 'redis',       'name' => 'Redis'],
            ['key' => 'site',        'name' => '站点信息'],
            ['key' => 'admin',       'name' => '管理员'],
            ['key' => 'complete',    'name' => '完成'],
        ];
        $current = $step ?? 0;
    @endphp

    <ol class="flex items-center gap-2 justify-center mb-8 flex-wrap">
        @foreach($steps as $i => $s)
            <li class="flex items-center gap-2">
                <span @class([
                    'w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold',
                    'bg-primary-600 text-white shadow' => $i == $current,
                    'bg-accent-500 text-white' => $i < $current,
                    'bg-ink-200 text-ink-500' => $i > $current,
                ])>
                    @if($i < $current)
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    @else
                        {{ $i + 1 }}
                    @endif
                </span>
                <span @class(['text-sm', 'font-semibold text-primary-700' => $i == $current, 'text-ink-400' => $i > $current])>
                    {{ $s['name'] }}
                </span>
                @if(!$loop->last)
                    <span class="w-6 h-px bg-ink-200"></span>
                @endif
            </li>
        @endforeach
    </ol>

    <div class="bg-white rounded-2xl shadow-card border border-ink-100 overflow-hidden">
        @if(session('status'))
            <div class="bg-accent-50 border-b border-accent-500/30 text-accent-600 px-6 py-3 text-sm">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="bg-red-50 border-b border-red-200 text-red-700 px-6 py-3 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="p-8">
            @yield('content')
        </div>
    </div>

    <footer class="text-center text-xs text-ink-400 mt-6">
        zfy-blog 安装器 © {{ date('Y') }}
    </footer>
</div>
</body>
</html>
