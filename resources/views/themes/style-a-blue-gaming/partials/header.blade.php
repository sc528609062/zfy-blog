@php
    $options = data_get($theme, 'settings.global', []);
    $logo = $options['logo_url'] ?? '';
@endphp
<header class="a-topbar {{ ($options['sticky_header'] ?? true) ? 'is-sticky' : '' }}">
    <a class="a-brand" href="/">
        @if(filled($logo) && app(\App\Services\ThemeConfiguration::class)->safeUrl($logo))
            <img class="site-logo" src="{{ $logo }}" alt="{{ $options['logo_text'] ?? $siteName }}">
        @else
            <span class="a-brand-mark">Z</span>
        @endif
        <span>
            <strong>{{ data_get($theme, 'settings.global.logo_text', $siteName ?? 'zfy-blog') }}</strong>
            @if(filled($options['tagline'] ?? ''))<small>{{ $options['tagline'] }}</small>@endif
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

    @if($options['header_search'] ?? true)<form class="a-search" action="/search">
        <input name="q" aria-label="搜索内容" value="{{ request('q') }}" placeholder="{{ $options['search_placeholder'] ?? '搜索文章、资源、教程' }}">
        <button aria-label="搜索">⌕</button>
    </form>@endif

    @if($options['header_publish'] ?? true)<a class="a-publish" href="{{ auth()->user()?->canSubmitContent() ? '/user/editor' : '/user/author' }}">投稿</a>@endif
    @if($options['header_vip'] ?? true)<a class="a-vip-chip" href="/vip">VIP</a>@endif
    <a class="a-bell" href="{{ auth()->check() ? '/user' : '/login' }}">{{ auth()->check() ? '账户' : '登录' }}</a>
    <a class="a-avatar" href="/user">
        <img src="{{ auth()->user()?->avatar_url ?: asset('theme-assets/a-avatar.png') }}" alt="用户头像" width="36" height="36">
    </a>
</header>
<details class="zfy-mobile-menu"><summary>导航</summary><nav>@if($menu = $navigation?->get('mobile') ?? $navigation?->get('primary'))@include('themes.shared.partials.navigation-items', ['items' => $menu->items])@else @foreach($navItems as $href => $label)<a href="{{ $href }}">{{ $label }}</a>@endforeach @endif<a href="/user">用户中心</a></nav></details>
@if(session('status'))<div class="a-shell" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="a-shell" role="alert">{{ $errors->first() }}</div>@endif
