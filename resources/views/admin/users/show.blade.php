@extends('admin.layouts.app')
@section('title', '用户详情')
@section('page_title', $user->name)
@section('content')
    <div class="card p-6 max-w-3xl">
        <div class="flex items-center gap-5">
            <img src="{{ $user->avatar_url }}" class="w-16 h-16 rounded-full" alt="">
            <div>
                <div class="text-xl font-semibold text-ink-900">{{ $user->name }}</div>
                <div class="text-sm text-ink-500">{{ $user->email }}</div>
                <div class="mt-2 flex gap-2">
                    @foreach($user->roles as $r)<span class="badge-primary">{{ $r->name }}</span>@endforeach
                </div>
            </div>
            <div class="ml-auto"><a href="{{ route('admin.users.edit', $user) }}" class="btn-secondary text-sm">编辑</a></div>
        </div>
    </div>
@endsection
