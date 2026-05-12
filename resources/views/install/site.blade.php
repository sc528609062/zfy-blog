@extends('install.layout')

@section('title', '站点信息')

@section('content')
    <h2 class="text-lg font-semibold text-ink-900 mb-4">站点信息</h2>

    <form method="POST" action="{{ route('install.site.store') }}" class="space-y-4">
        @csrf
        <div>
            <label class="text-sm text-ink-600 block mb-1">站点名称</label>
            <input type="text" name="name" value="{{ $old['name'] }}" class="input" required>
        </div>
        <div>
            <label class="text-sm text-ink-600 block mb-1">站点 Slogan</label>
            <input type="text" name="tagline" value="{{ $old['tagline'] }}" class="input">
        </div>
        <div>
            <label class="text-sm text-ink-600 block mb-1">站点 URL</label>
            <input type="url" name="url" value="{{ $old['url'] }}" class="input" required>
            <p class="text-xs text-ink-400 mt-1">用于邮件、回调、CDN base URL，正式部署时务必使用 HTTPS 主域名。</p>
        </div>

        <div class="flex justify-between mt-8">
            <a href="{{ route('install.redis') }}" class="btn-secondary">返回</a>
            <button type="submit" class="btn-primary">下一步</button>
        </div>
    </form>
@endsection
