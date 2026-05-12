@extends('install.layout')

@section('title', '安装完成')

@section('content')
    @if(!empty($error))
        <div class="text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full mx-auto mb-4 flex items-center justify-center">
                <svg class="w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <h2 class="text-xl font-semibold text-red-700 mb-2">安装失败</h2>
            <p class="text-sm text-ink-500 mb-6">请回退检查或查看日志：</p>
            <pre class="text-left bg-ink-50 p-4 rounded text-xs text-red-700 overflow-auto">{{ $error }}</pre>
            <a href="{{ route('install.environment') }}" class="btn-secondary mt-6 inline-flex">重新检查</a>
        </div>
    @else
        <div class="text-center">
            <div class="w-16 h-16 bg-accent-50 rounded-full mx-auto mb-4 flex items-center justify-center">
                <svg class="w-8 h-8 text-accent-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h2 class="text-2xl font-bold text-ink-900 mb-2">🎉 安装完成</h2>
            <p class="text-ink-500 mb-6">zfy-blog v{{ config('zfy.version') }} 已就绪。</p>

            <div class="grid md:grid-cols-2 gap-4 max-w-lg mx-auto mb-8 text-left">
                <a href="{{ url('/') }}" class="card-hover p-5 block">
                    <div class="font-semibold text-ink-900 mb-1">访问前台</div>
                    <p class="text-sm text-ink-500">查看你的首页与公开内容</p>
                </a>
                <a href="{{ route('admin.login') }}" class="card-hover p-5 block">
                    <div class="font-semibold text-primary-600 mb-1">登录后台 →</div>
                    <p class="text-sm text-ink-500">使用刚才创建的管理员账号登录</p>
                </a>
            </div>

            <div class="text-xs text-ink-400 mt-6">
                提示：请将 <code>storage/app/installed.lock</code> 加入备份。如需重新安装，请删除该文件。
            </div>
        </div>
    @endif
@endsection
