@extends('layouts.app')

@section('title', '注册')

@section('content')
    <div class="max-w-md mx-auto px-4 py-12">
        <div class="card p-8">
            <h1 class="text-2xl font-bold text-ink-900 mb-1">加入 {{ $site['name'] ?? 'zfy-blog' }}</h1>
            <p class="text-sm text-ink-500 mb-6">免费注册，立即享受全站功能</p>

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-3 py-2 mb-4">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register.attempt') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="text-sm text-ink-600 block mb-1">用户名</label>
                    <input type="text" name="username" value="{{ old('username') }}" class="input" required>
                </div>
                <div>
                    <label class="text-sm text-ink-600 block mb-1">邮箱</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="input" required>
                </div>
                <div>
                    <label class="text-sm text-ink-600 block mb-1">密码</label>
                    <input type="password" name="password" class="input" required minlength="8">
                </div>
                <div>
                    <label class="text-sm text-ink-600 block mb-1">确认密码</label>
                    <input type="password" name="password_confirmation" class="input" required minlength="8">
                </div>
                <button class="btn-primary w-full">立即注册</button>
            </form>

            <p class="text-center text-sm text-ink-500 mt-6">
                已有账号？<a href="{{ route('login') }}" class="text-primary-600 hover:text-primary-700">登录</a>
            </p>
        </div>
    </div>
@endsection
