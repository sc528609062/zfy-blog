@extends('admin.layouts.app')
@section('title', $user->exists ? '编辑用户' : '新建用户')
@section('page_title', $user->exists ? '编辑用户' : '新建用户')
@section('content')
    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="card p-6 max-w-2xl space-y-4">
        @csrf
        @if($user->exists)@method('PUT')@endif
        <div class="grid grid-cols-2 gap-4">
            <div><label class="text-sm text-ink-600 block mb-1">姓名</label><input type="text" name="name" value="{{ old('name', $user->name) }}" class="input" required></div>
            <div><label class="text-sm text-ink-600 block mb-1">用户名</label><input type="text" name="username" value="{{ old('username', $user->username) }}" class="input" required></div>
            <div class="col-span-2"><label class="text-sm text-ink-600 block mb-1">邮箱</label><input type="email" name="email" value="{{ old('email', $user->email) }}" class="input" required></div>
            <div><label class="text-sm text-ink-600 block mb-1">密码 {{ $user->exists ? '（留空不变）' : '' }}</label><input type="password" name="password" class="input" {{ $user->exists ? '' : 'required' }}></div>
            <div><label class="text-sm text-ink-600 block mb-1">状态</label>
                <select name="status" class="input">
                    @foreach(['active' => '正常','banned' => '封禁','pending' => '待审'] as $k => $v)
                        <option value="{{ $k }}" {{ old('status', $user->status ?? 'active') === $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-2"><label class="text-sm text-ink-600 block mb-1">角色</label>
                <select name="role" class="input">
                    <option value="">— 普通用户 —</option>
                    @foreach($roles as $r)
                        <option value="{{ $r->name }}" {{ old('role', $user->roles->first()?->name) === $r->name ? 'selected' : '' }}>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <button class="btn-primary">保存</button>
    </form>
@endsection
