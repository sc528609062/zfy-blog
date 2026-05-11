<section class="page-title">
    <h1>VIP 会员中心</h1>
    <p>支持多级 VIP、折扣比例、独立优惠金额、付费内容与下载权限联动。</p>
</section>
<section class="pricing-grid">
    @foreach($vipLevels as $level)
        <div class="pricing-card {{ $loop->last ? 'featured' : '' }}">
            <h2>{{ $level->name }}</h2>
            <strong>¥{{ $level->price_yearly }}<span>/年</span></strong>
            <p>月付 ¥{{ $level->price_monthly }} · {{ $level->discount_percent }}% 折扣</p>
            @foreach($level->benefits ?? [] as $benefit)
                <div class="benefit">✓ {{ $benefit }}</div>
            @endforeach
            <button class="primary-btn">开通会员</button>
        </div>
    @endforeach
</section>
