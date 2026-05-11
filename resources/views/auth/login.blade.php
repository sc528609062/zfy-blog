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
    <title>登录 zfy-blog</title>
    @vite(['resources/css/app.css'])
</head>
<body class="a-auth-body auth-{{ $authTone }}">
    <header class="a-topbar auth">
        <a class="a-brand" href="/"><span class="a-brand-mark">Z</span><span><strong>zfy-blog</strong><small>游戏资源社区</small></span></a>
        <nav class="a-nav"><a href="/">首页</a><a href="/files">游戏库</a><a href="/posts">攻略中心</a><a href="/vip">会员中心</a></nav>
        <form class="a-search" action="/search"><input name="q" placeholder="搜索游戏、资源、攻略..."><button>⌕</button></form>
        <a class="a-vip-chip" href="/vip">开通VIP</a>
    </header>
    <main class="a-auth-shell">
        <section class="a-auth-visual">
            <h1>zfy-blog</h1>
            <p>{{ $authTone === 'market' ? '资源交易服务平台' : ($authTone === 'creative' ? '创意资源社区' : '游戏资源社区') }}</p>
            <div class="a-auth-stats"><strong>18,256+<span>游戏资源</span></strong><strong>128,560+<span>注册用户</span></strong><strong>45,360+<span>社区帖子</span></strong></div>
            <div class="a-auth-collage"><span></span><span></span><span></span><span></span></div>
        </section>
        <form method="post" action="/login" class="a-auth-form">
            @csrf
            <h1>欢迎回来</h1>
            <p>登录 zfy-blog 继续你的游戏之旅</p>
            <div class="a-auth-tabs"><button type="button" class="active">账号密码登录</button><button type="button">手机验证码登录</button></div>
            <label>邮箱地址<input type="email" name="email" value="admin@zfy-blog.test" required></label>
            <label>密码<input type="password" name="password" value="zfy-blog-123456" required></label>
            <div class="a-form-row"><label><input type="checkbox" checked> 记住我</label><a href="/register">忘记密码?</a></div>
            @error('email')<span class="a-error-text">{{ $message }}</span>@enderror
            <button class="a-primary wide">登录</button>
            <div class="a-social-login"><span>QQ登录</span><span>微信登录</span><span>微博登录</span><span>Steam登录</span></div>
            <p class="a-auth-switch">还没有账号？<a href="/register">立即注册</a></p>
        </form>
    </main>
    <footer class="a-auth-benefits"><div>安全可靠<span>多重安全验证</span></div><div>高速下载<span>多线路资源站点</span></div><div>优质资源<span>严格筛选审核</span></div><div>活跃社区<span>万千玩家交流</span></div></footer>
</body>
</html>
