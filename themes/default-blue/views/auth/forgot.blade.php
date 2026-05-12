@extends('layouts.app')

@section('title', '找回密码')

@section('content')
    <div class="max-w-md mx-auto px-4 py-12">
        <div class="card p-8">
            <h1 class="text-2xl font-bold text-ink-900 mb-2">找回密码</h1>
            <p class="text-sm text-ink-500 mb-6">输入注册邮箱，我们将发送重置链接（Sprint 2 完整接入）</p>

            @if(session('status'))
                <div class="bg-accent-50 border border-accent-500/30 text-accent-600 text-sm rounded-lg px-3 py-2 mb-4">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf
                <input type="email" name="email" placeholder="email@example.com" class="input" required>
                <button class="btn-primary w-full">发送重置链接</button>
            </form>

            <p class="text-center text-sm text-ink-500 mt-6">
                <a href="{{ route('login') }}" class="text-primary-600 hover:text-primary-700">返回登录</a>
            </p>
        </div>
    </div>
@endsection
