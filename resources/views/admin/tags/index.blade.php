@extends('admin.layouts.app')
@section('title', '标签')
@section('page_title', '标签管理')
@section('content')
    <div class="card mb-4 p-4 flex justify-end">
        <a href="{{ route('admin.tags.create') }}" class="btn-primary text-sm">+ 新建标签</a>
    </div>
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-ink-50 text-ink-600">
                <tr><th class="px-4 py-2.5 text-left">名称</th><th class="px-4 py-2.5 text-left">Slug</th><th class="px-4 py-2.5 text-left">内容数</th><th class="px-4 py-2.5 text-right">操作</th></tr>
            </thead>
            <tbody>
                @forelse($tags as $tag)
                    <tr class="border-t border-ink-100">
                        <td class="px-4 py-3 text-ink-700">{{ $tag->name }}</td>
                        <td class="px-4 py-3 text-ink-500 font-mono text-xs">{{ $tag->slug }}</td>
                        <td class="px-4 py-3 text-ink-500">{{ $tag->content_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.tags.edit', $tag) }}" class="btn-ghost text-xs">编辑</a>
                            <form method="POST" action="{{ route('admin.tags.destroy', $tag) }}" class="inline">@csrf @method('DELETE')<button class="btn-ghost text-xs text-red-500" onclick="return confirm('确认删除？')">删除</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-10 text-ink-400">暂无标签</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $tags->links() }}</div>
@endsection
