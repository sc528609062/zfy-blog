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
            @if($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
            <label><span>邮箱</span><input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required maxlength="160"></label>
            <label><span>昵称</span><input name="name" value="{{ old('name') }}" autocomplete="nickname" required maxlength="80"></label>
            <label><span>密码</span><input type="password" name="password" autocomplete="new-password" minlength="12" maxlength="128" required></label>
            <label><span>确认密码</span><input type="password" name="password_confirmation" autocomplete="new-password" minlength="12" maxlength="128" required></label>
            <label><span>邀请码</span><input name="invite_code" value="{{ old('invite_code', request('invite_code')) }}" maxlength="80" @required(app(\App\Services\SiteSettings::class)->get('registration.invite_required', false))></label>
            <button class="a-primary wide">立即注册</button>
            <p class="a-auth-switch">已有账号？<a href="/login">立即登录</a></p>
        </form>
        <aside class="a-register-perks">
            <h2>注册即享专属福利</h2>
            @foreach(\App\Models\VipLevel::orderBy('level')->get() as $level)
                <div><strong>{{ $level->name }}</strong><p>{{ implode(' · ', $level->benefits ?? []) }}</p><span>¥{{ $level->price_monthly }}/月</span></div>
            @endforeach
        </aside>
    </main>
    <footer class="a-auth-benefits"><a href="/">首页</a><a href="/vip">会员</a><a href="/links">友情链接</a></footer>
</body>
</html>
