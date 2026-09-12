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
            <div class="a-auth-stats"><strong>{{ number_format(\App\Models\Content::published()->where('type', 'files')->count()) }}<span>资源</span></strong><strong>{{ number_format(\App\Models\User::count()) }}<span>注册用户</span></strong><strong>{{ number_format(\App\Models\Content::published()->where('type', 'post')->count()) }}<span>文章</span></strong></div>
            <div class="a-auth-collage"><span></span><span></span><span></span><span></span></div>
        </section>
        <form method="post" action="/login" class="a-auth-form">
            @csrf
            <h1>欢迎回来</h1>
            <p>登录 zfy-blog 继续你的游戏之旅</p>
            @if(session('status'))<p role="status">{{ session('status') }}</p>@endif
            <label>用户名或邮箱<input type="text" name="login" value="{{ old('login') }}" autocomplete="username" required></label>
            <label>密码<input type="password" name="password" autocomplete="current-password" required></label>
            <div class="a-form-row"><label><input type="checkbox" name="remember" value="1"> 记住我</label><a href="/forgot-password">忘记密码?</a></div>
            @error('login')<span class="a-error-text">{{ $message }}</span>@enderror
            @error('email')<span class="a-error-text">{{ $message }}</span>@enderror
            <button class="a-primary wide">登录</button>
            <p class="a-auth-switch">还没有账号？<a href="/register">立即注册</a></p>
        </form>
    </main>
    <footer class="a-auth-benefits"><a href="/">首页</a><a href="/vip">会员</a><a href="/links">友情链接</a></footer>
</body>
</html>
