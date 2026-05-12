@extends('admin.layouts.app')
@section('title', '角色')
@section('page_title', '角色管理')
@section('content')
    <div class="card mb-4 p-4 flex justify-end">
        <a href="{{ route('admin.roles.create') }}" class="btn-primary text-sm">+ 新建角色</a>
    </div>
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-ink-50 text-ink-600">
                <tr><th class="px-4 py-2.5 text-left">角色</th><th class="px-4 py-2.5 text-left">权限数</th><th class="px-4 py-2.5 text-right">操作</th></tr>
            </thead>
            <tbody>
                @foreach($roles as $r)
                    <tr class="border-t border-ink-100">
                        <td class="px-4 py-3 font-medium text-ink-700">{{ $r->name }}</td>
                        <td class="px-4 py-3 text-ink-500">{{ $r->permissions->count() }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.roles.edit', $r) }}" class="btn-ghost text-xs">权限</a>
                            @if(!in_array($r->name, ['SUPER_ADMIN','USER']))
                                <form method="POST" action="{{ route('admin.roles.destroy', $r) }}" class="inline">@csrf @method('DELETE')<button class="btn-ghost text-xs text-red-500" onclick="return confirm('确认删除？')">删除</button></form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
