<aside class="a-account-menu">
    <a class="a-account-title" href="/user"><span></span><strong>个人中心</strong><small>管理您的账号与内容</small></a>
    @foreach([
        '/user' => '概览',
        '/user/wallet' => '钱包与充值',
        '/user/vip' => '我的VIP',
        '/user/orders' => '我的订单',
        '/user/downloads' => '我的下载',
        '/user/points' => '积分中心',
        '/user/settings' => '用户设置',
        '/user/author' => '作者工作台',
    ] as $href => $label)
        <a href="{{ $href }}" class="{{ request()->path() === trim($href, '/') ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
    <div class="a-vip-ad dark"><strong>升级VIP会员</strong><p>尊享更多专属特权</p><a href="/vip">立即开通</a></div>
</aside>
