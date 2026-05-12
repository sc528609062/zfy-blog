<!DOCTYPE html>
<html lang="zh-CN" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '后台管理') - {{ $site['name'] ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
</head>
<body class="min-h-full bg-ink-50 text-ink-700">

<div class="flex min-h-screen">

    {{-- Sidebar --}}
    @include('admin.partials.sidebar')

    <div class="flex-1 flex flex-col min-w-0">
        {{-- Topbar --}}
        @include('admin.partials.topbar')

        {{-- Flash --}}
        @if(session('status'))
            <div class="mx-6 mt-4 px-4 py-2.5 bg-accent-50 border border-accent-500/30 text-accent-700 text-sm rounded-lg">
                {{ session('status') }}
            </div>
        @endif
        @if($errors->any())
            <div class="mx-6 mt-4 px-4 py-2.5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        <main class="flex-1 px-6 py-6">
            @yield('content')
        </main>

        <footer class="border-t border-ink-100 px-6 py-3 text-xs text-ink-400 flex justify-between">
            <span>zfy-blog v{{ config('zfy.version') }} · Laravel {{ app()->version() }}</span>
            <span>{{ now()->format('Y-m-d H:i') }}</span>
        </footer>
    </div>
</div>

@stack('scripts')
</body>
</html>
