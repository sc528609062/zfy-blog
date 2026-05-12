@extends('admin.layouts.app')
@section('title', '主题')
@section('page_title', '主题管理')
@section('content')
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($themes as $theme)
            <div @class([
                'card overflow-hidden ring-2 ring-transparent hover:ring-primary-300 transition',
                'ring-primary-500 shadow-lg' => $theme->slug === $active,
            ])>
                <div class="aspect-[16/9] bg-gradient-to-br from-primary-100 to-primary-300 flex items-center justify-center text-primary-700 font-bold text-2xl">
                    @if($theme->preview)<img src="{{ asset($theme->preview) }}" alt="{{ $theme->name }}" class="w-full h-full object-cover">
                    @else {{ $theme->name }} @endif
                </div>
                <div class="p-4">
                    <div class="flex items-center gap-2">
                        <h3 class="font-semibold text-ink-900">{{ $theme->name }}</h3>
                        @if($theme->slug === $active)<span class="badge-primary">使用中</span>@endif
                    </div>
                    <p class="text-xs text-ink-500 mt-1 line-clamp-2">{{ $theme->description }}</p>
                    <div class="text-xs text-ink-400 mt-2">v{{ $theme->version }} · {{ $theme->author }}</div>
                    <div class="flex gap-2 mt-3">
                        @if($theme->slug !== $active)
                            <form method="POST" action="{{ route('admin.themes.activate', $theme->slug) }}" class="flex-1">
                                @csrf <button class="btn-primary w-full text-xs">启用</button>
                            </form>
                        @endif
                        <a href="{{ route('admin.themes.preview', $theme->slug) }}" class="btn-secondary text-xs">预览</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
