@extends('install.layout')

@section('title', '欢迎 - zfy-blog 安装')

@section('content')
    <div class="text-center">
        <h1 class="text-2xl font-bold text-ink-900 mb-2">欢迎使用 zfy-blog v{{ config('zfy.version') }}</h1>
        <p class="text-ink-500 mb-8">一个面向中文资源创作者的内容/资源/会员/积分一体化平台</p>
    </div>

    <div class="grid md:grid-cols-3 gap-4 mb-8">
        <div class="card p-5">
            <div class="text-primary-600 mb-2 font-semibold">📚 强大编辑器</div>
            <p class="text-sm text-ink-500">TipTap 三模式编辑（块/富文本/Markdown），统一 JSON 存储</p>
        </div>
        <div class="card p-5">
            <div class="text-primary-600 mb-2 font-semibold">💎 商业化</div>
            <p class="text-sm text-ink-500">VIP 会员、付费内容、钱包、积分商城、作者分成与提现</p>
        </div>
        <div class="card p-5">
            <div class="text-primary-600 mb-2 font-semibold">🎨 主题与插件</div>
            <p class="text-sm text-ink-500">A-Style 默认主题 + 主题切换 + 插件机制 + 可视化页面构建</p>
        </div>
    </div>

    <div class="prose prose-sm max-w-none text-ink-600 mb-8">
        <h3 class="text-base text-ink-900 font-semibold mt-0">安装前准备</h3>
        <ul>
            <li>PHP {{ config('zfy.install.min_php') }}+，已启用 pdo_mysql, mbstring, openssl, gd, fileinfo</li>
            <li>MySQL 8.0+ / MariaDB 10.6+，已建好数据库与账号</li>
            <li>Redis 6+（可选，强烈推荐）</li>
            <li>composer、npm 已安装，依赖已经 install / build</li>
        </ul>
    </div>

    <div class="text-center">
        <a href="{{ route('install.environment') }}" class="btn-primary inline-flex">
            开始安装
            <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>
    </div>
@endsection
