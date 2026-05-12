@extends('admin.layouts.app')
@section('title', '角色权限')
@section('page_title', '角色权限：' . $role->name)
@section('content')
    <form method="POST" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}" class="card p-6 max-w-3xl">
        @csrf
        @if($role->exists)@method('PUT')@endif

        @if(!$role->exists)
            <div class="mb-4"><label class="text-sm text-ink-600 block mb-1">角色名</label><input type="text" name="name" class="input" required></div>
        @endif

        <h3 class="font-semibold text-ink-900 mb-3">权限</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-2 text-sm max-h-96 overflow-y-auto">
            @foreach($permissions as $perm)
                <label class="flex items-center gap-2 px-2 py-1 hover:bg-ink-50 rounded">
                    <input type="checkbox" name="permissions[]" value="{{ $perm->name }}"
                           {{ $role->permissions->contains('id', $perm->id) ? 'checked' : '' }}>
                    <span class="text-ink-600 text-xs font-mono">{{ $perm->name }}</span>
                </label>
            @endforeach
        </div>
        <button class="btn-primary mt-4">保存</button>
    </form>
@endsection
