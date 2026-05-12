@extends('layouts.app')
@section('title', 'VIP 会员')
@section('content')
    @php $levels = \App\Models\VipLevel::where('enabled', true)->orderBy('sort_order')->get(); @endphp

    <section class="bg-gradient-to-br from-vip-50 via-white to-primary-50 py-16">
        <div class="max-w-5xl mx-auto px-4 text-center">
            <h1 class="text-3xl md:text-4xl font-bold text-ink-900 mb-3">
                <span class="bg-gradient-to-r from-vip-500 to-primary-600 bg-clip-text text-transparent">{{ $site['name'] ?? 'zfy-blog' }} VIP</span>
            </h1>
            <p class="text-ink-500 max-w-xl mx-auto">解锁全站精品内容，享每日免费下载额度、折扣与专属客服</p>
        </div>
    </section>

    <div class="max-w-6xl mx-auto px-4 -mt-10 pb-16">
        <div class="grid md:grid-cols-3 gap-6">
            @foreach($levels as $lv)
                <div class="card p-6 relative {{ $loop->index === 1 ? 'ring-2 ring-vip-500 shadow-lg scale-105' : '' }}">
                    @if($loop->index === 1)
                        <span class="badge-vip absolute -top-3 left-1/2 -translate-x-1/2">推荐</span>
                    @endif
                    <h3 class="text-lg font-bold text-ink-900 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full" style="background: {{ $lv->color }}"></span>
                        {{ $lv->name }}
                    </h3>
                    <div class="mt-3">
                        <span class="text-3xl font-bold text-ink-900">¥{{ $lv->price }}</span>
                        <span class="text-sm text-ink-500">/ {{ $lv->duration_days ? $lv->duration_days.'天' : '永久' }}</span>
                    </div>
                    <ul class="mt-5 space-y-2 text-sm text-ink-600">
                        @foreach(($lv->benefits ?? []) as $b)
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-accent-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                {{ $b }}
                            </li>
                        @endforeach
                    </ul>
                    <button class="btn-vip w-full mt-6" disabled>开通（Sprint 4 上线）</button>
                </div>
            @endforeach
        </div>
        <p class="text-center text-xs text-ink-400 mt-8">支付流程将在 Sprint 4 / M8 完整接入（支付宝/微信/虎皮椒/易支付）</p>
    </div>
@endsection
