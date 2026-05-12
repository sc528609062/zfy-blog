@extends('layouts.app')
@section('title', '我的中心')
@section('content')
    <div class="max-w-5xl mx-auto px-4 py-8">
        <div class="card p-6 mb-6 flex items-center gap-5">
            <img src="{{ $user->avatar_url }}" class="w-16 h-16 rounded-full" alt="">
            <div class="flex-1 min-w-0">
                <div class="text-xl font-semibold text-ink-900">{{ $user->name }}</div>
                <div class="text-sm text-ink-500 truncate">{{ $user->bio ?? '这位创作者还没填写简介' }}</div>
                <div class="flex flex-wrap gap-2 mt-2 text-xs">
                    @if($user->isVip())
                        <span class="badge-vip">VIP</span>
                    @endif
                    @if($user->roles)
                        @foreach($user->roles as $r)
                            <span class="badge-primary">{{ $r->name }}</span>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="card p-5">
                <div class="text-xs text-ink-500">钱包余额</div>
                <div class="text-2xl font-bold text-ink-900 mt-1">¥{{ optional($user->wallet)->balance ?? '0.00' }}</div>
                <a href="{{ route('user.wallet') }}" class="text-xs text-primary-600 mt-2 inline-block">查看 →</a>
            </div>
            <div class="card p-5">
                <div class="text-xs text-ink-500">积分余额</div>
                <div class="text-2xl font-bold text-vip-600 mt-1">{{ optional($user->pointsAccount)->balance ?? 0 }}</div>
                <a href="{{ route('user.points') }}" class="text-xs text-primary-600 mt-2 inline-block">查看 →</a>
            </div>
            <div class="card p-5">
                <div class="text-xs text-ink-500">VIP 状态</div>
                <div class="text-lg font-bold text-ink-900 mt-1">
                    @php $vip = $user->activeVip->first(); @endphp
                    {{ $vip ? $vip->level->name : '未开通' }}
                </div>
                <a href="{{ route('user.vip') }}" class="text-xs text-primary-600 mt-2 inline-block">详情 →</a>
            </div>
            <div class="card p-5">
                <div class="text-xs text-ink-500">作者中心</div>
                <div class="text-sm text-ink-700 mt-1">投稿、收益、提现</div>
                <a href="{{ route('user.author') }}" class="text-xs text-primary-600 mt-2 inline-block">进入 →</a>
            </div>
        </div>

        <div class="card p-6">
            <h3 class="font-semibold text-ink-900 mb-4">最近活动</h3>
            <p class="text-sm text-ink-500">活动流将在 Sprint 2 / M3 完整接入</p>
        </div>
    </div>
@endsection
