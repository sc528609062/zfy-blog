@extends('admin.layouts.app')
@section('title', '菜单')
@section('content')
    <form method="POST" action="{{ $menu->exists ? route('admin.menus.update', $menu) : route('admin.menus.store') }}" class="card p-6 max-w-2xl space-y-4">
        @csrf
        @if($menu->exists)@method('PUT')@endif
        <div><label class="text-sm text-ink-600 block mb-1">名称</label><input type="text" name="name" value="{{ old('name', $menu->name) }}" class="input" required></div>
        @if(!$menu->exists)
            <div><label class="text-sm text-ink-600 block mb-1">位置</label><input type="text" name="location" class="input" placeholder="primary/footer/..."></div>
        @endif
        <div><label class="text-sm text-ink-600 block mb-1">描述</label><textarea name="description" rows="2" class="input">{{ old('description', $menu->description) }}</textarea></div>
        <button class="btn-primary">保存</button>
    </form>
@endsection
