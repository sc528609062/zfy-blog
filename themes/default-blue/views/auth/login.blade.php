@extends('layouts.app')

@section('title', '登录')

@section('content')
    <div class="max-w-md mx-auto px-4 py-12">
        <div class="card p-8">
            <h1 class="text-2xl font-bold text-ink-900 mb-1">欢迎回来</h1>
            <p class="text-sm text-ink-500 mb-6">登录你的 {{ $site['name'] ?? 'zfy-blog' }} 账号</p>

            @if(session('status'))
                <div class="bg-accent-50 border border-accent-500/30 text-accent-600 text-sm rounded-lg px-3 py-2 mb-4">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-3 py-2 mb-4">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
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
                    记住我
                </label>
                <button class="btn-primary w-full">登录</button>
            </form>

            <div class="flex items-center justify-between text-sm mt-6 text-ink-500">
                <a href="{{ route('password.request') }}" class="hover:text-primary-600">忘记密码？</a>
                <a href="{{ route('register') }}" class="hover:text-primary-600">注册新账号</a>
            </div>
        </div>
    </div>
@endsection
