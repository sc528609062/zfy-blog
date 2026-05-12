<!DOCTYPE html>
<html lang="zh-CN" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>后台登录 - {{ $site['name'] ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-ink-900 flex items-center justify-center px-4 relative overflow-hidden">

    <div class="absolute inset-0 bg-gradient-to-br from-primary-900 via-ink-900 to-ink-950"></div>
    <div class="absolute inset-0 opacity-40 bg-hero-grid"></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
        <div class="text-center mb-6">
            <div class="w-12 h-12 mx-auto bg-gradient-to-br from-primary-500 to-primary-700 rounded-2xl flex items-center justify-center text-white">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M3 6h18l-2 12H5L3 6z"/></svg>
            </div>
            <h1 class="mt-4 text-xl font-bold text-ink-900">{{ $site['name'] ?? 'zfy-blog' }} 后台</h1>
            <p class="text-sm text-ink-500">登录您的管理员账号</p>
        </div>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-3 py-2 mb-4">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.login.attempt') }}" class="space-y-4">
            @csrf
            <div>
                <label class="text-sm text-ink-600 block mb-1">账号 / 邮箱</label>
                <input type="text" name="login" value="{{ old('login') }}" class="input" required autofocus>
            </div>
            <div>
                <label class="text-sm text-ink-600 block mb-1">密码</label>
                <input type="password" name="password" class="input" required>
            </div>
            <label class="flex items-center gap-2 text-sm text-ink-600">
                <input type="checkbox" name="remember" value="1">
                7 天内自动登录
            </label>
            <button class="btn-primary w-full">登录</button>
        </form>

        <div class="mt-6 text-center text-xs text-ink-400">
            <a href="{{ url('/') }}" class="hover:text-primary-600">← 返回前台</a>
        </div>
    </div>
</body>
</html>
