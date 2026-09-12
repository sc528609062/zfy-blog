<header class="a-topbar">
    <a class="a-brand" href="/">
        <span class="a-brand-mark">{{ ($themeTone ?? 'blue') === 'creative' ? '★' : (($themeTone ?? 'blue') === 'blue' ? '🎮' : 'Z') }}</span>
        <span>
            <strong>{{ data_get($theme, 'settings.global.logo_text', $siteName ?? 'zfy-blog') }}</strong>
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
        @if(isset($navigation) && $navigation->has('primary'))
            @include('themes.shared.partials.navigation-items', ['items' => $navigation->get('primary')->items])
        @else
        @foreach($navItems as $href => $label)
            <a href="{{ $href }}" class="{{ request()->path() === trim($href, '/') || ($href === '/' && request()->is('/')) ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
        @endif
    </nav>

    <form class="a-search" action="/search">
        <input name="q" value="{{ request('q') }}" placeholder="{{ ($themeTone ?? 'blue') === 'market' ? '搜索资源/教程/文章' : (($themeTone ?? 'blue') === 'creative' ? '搜索资源、教程、用户、帖子...' : '搜索游戏、资源、攻略...') }}">
        <button aria-label="搜索">⌕</button>
    </form>

    <a class="a-publish" href="{{ auth()->user()?->canSubmitContent() ? '/user/editor' : '/user/author' }}">{{ ($themeTone ?? 'blue') === 'market' ? '发布资源' : '发布' }}</a>
    <a class="a-vip-chip" href="/vip">{{ ($themeTone ?? 'blue') === 'creative' ? 'VIP' : '开通VIP' }}</a>
    <a class="a-bell" href="{{ auth()->check() ? '/user' : '/login' }}">{{ auth()->check() ? '账户' : '登录' }}</a>
    <a class="a-avatar" href="/user">
        <img src="{{ asset('theme-assets/a-avatar.png') }}" alt="用户头像">
    </a>
</header>
<details class="zfy-mobile-menu"><summary>导航</summary><nav>@if($menu = $navigation?->get('mobile') ?? $navigation?->get('primary'))@include('themes.shared.partials.navigation-items', ['items' => $menu->items])@else @foreach($navItems as $href => $label)<a href="{{ $href }}">{{ $label }}</a>@endforeach @endif<a href="/user">用户中心</a></nav></details>
<style>.zfy-mobile-menu{display:none}@media(max-width:900px){.zfy-mobile-menu{display:block;padding:12px 20px;background:#fff;color:#222}.zfy-mobile-menu nav{display:flex;flex-wrap:wrap;gap:16px;padding-top:12px}}</style>
@if(session('status'))<div class="a-shell" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="a-shell" role="alert">{{ $errors->first() }}</div>@endif
