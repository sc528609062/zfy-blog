<!doctype html>
@php
    $activeTheme = app(\App\Services\ThemeManager::class)->active();
    $authTone = match ($activeTheme['slug'] ?? 'style-a-blue-gaming') {
        'style-b-marketplace' => 'market',
        'style-c-creative' => 'creative',
        default => 'blue',
    };
@endphp
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>注册 zfy-blog</title>
    @vite(['resources/css/app.css'])
</head>
<body class="a-auth-body register auth-{{ $authTone }}">
    <header class="a-topbar auth">
        <a class="a-brand" href="/"><span class="a-brand-mark">Z</span><span><strong>zfy-blog</strong><small>游戏资源 · 社区 · 攻略</small></span></a>
        <nav class="a-nav"><a href="/">首页</a><a href="/files">游戏资源</a><a href="/posts">攻略资讯</a><a href="/images">MOD专区</a><a href="/rank">排行榜</a></nav>
        <form class="a-search" action="/search"><input name="q" placeholder="搜索游戏、资源、攻略..."><button>⌕</button></form>
        <a class="a-vip-chip" href="/vip">开通VIP</a>
    </header>
    <main class="a-register-shell">
        <aside class="a-register-art"></aside>
        <form method="post" action="/register" class="a-register-form">
            @csrf
            <h1>{{ $authTone === 'creative' ? '欢迎加入创意社区' : '欢迎加入 zfy-blog' }}</h1>
            <p>{{ $authTone === 'market' ? '创建账号，购买源码、素材和教程资源' : ($authTone === 'creative' ? '创建账号，连接创作者和优质灵感资源' : '创建账号，解锁更多游戏资源与社区体验') }}</p>
            <label><span>1. 邮箱验证</span><input type="email" name="email" value="player_zfy@protonmail.com" required></label>
            <label><span>2. 设置用户名</span><input name="name" value="Zfy_玩家小白" required></label>
            <label><span>3. 设置密码</span><input type="password" name="password" value="zfy-blog-123456" required></label>
            <label><span>4. 确认密码</span><input type="password" name="password_confirmation" value="zfy-blog-123456"></label>
            <label class="a-check"><input type="checkbox" checked> 我已阅读并同意用户协议和隐私政策</label>
            <button class="a-primary wide">立即注册</button>
            <p class="a-auth-switch">已有账号？<a href="/login">立即登录</a></p>
        </form>
        <aside class="a-register-perks">
            <h2>注册即享专属福利</h2>
            @foreach(['新手礼包','高速下载','专属权限','社区互动','积分奖励'] as $perk)
                <div><strong>{{ $perk }}</strong><p>解锁更多游戏资源、隐藏内容和优先客服支持。</p><span>{{ $loop->first ? '价值 88元' : '尊享特权' }}</span></div>
            @endforeach
        </aside>
    </main>
    <footer class="a-auth-benefits"><div>安全可靠<span>多重安全防护</span></div><div>资源丰富<span>100,000+ 优质资源</span></div><div>更新及时<span>每日更新最新内容</span></div><div>优质服务<span>7x24小时客服支持</span></div></footer>
</body>
</html>
