@extends('layouts.app')

@section('title', $site['name'] ?? 'zfy-blog')

@section('content')
    {{-- Hero --}}
    <section class="bg-gradient-to-br from-primary-700 via-primary-600 to-primary-800 text-white relative overflow-hidden">
        <div class="absolute inset-0 bg-hero-grid opacity-50"></div>
        <div class="max-w-7xl mx-auto px-4 py-16 relative">
            <h1 class="text-3xl md:text-4xl font-bold mb-3">{{ $site['name'] ?? 'zfy-blog' }}</h1>
            <p class="text-primary-100 text-lg mb-8">{{ $site['tagline'] ?? '精品内容 · 资源分享 · 创作者社区' }}</p>

            <form method="GET" action="{{ route('search') }}" class="max-w-2xl flex">
                <input type="search" name="q" placeholder="搜索游戏、素材、教程..."
                       class="flex-1 px-5 py-3 rounded-l-xl text-ink-800 border-0 focus:ring-2 focus:ring-primary-200">
                <button class="px-6 bg-vip-500 hover:bg-vip-600 rounded-r-xl font-semibold flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m21 21-4.3-4.3"/></svg>
                    搜索
                </button>
            </form>

            <div class="mt-8 flex flex-wrap gap-3">
                @foreach($featuredCategories as $cat)
                    <a href="{{ route('taxonomy.category', $cat->slug) }}"
                       class="px-4 py-2 bg-white/10 hover:bg-white/20 backdrop-blur rounded-lg text-sm transition">
                        {{ $cat->name }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-4 py-8 grid lg:grid-cols-4 gap-6">
        {{-- 主区 --}}
        <div class="lg:col-span-3 space-y-8">
            <section>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-ink-900 flex items-center gap-2">
                        <span class="w-1 h-5 bg-primary-600 rounded"></span>
                        最新内容
                    </h2>
                    <a href="{{ route('channel.posts') }}" class="text-sm text-primary-600 hover:text-primary-700">查看全部 →</a>
                </div>

                <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
                    @forelse($latest as $item)
                        @include('partials.content-card', ['item' => $item])
                    @empty
                        <div class="col-span-full card p-10 text-center text-ink-400">
                            <p>还没有内容，<a href="{{ route('admin.contents.create') }}" class="text-primary-600">去后台发布第一篇</a></p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        {{-- 侧边栏 --}}
        <aside class="space-y-6">
            @if($vipLevels->count())
                <div class="card p-5 bg-gradient-to-br from-vip-50 to-white border-vip-500/20">
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="w-5 h-5 text-vip-500" fill="currentColor" viewBox="0 0 24 24"><path d="M5 16L3 7l5.5 4L12 4l3.5 7L21 7l-2 9H5zm0 2h14v2H5v-2z"/></svg>
                        <span class="font-semibold text-ink-900">开通 VIP，享专属权益</span>
                    </div>
                    <ul class="space-y-2 mb-4">
                        @foreach($vipLevels as $lv)
                            <li class="flex items-center justify-between text-sm">
                                <span class="text-ink-700">{{ $lv->name }}</span>
                                <span class="text-vip-600 font-semibold">¥{{ $lv->price }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('vip.index') }}" class="btn-vip w-full text-sm justify-center">立即开通</a>
                </div>
            @endif

            <div class="card p-5">
                <h3 class="font-semibold text-ink-900 mb-3">热门内容</h3>
                <ol class="space-y-3">
                    @foreach($hot as $i => $item)
                        <li class="flex gap-3 items-start">
                            <span @class([
                                'w-6 h-6 rounded text-xs font-bold flex items-center justify-center shrink-0',
                                'bg-primary-600 text-white' => $i < 3,
                                'bg-ink-100 text-ink-500' => $i >= 3,
                            ])>{{ $i + 1 }}</span>
                            <a href="{{ $item->url }}" class="text-sm text-ink-700 hover:text-primary-600 line-clamp-2">{{ $item->title }}</a>
                        </li>
                    @endforeach
                </ol>
            </div>

            @if($friendLinks->count())
                <div class="card p-5">
                    <h3 class="font-semibold text-ink-900 mb-3">友情链接</h3>
                    <div class="flex flex-wrap gap-x-3 gap-y-1.5 text-xs">
                        @foreach($friendLinks as $link)
                            <a href="{{ $link->url }}" target="_blank" rel="nofollow" class="text-ink-500 hover:text-primary-600">{{ $link->name }}</a>
                        @endforeach
                    </div>
                </div>
            @endif
        </aside>
    </div>
@endsection
