@extends('admin.layouts.app')

@section('title', '仪表盘')
@section('page_title', '仪表盘')

@section('content')
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @php
            $cards = [
                ['label' => '注册用户',  'value' => $stats['users'],     'tone' => 'primary'],
                ['label' => '内容总数',  'value' => $stats['contents'],  'tone' => 'accent'],
                ['label' => '已发布',    'value' => $stats['published'], 'tone' => 'accent'],
                ['label' => '待审核',    'value' => $stats['pending'],   'tone' => 'vip'],
                ['label' => '评论总数',  'value' => $stats['comments'],  'tone' => 'primary'],
                ['label' => '订单总数',  'value' => $stats['orders'],    'tone' => 'primary'],
                ['label' => '已支付',    'value' => $stats['paid_orders'],'tone' => 'accent'],
            ];
        @endphp
        @foreach($cards as $c)
            <div class="card p-5">
                <div class="text-xs text-ink-500">{{ $c['label'] }}</div>
                <div @class([
                    'text-2xl font-bold mt-1',
                    'text-primary-600' => $c['tone'] === 'primary',
                    'text-accent-600' => $c['tone'] === 'accent',
                    'text-vip-600' => $c['tone'] === 'vip',
                ])>{{ $c['value'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-ink-900">最近内容</h3>
                <a href="{{ route('admin.contents.index') }}" class="text-xs text-primary-600">查看全部 →</a>
            </div>
            <ul class="divide-y divide-ink-100">
                @foreach($recentContents as $c)
                    <li class="py-2.5 flex items-center justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.contents.edit', $c) }}" class="text-sm text-ink-700 hover:text-primary-600 line-clamp-1">{{ $c->title }}</a>
                            <div class="text-xs text-ink-400">{{ $c->author?->name ?? '匿名' }} · {{ $c->created_at->diffForHumans() }}</div>
                        </div>
                        <span @class([
                            'badge-muted',
                            'bg-accent-50 text-accent-600' => $c->status === 'published',
                            'bg-vip-50 text-vip-600' => $c->status === 'pending',
                        ])>{{ $c->status }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-ink-900">最近用户</h3>
                <a href="{{ route('admin.users.index') }}" class="text-xs text-primary-600">查看全部 →</a>
            </div>
            <ul class="divide-y divide-ink-100">
                @foreach($recentUsers as $u)
                    <li class="py-2.5 flex items-center gap-3">
                        <img src="{{ $u->avatar_url }}" class="w-8 h-8 rounded-full" alt="">
                        <div class="flex-1 min-w-0">
                            <div class="text-sm text-ink-700">{{ $u->name }}</div>
                            <div class="text-xs text-ink-400">{{ $u->email }}</div>
                        </div>
                        <span class="text-xs text-ink-400">{{ $u->created_at->diffForHumans() }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endsection
