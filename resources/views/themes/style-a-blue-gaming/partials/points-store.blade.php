@php
    $tone = $themeTone ?? 'blue';
@endphp

<section class="a-page-hero compact">
    <h1>积分商城</h1>
    <p>使用积分兑换资源、优惠券和会员权益</p>
</section>

<section class="a-two-col">
    <div class="a-card-panel">
        <div class="a-section-title">
            <h2>积分商品</h2>
            <div class="a-user-points">
                <span>我的积分</span>
                <strong>8,950</strong>
            </div>
        </div>

        <div class="a-tabs-head">
            <nav>
                <a class="active">全部商品</a>
                <a>优惠券</a>
                <a>会员权益</a>
                <a>实物商品</a>
                <a>虚拟商品</a>
            </nav>
        </div>

        <div class="a-points-grid">
            @foreach([
                ['VIP月卡', 1500, 'vip-card.png', 'VIP会员1个月'],
                ['20元优惠券', 800, 'coupon-20.png', '全站通用'],
                ['50元优惠券', 1800, 'coupon-50.png', '全站通用'],
                ['100元优惠券', 3500, 'coupon-100.png', '全站通用'],
                ['精品资源包', 2000, 'resource-pack.png', '10个精选资源'],
                ['高速下载券', 500, 'download-ticket.png', '7天高速下载'],
            ] as [$name, $points, $image, $desc])
                <article class="a-points-item">
                    <img src="{{ asset('theme-assets/points-'.$image) }}" alt="{{ $name }}" onerror="this.src='{{ asset('theme-assets/a-home-hero.png') }}'">
                    <h3>{{ $name }}</h3>
                    <p>{{ $desc }}</p>
                    <div class="a-points-price">
                        <strong>{{ number_format($points) }}</strong>
                        <span>积分</span>
                    </div>
                    <button class="a-primary">立即兑换</button>
                </article>
            @endforeach
        </div>
    </div>

    <aside class="a-side-stack">
        <div class="a-card-panel">
            <div class="a-section-title"><h3>我的积分</h3></div>
            <div class="a-stat-cards compact">
                <div>
                    <span>当前积分</span>
                    <strong>8,950</strong>
                </div>
                <div>
                    <span>累计获得</span>
                    <strong>12,560</strong>
                </div>
                <div>
                    <span>累计消费</span>
                    <strong>3,610</strong>
                </div>
            </div>
            <a href="/user/points" class="a-primary">积分明细</a>
        </div>

        <div class="a-card-panel">
            <div class="a-section-title"><h3>获取积分</h3></div>
            <ul class="a-simple-list">
                <li>每日签到 +10 积分</li>
                <li>发布内容 +50 积分</li>
                <li>评论互动 +5 积分</li>
                <li>分享内容 +3 积分</li>
                <li>邀请好友 +100 积分</li>
            </ul>
            <button class="a-primary">每日签到</button>
        </div>

        <div class="a-card-panel">
            <div class="a-section-title"><h3>兑换记录</h3></div>
            <div class="a-exchange-history">
                @foreach(['VIP月卡', '20元优惠券', '精品资源包'] as $item)
                    <article>
                        <strong>{{ $item }}</strong>
                        <span>-{{ rand(500, 2000) }} 积分</span>
                        <time>2026-05-{{ rand(1, 11) }}</time>
                    </article>
                @endforeach
            </div>
            <a href="/user/points">查看全部</a>
        </div>
    </aside>
</section>
