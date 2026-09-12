<section class="a-account-main">
    <div class="a-section-title"><h1>VIP会员</h1><a href="/user/vip">我的会员</a></div>
    <div class="a-plan-grid">
        @forelse($vipLevels as $level)
            <article class="a-plan-card">
                <h2>{{ $level->name }}</h2>
                <strong>¥{{ $level->price_monthly }}<span>/月</span></strong>
                <p>年费 ¥{{ $level->price_yearly }}</p>
                @foreach($level->benefits ?? [] as $benefit)<p>{{ $benefit }}</p>@endforeach
                @auth
                    <form method="post" action="{{ route('vip.buy', $level) }}">@csrf
                        <p><label>周期 <select name="period">@include('themes.shared.partials.vip-periods')</select></label></p>
                        <p><label>支付方式 <select name="gateway">@include('themes.shared.partials.payment-options')</select></label></p>
                        <button class="a-primary">购买会员</button>
                    </form>
                @else
                    <a class="a-primary" href="{{ route('login') }}">登录购买</a>
                @endauth
            </article>
        @empty
            <p>暂无可购买的会员套餐。</p>
        @endforelse
    </div>
</section>
