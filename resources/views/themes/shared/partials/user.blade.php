<section class="dashboard-shell">
    <aside class="user-menu">
        <strong>用户中心</strong>
        <a href="/user">概览</a>
        <a href="/user/orders">我的订单</a>
        <a href="/user/downloads">我的下载</a>
        <a href="/user/wallet">钱包余额</a>
        <a href="/user/points">积分明细</a>
        <a href="/user/vip">我的会员</a>
        <a href="/user/settings">用户设置</a>
        <a href="/user/author">作者工作台</a>
    </aside>
    <section class="dashboard-main">
        <div class="page-title compact">
            <h1>{{ [
                'user-overview' => '用户中心概览',
                'user-orders' => '我的订单',
                'user-downloads' => '我的下载',
                'user-wallet' => '钱包余额',
                'user-points' => '积分明细',
                'user-vip' => '我的会员',
                'user-settings' => '用户设置',
                'author-workspace' => '作者工作台',
            ][$page] ?? '用户中心' }}</h1>
            <p>钱包、积分、VIP、下载权限、作者收益和提现流程已接入统一业务模型。</p>
        </div>
        <div class="stat-grid">
            <div><span>余额</span><strong>¥888.00</strong></div>
            <div><span>积分</span><strong>5200</strong></div>
            <div><span>VIP</span><strong>SVIP</strong></div>
            <div><span>下载</span><strong>128</strong></div>
        </div>
        <div class="panel">
            <h3>最近记录</h3>
            @foreach($contents->take(6) as $item)
                <a class="filter-row" href="/content/{{ $item->slug }}">{{ $item->title }} <span>{{ $item->type }}</span></a>
            @endforeach
        </div>
    </section>
</section>
