@extends('admin.layouts.app')
@section('title', '菜单')
@section('page_title', '菜单管理')
@section('content')
    <div class="grid md:grid-cols-3 gap-4 mb-4">
        @foreach($menus as $m)
            <a href="{{ route('admin.menus.show', $m) }}" class="card-hover p-5">
                <div class="font-semibold text-ink-900">{{ $m->name }}</div>
                <div class="text-xs text-ink-500 mt-1">位置：<code class="bg-ink-100 px-1 rounded">{{ $m->location }}</code></div>
                <div class="text-xs text-ink-500 mt-1">{{ $m->items->count() }} 个项目</div>
            </a>
        @endforeach
        <a href="{{ route('admin.menus.create') }}" class="border-2 border-dashed border-ink-200 hover:border-primary-300 hover:bg-primary-50/40 rounded-2xl flex items-center justify-center text-ink-400 hover:text-primary-600 text-sm">+ 新建菜单</a>
    </div>
@endsection
