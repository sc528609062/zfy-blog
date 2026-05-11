<header class="a-topbar">
    <a class="a-brand" href="/">
        <span class="a-brand-mark">{{ ($themeTone ?? 'blue') === 'creative' ? '★' : (($themeTone ?? 'blue') === 'blue' ? '🎮' : 'Z') }}</span>
        <span>
            <strong>zfy-blog</strong>
            <small>{{ ($themeTone ?? 'blue') === 'market' ? '精品源码 · 技术分享' : (($themeTone ?? 'blue') === 'creative' ? '创意资源 · 社区' : '游戏资源社区') }}</small>
        </span>
    </a>

    <nav class="a-nav">
        @php
            $navItems = match ($themeTone ?? 'blue') {
                'market' => ['/' => '首页', '/files' => '资源商城', '/posts' => '教程中心', '/rank' => '排行榜', '/vip' => '会员中心', '/links' => '帮助中心'],
                'creative' => ['/' => '首页', '/images' => '资源', '/posts' => '教程', '/files' => '工具', '/authors' => '社区', '/vip' => '活动'],
                default => ['/' => '首页', '/files' => '游戏资源', '/posts' => '攻略教程', '/images' => 'MOD社区', '/rank' => '排行榜', '/vip' => '会员中心'],
            };
        @endphp
        @foreach($navItems as $href => $label)
            <a href="{{ $href }}" class="{{ request()->path() === trim($href, '/') || ($href === '/' && request()->is('/')) ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </nav>

    <form class="a-search" action="/search">
        <input name="q" value="{{ request('q') }}" placeholder="{{ ($themeTone ?? 'blue') === 'market' ? '搜索资源/教程/文章' : (($themeTone ?? 'blue') === 'creative' ? '搜索资源、教程、用户、帖子...' : '搜索游戏、资源、攻略...') }}">
        <button aria-label="搜索">⌕</button>
    </form>

    <a class="a-publish" href="/admin/editor">{{ ($themeTone ?? 'blue') === 'market' ? '发布资源' : '发布' }}</a>
    <a class="a-vip-chip" href="/vip">{{ ($themeTone ?? 'blue') === 'creative' ? 'VIP' : '开通VIP' }}</a>
    <a class="a-bell" href="/user">12</a>
    <a class="a-avatar" href="/user">
        <img src="{{ asset('theme-assets/a-avatar.png') }}" alt="用户头像">
    </a>
</header>
