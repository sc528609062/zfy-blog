@extends('install.layout')

@section('title', '创建管理员')

@section('content')
    <h2 class="text-lg font-semibold text-ink-900 mb-2">创建超级管理员</h2>
    <p class="text-sm text-ink-500 mb-6">该账号将拥有 SUPER_ADMIN 角色，可访问全部后台功能。</p>

    <form method="POST" action="{{ route('install.admin.store') }}" class="space-y-4">
        @csrf
        <div>
            <label class="text-sm text-ink-600 block mb-1">用户名（登录名）</label>
            <input type="text" name="username" value="{{ old('username', 'admin') }}" class="input" required>
            <p class="text-xs text-ink-400 mt-1">3-64 字符，字母数字下划线连字符</p>
        </div>
        <div>
            <label class="text-sm text-ink-600 block mb-1">邮箱</label>
            <input type="email" name="email" value="{{ old('email') }}" class="input" required>
        </div>
        <div>
            <label class="text-sm text-ink-600 block mb-1">密码</label>
            <input type="password" name="password" class="input" required minlength="8">
            <p class="text-xs text-ink-400 mt-1">不少于 8 位</p>
        </div>
        <div>
            <label class="text-sm text-ink-600 block mb-1">确认密码</label>
            <input type="password" name="password_confirmation" class="input" required minlength="8">
        </div>

        <div class="flex justify-between mt-8">
            <a href="{{ route('install.site') }}" class="btn-secondary">返回</a>
            <button type="submit" class="btn-primary">完成安装</button>
        </div>
    </form>
@endsection
