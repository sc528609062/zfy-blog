@extends('admin.layouts.app')
@section('title', '用户')
@section('page_title', '用户管理')
@section('content')
    <div class="card mb-4 p-4 flex justify-between gap-3">
        <form method="GET" class="flex gap-2 flex-1 max-w-md">
            <input type="text" name="q" value="{{ request('q') }}" class="input" placeholder="搜索用户名 / 邮箱 / 姓名">
            <button class="btn-primary text-sm">搜索</button>
        </form>
        <a href="{{ route('admin.users.create') }}" class="btn-primary text-sm">+ 新建用户</a>
    </div>
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-ink-50 text-ink-600">
                <tr>
                    <th class="px-4 py-2.5 text-left">用户</th>
                    <th class="px-4 py-2.5 text-left w-32">角色</th>
                    <th class="px-4 py-2.5 text-left w-24">状态</th>
                    <th class="px-4 py-2.5 text-left w-32">注册时间</th>
                    <th class="px-4 py-2.5 text-right w-24">操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $u)
                    <tr class="border-t border-ink-100 hover:bg-ink-50/40">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <img src="{{ $u->avatar_url }}" class="w-8 h-8 rounded-full" alt="">
                                <div class="min-w-0">
                                    <div class="font-medium text-ink-700">{{ $u->name }}</div>
                                    <div class="text-xs text-ink-400">{{ $u->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @foreach($u->roles as $r)<span class="badge-primary">{{ $r->name }}</span>@endforeach
                        </td>
                        <td class="px-4 py-3">
                            <span @class([
                                'badge',
                                'bg-accent-50 text-accent-600' => $u->status === 'active',
                                'bg-red-50 text-red-600' => $u->status === 'banned',
                                'bg-vip-50 text-vip-600' => $u->status === 'pending',
                            ])>{{ $u->status }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-ink-500">{{ $u->created_at->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.users.edit', $u) }}" class="btn-ghost text-xs">编辑</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
@endsection
