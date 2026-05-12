@php
    /** @var \App\Domain\Content\AccessDecision $decision */
    /** @var \App\Models\Content $content */
@endphp

<div class="prose max-w-none text-ink-700 mb-6">
    {{ $content->excerpt ?: '本内容受访问限制，登录或满足条件后可见完整内容。' }}
</div>

<div class="mt-6 p-6 rounded-2xl border-2 border-dashed border-primary-200 bg-primary-50/40 text-center">
    @switch($decision->lock)
        @case('login')
            <div class="text-primary-700 font-semibold mb-2">需要登录后查看</div>
            <p class="text-sm text-ink-500 mb-4">登录账号后即可阅读完整内容</p>
            <div class="flex justify-center gap-2">
                <a href="{{ route('login') }}" class="btn-primary text-sm">立即登录</a>
                <a href="{{ route('register') }}" class="btn-secondary text-sm">注册账号</a>
            </div>
            @break
        @case('vip')
            <div class="text-vip-600 font-semibold mb-2">VIP 专享内容</div>
            <p class="text-sm text-ink-500 mb-4">开通 VIP 会员即可永久阅读</p>
            <a href="{{ route('vip.index') }}" class="btn-vip text-sm">开通 VIP</a>
            @break
        @case('buy')
            <div class="text-red-600 font-semibold mb-2">付费内容</div>
            <p class="text-sm text-ink-500 mb-1">单独购买：<span class="text-2xl font-bold text-red-600">¥{{ $content->price }}</span></p>
            @if($content->points_price > 0)
                <p class="text-sm text-ink-500 mb-4">或使用 {{ $content->points_price }} 积分兑换</p>
            @endif
            <p class="text-xs text-ink-400 mb-4">Sprint 4 (M8) 完整支付流程</p>
            <button class="btn-primary text-sm" disabled>立即购买（即将开放）</button>
            @break
        @case('comment')
            <div class="text-accent-600 font-semibold mb-2">评论后可见</div>
            <p class="text-sm text-ink-500 mb-4">登录后留下你的评论，即可解锁完整内容</p>
            @auth
                <a href="#comments" class="btn-primary text-sm">前往评论区</a>
            @else
                <a href="{{ route('login') }}" class="btn-primary text-sm">立即登录</a>
            @endauth
            @break
        @case('password')
            <div class="text-primary-700 font-semibold mb-2">密码访问</div>
            <form method="POST" action="{{ route('content.unlock', $content->slug) }}" class="flex justify-center gap-2 max-w-md mx-auto">
                @csrf
                <input type="password" name="password" placeholder="访问密码" class="input flex-1" required>
                <button class="btn-primary text-sm">解锁</button>
            </form>
            @break
        @default
            <div class="text-ink-500">该内容当前不可访问</div>
    @endswitch
</div>
