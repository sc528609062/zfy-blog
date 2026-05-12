@extends('admin.layouts.app')
@section('title', $tag->exists ? '编辑标签' : '新建标签')
@section('page_title', $tag->exists ? '编辑标签' : '新建标签')
@section('content')
    <form method="POST" action="{{ $tag->exists ? route('admin.tags.update', $tag) : route('admin.tags.store') }}" class="card p-6 max-w-xl space-y-4">
        @csrf
        @if($tag->exists)@method('PUT')@endif
        <div><label class="text-sm text-ink-600 block mb-1">名称</label><input type="text" name="name" value="{{ old('name', $tag->name) }}" class="input" required></div>
        <div><label class="text-sm text-ink-600 block mb-1">Slug</label><input type="text" name="slug" value="{{ old('slug', $tag->slug) }}" class="input"></div>
        <div><label class="text-sm text-ink-600 block mb-1">描述</label><textarea name="description" rows="3" class="input">{{ old('description', $tag->description) }}</textarea></div>
        <button class="btn-primary">保存</button>
    </form>
@endsection
