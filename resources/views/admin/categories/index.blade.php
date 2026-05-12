@extends('admin.layouts.app')
@section('title', '分类')
@section('page_title', '分类管理')
@section('content')
    <div class="card mb-4 p-4 flex justify-end">
        <a href="{{ route('admin.categories.create') }}" class="btn-primary text-sm">+ 新建分类</a>
    </div>
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-ink-50 text-ink-600">
                <tr>
                    <th class="px-4 py-2.5 text-left font-medium">名称</th>
                    <th class="px-4 py-2.5 text-left font-medium">Slug</th>
                    <th class="px-4 py-2.5 text-left font-medium w-24">内容数</th>
                    <th class="px-4 py-2.5 text-left font-medium w-20">排序</th>
                    <th class="px-4 py-2.5 text-right font-medium w-32">操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $cat)
                    <tr class="border-t border-ink-100 hover:bg-ink-50/40">
                        <td class="px-4 py-3 text-ink-700">@if($cat->parent_id)<span class="text-ink-400">└</span> @endif{{ $cat->name }}</td>
                        <td class="px-4 py-3 text-ink-500 font-mono text-xs">{{ $cat->slug }}</td>
                        <td class="px-4 py-3 text-ink-500">{{ $cat->content_count }}</td>
                        <td class="px-4 py-3 text-ink-500">{{ $cat->sort_order }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.categories.edit', $cat) }}" class="btn-ghost text-xs">编辑</a>
                            <form method="POST" action="{{ route('admin.categories.destroy', $cat) }}" class="inline">
                                @csrf @method('DELETE')
                                <button class="btn-ghost text-xs text-red-500" onclick="return confirm('确认删除？')">删除</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-12 text-ink-400">暂无分类</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
