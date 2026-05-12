@extends('admin.layouts.app')
@section('title', '主题预览')
@section('page_title', '主题预览：' . $theme->name)
@section('content')
    <div class="card p-6 max-w-3xl space-y-3">
        <h2 class="text-xl font-bold">{{ $theme->name }}</h2>
        <p class="text-sm text-ink-500">{{ $theme->description }}</p>
        <div class="grid grid-cols-2 gap-3 text-sm">
            <div><span class="text-ink-400">版本：</span>{{ $theme->version }}</div>
            <div><span class="text-ink-400">作者：</span>{{ $theme->author }}</div>
            <div><span class="text-ink-400">兼容：</span>{{ $theme->compatible }}</div>
            <div><span class="text-ink-400">Slug：</span>{{ $theme->slug }}</div>
        </div>
        @if(count($theme->menus))
            <div>
                <div class="text-ink-400 text-sm mb-1">建议菜单位</div>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($theme->menus as $loc => $name)
                        <span class="badge-muted">{{ $loc }} · {{ $name }}</span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
