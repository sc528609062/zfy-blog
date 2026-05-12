@php
    $titles = [
        'user-overview' => '个人中心概览',
        'user-orders' => '我的订单',
        'user-downloads' => '我的下载',
        'user-wallet' => '钱包与充值',
        'user-points' => '积分中心',
        'user-vip' => '我的会员',
        'author-workspace' => '作者工作台',
    ];
    $title = $titles[$page] ?? '个人中心';
@endphp

<section class="a-account-layout">
    @include('themes.style-a-blue-gaming.partials.account-menu')
    <div class="a-account-main">
        <section class="a-user-hero">
            <img src="/assets/zfy/placeholders/avatar.svg" alt="用户头像">
            <div>
                <h1>风之旅人 <span>VIP</span></h1>
                <p>UID: 10002345 · 经验值 8250 / 12000</p>
                <div class="a-progress"><i style="width:70%"></i></div>
            </div>
            <div class="a-user-stats"><strong>24<span>发布帖子</span></strong><strong>156<span>获得点赞</span></strong><strong>89<span>收藏资源</span></strong><strong>12<span>关注用户</span></strong></div>
            <button>编辑资料</button>
        </section>

        <section class="a-stat-cards">
            <div><span>我的钱包</span><strong>¥125.50</strong><p>金币 2,860</p><a href="/user/wallet">充值</a></div>
            <div><span>积分中心</span><strong>8,950</strong><p>今日签到 +10</p><a href="/user/points">签到</a></div>
            <div><span>我的VIP</span><strong>VIP</strong><p>有效期至 2025-12-31</p><a href="/user/vip">查看特权</a></div>
            <div><span>我的优惠券</span><strong>5</strong><p>即将过期 2 张</p><a href="/points-store">查看优惠券</a></div>
        </section>

        @if($page === 'author-workspace')
            <section class="a-card-panel">
                <div class="a-section-title"><h2>作者工作台</h2><a href="/admin/editor">发布资源</a></div>
                <div class="a-stat-cards compact"><div><span>总收益</span><strong>¥8,926</strong></div><div><span>下载量</span><strong>32,680</strong></div><div><span>粉丝</span><strong>12,560</strong></div><div><span>待审核</span><strong>6</strong></div></div>
            </section>
        @elseif($page === 'user-vip')
            <section class="a-card-panel">
                <div class="a-section-title"><h2>我的会员</h2><a href="/vip">续费会员</a></div>
                <div class="a-stat-cards compact">
                    <div><span>当前等级</span><strong>VIP</strong><p>年度会员</p></div>
                    <div><span>到期时间</span><strong>2025-12-31</strong><p>剩余 234 天</p></div>
                    <div><span>会员折扣</span><strong>8折</strong><p>付费资源自动优惠</p></div>
                    <div><span>免费下载</span><strong>128</strong><p>本月剩余额度</p></div>
                </div>
            </section>
            <section class="a-card-panel">
                <div class="a-section-title"><h2>会员特权</h2></div>
                <div class="a-perk-grid">
                    @foreach(['高速下载','资源抢先','专属标识','无限下载','专属客服','积分加成','去广告','更多特权'] as $perk)
                        <div><span>{{ mb_substr($perk, 0, 1) }}</span><strong>{{ $perk }}</strong><p>当前账号已解锁</p></div>
                    @endforeach
                </div>
            </section>
        @else
            <section class="a-dashboard-grid">
                <div class="a-card-panel">
                    <div class="a-section-title"><h2>{{ in_array($page, ['user-orders','user-wallet','user-points'], true) ? $title : '最近订单' }}</h2><a href="/user/orders">全部订单</a></div>
                    <table class="a-ui-table">
                        <thead><tr><th>订单号</th><th>商品</th><th>金额</th><th>状态</th><th>时间</th></tr></thead>
                        <tbody>
                        @foreach($contents->take(5) as $item)
                            <tr><td>20250524{{ str_pad((string)$loop->iteration, 3, '0', STR_PAD_LEFT) }}</td><td>{{ $item->title }}</td><td>¥{{ data_get($item, 'pricing.price', 28) ?: 15 }}</td><td><span class="a-ok">已完成</span></td><td>2025-05-{{ 24 - $loop->index }}</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="a-card-panel">
                    <div class="a-section-title"><h2>{{ $page === 'user-downloads' ? '我的下载' : '最近下载' }}</h2><a href="/user/downloads">全部下载</a></div>
                    <table class="a-ui-table">
                        <thead><tr><th>资源名称</th><th>大小</th><th>进度</th><th>时间</th></tr></thead>
                        <tbody>
                        @foreach($resources->take(5) as $item)
                            <tr><td>{{ $item->title }}</td><td>{{ rand(2, 56) }}.{{ rand(0, 9) }} GB</td><td><span class="a-ok">已完成</span></td><td>2025-05-{{ 24 - $loop->index }}</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</section>
