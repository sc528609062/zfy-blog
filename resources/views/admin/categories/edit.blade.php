@extends('admin.layouts.app')
@section('title', $category->exists ? '编辑分类' : '新建分类')
@section('page_title', $category->exists ? '编辑分类' : '新建分类')
@section('content')
    <form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="card p-6 max-w-2xl space-y-4">
        @csrf
        @if($category->exists) @method('PUT') @endif
        <div>
            <label class="text-sm text-ink-600 block mb-1">名称</label>
            <input type="text" name="name" value="{{ old('name', $category->name) }}" class="input" required>
        </div>
        <div>
            <label class="text-sm text-ink-600 block mb-1">Slug</label>
            <input type="text" name="slug" value="{{ old('slug', $category->slug) }}" class="input">
        </div>
        <div>
            <label class="text-sm text-ink-600 block mb-1">父级分类</label>
            <select name="parent_id" class="input">
                <option value="">— 顶级 —</option>
                @foreach($parents as $p)
                    <option value="{{ $p->id }}" {{ old('parent_id', $category->parent_id) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-sm text-ink-600 block mb-1">描述</label>
            <textarea name="description" rows="3" class="input">{{ old('description', $category->description) }}</textarea>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-sm text-ink-600 block mb-1">图标</label>
                <input type="text" name="icon" value="{{ old('icon', $category->icon) }}" class="input">
            </div>
            <div>
                <label class="text-sm text-ink-600 block mb-1">排序</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="input">
            </div>
        </div>
        <div class="pt-2">
            <button class="btn-primary">保存</button>
            <a href="{{ route('admin.categories.index') }}" class="btn-ghost">取消</a>
        </div>
    </form>
@endsection
