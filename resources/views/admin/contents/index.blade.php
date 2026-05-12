@extends('admin.layouts.app')

@section('title', '内容管理')
@section('page_title', '内容管理')

@section('content')
    <div class="card mb-4 p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-center">
            <select name="type" class="input w-32">
                <option value="">所有类型</option>
                @foreach(config('zfy.content_types') as $key => $cfg)
                    <option value="{{ $key }}" {{ request('type') === $key ? 'selected' : '' }}>{{ $cfg['label'] }}</option>
                @endforeach
            </select>
            <select name="status" class="input w-32">
                <option value="">所有状态</option>
                @foreach(['draft','pending','published','private','trash'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>
            <input type="text" name="q" value="{{ request('q') }}" class="input flex-1 min-w-[200px]" placeholder="搜索标题">
            <button class="btn-primary text-sm">筛选</button>
            <a href="{{ route('admin.contents.create') }}" class="btn-secondary text-sm">+ 新建内容</a>
        </form>
    </div>

    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-ink-50 text-ink-600">
                <tr>
                    <th class="px-4 py-2.5 text-left font-medium">标题</th>
                    <th class="px-4 py-2.5 text-left font-medium w-24">类型</th>
                    <th class="px-4 py-2.5 text-left font-medium w-24">状态</th>
                    <th class="px-4 py-2.5 text-left font-medium w-32">作者</th>
                    <th class="px-4 py-2.5 text-left font-medium w-28">浏览/评论</th>
                    <th class="px-4 py-2.5 text-left font-medium w-32">时间</th>
                    <th class="px-4 py-2.5 text-right font-medium w-32">操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contents as $c)
                    <tr class="border-t border-ink-100 hover:bg-ink-50/40">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.contents.edit', $c) }}" class="text-ink-700 hover:text-primary-600 line-clamp-1">{{ $c->title }}</a>
                            <div class="text-xs text-ink-400 truncate">{{ $c->slug }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="badge-muted">{{ config('zfy.content_types.' . $c->type . '.label', $c->type) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span @class([
                                'badge',
                                'bg-accent-50 text-accent-600' => $c->status === 'published',
                                'bg-vip-50 text-vip-600' => $c->status === 'pending',
                                'bg-ink-100 text-ink-500' => in_array($c->status, ['draft','trash']),
                                'bg-red-50 text-red-600' => $c->status === 'rejected',
                            ])>{{ $c->status }}</span>
                        </td>
                        <td class="px-4 py-3 text-ink-500">{{ $c->author?->name ?? '匿名' }}</td>
                        <td class="px-4 py-3 text-xs text-ink-500">{{ $c->view_count }} / {{ $c->comment_count }}</td>
                        <td class="px-4 py-3 text-xs text-ink-500">{{ $c->created_at->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('admin.contents.edit', $c) }}" class="btn-ghost text-xs">编辑</a>
                                @if($c->status !== 'published')
                                    <form method="POST" action="{{ route('admin.contents.publish', $c) }}" class="inline">@csrf<button class="btn-ghost text-xs text-accent-600">发布</button></form>
                                @else
                                    <form method="POST" action="{{ route('admin.contents.unpublish', $c) }}" class="inline">@csrf<button class="btn-ghost text-xs text-ink-500">撤下</button></form>
                                @endif
                                <form method="POST" action="{{ route('admin.contents.trash', $c) }}" class="inline">@csrf<button class="btn-ghost text-xs text-red-500">回收</button></form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-ink-400">暂无内容</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $contents->links() }}</div>
@endsection
