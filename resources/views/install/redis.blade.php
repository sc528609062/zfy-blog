@extends('install.layout')

@section('title', 'Redis 配置')

@section('content')
    <h2 class="text-lg font-semibold text-ink-900 mb-2">Redis 配置（推荐）</h2>
    <p class="text-sm text-ink-500 mb-6">Redis 用于缓存、会话、队列。如未安装可跳过，但生产环境强烈建议启用。</p>

    <form method="POST" action="{{ route('install.redis.store') }}" class="space-y-4">
        @csrf
        <label class="flex items-center gap-2 mb-4">
            <input type="checkbox" name="enabled" value="1" {{ ($old['enabled'] ?? true) ? 'checked' : '' }}>
            <span class="text-sm text-ink-700">启用 Redis</span>
        </label>

        <div class="grid md:grid-cols-3 gap-4">
            <div>
                <label class="text-sm text-ink-600 block mb-1">Host</label>
                <input type="text" name="host" value="{{ $old['host'] }}" class="input">
            </div>
            <div>
                <label class="text-sm text-ink-600 block mb-1">Port</label>
                <input type="text" name="port" value="{{ $old['port'] }}" class="input">
            </div>
            <div>
                <label class="text-sm text-ink-600 block mb-1">密码</label>
                <input type="text" name="password" value="{{ $old['password'] }}" class="input" placeholder="为空表示无密码">
            </div>
        </div>

        <div class="flex justify-between mt-8">
            <a href="{{ route('install.database') }}" class="btn-secondary">返回</a>
            <button type="submit" class="btn-primary">下一步</button>
        </div>
    </form>
@endsection
