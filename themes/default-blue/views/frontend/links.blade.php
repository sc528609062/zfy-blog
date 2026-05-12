@extends('layouts.app')
@section('title', '友情链接')
@section('content')
    <div class="max-w-5xl mx-auto px-4 py-10">
        <h1 class="text-2xl font-bold text-ink-900 mb-6">友情链接</h1>
        @php $links = \App\Models\FriendLink::where('enabled', true)->orderBy('sort_order')->get()->groupBy('category'); @endphp
        @if($links->isEmpty())
            @include('partials.placeholder', ['title' => '暂无友情链接', 'subtitle' => '后台 → 设置 → 友情链接 添加'])
        @else
            @foreach($links as $cat => $group)
                <h2 class="font-semibold text-ink-700 mt-6 mb-3">{{ $cat ?: '默认' }}</h2>
                <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($group as $link)
                        <a href="{{ $link->url }}" target="_blank" rel="nofollow"
                           class="card-hover p-4 flex items-center gap-3">
                            @if($link->logo)<img src="{{ $link->logo }}" class="w-8 h-8 rounded" alt="">@endif
                            <div class="min-w-0">
                                <div class="font-medium text-ink-900 truncate">{{ $link->name }}</div>
                                <div class="text-xs text-ink-500 truncate">{{ $link->description }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endforeach
        @endif
    </div>
@endsection
