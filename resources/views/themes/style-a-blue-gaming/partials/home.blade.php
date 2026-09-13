@php
    $tone = $themeTone ?? 'blue';
    $hero = $featured;
    $options = data_get($theme, 'settings.global', []);
    foreach (['hero_image', 'hero_url', 'announcement_url'] as $urlKey) {
        if (! app(\App\Services\ThemeConfiguration::class)->safeUrl($options[$urlKey] ?? '')) $options[$urlKey] = '';
    }
    $heroImage = ($options['hero_image'] ?? '') ?: ($hero?->cover_url ?: asset('theme-assets/'.match($tone) { 'market' => 'b-home-art.png', 'creative' => 'c-home-art.png', default => 'a-home-art.png' }));
    $heroTitle = ($options['hero_title'] ?? '') ?: ($hero?->title ?? $siteName);
    $heroSubtitle = ($options['hero_subtitle'] ?? '') ?: $hero?->excerpt;
    $heroUrl = ($options['hero_url'] ?? '') ?: ($hero ? route('contents.show', $hero->slug) : '/posts');
    $feed = $contents->merge($resources)->merge($images)->unique('id')->take(max(4, min(12, (int) ($options['home_count'] ?? 12))));
    $feedLayout = ($options['feed_layout'] ?? ($tone === 'blue' ? 'list' : 'grid')) === 'grid' ? 'grid' : 'list';
@endphp
@if(($options['announcement_enabled'] ?? false) && filled($options['announcement_text'] ?? ''))
<aside class="site-announcement" aria-label="站点公告"><strong>公告</strong><span>{{ $options['announcement_text'] }}</span>@if(filled($options['announcement_url'] ?? ''))<a href="{{ $options['announcement_url'] }}">查看详情</a>@endif</aside>
@endif
@if(data_get($theme, 'settings.home.hero_enabled', true))
<section class="a-home-grid">
    <div class="a-hero">
        <img class="site-hero-image" src="{{ $heroImage }}" alt="" fetchpriority="high">
        <div><h1>{{ $heroTitle }}</h1><p>{{ $heroSubtitle }}</p><a class="a-primary" href="{{ $heroUrl }}">{{ $options['hero_button'] ?? '查看内容' }}</a></div>
    </div>
</section>
@endif
@if(data_get($theme, 'settings.home.channels_enabled', true))
<section class="a-section">
    <div class="a-section-title"><h2>内容分类</h2><a href="/posts">全部内容</a></div>
    <div class="a-channel-grid">@foreach($categories->take(8) as $category)<a href="/c/{{ $category->slug }}"><span>{{ mb_substr($category->name, 0, 1) }}</span><strong>{{ $category->name }}</strong><small>{{ $category->contents_count }} 内容</small></a>@endforeach</div>
</section>
@endif
<section class="a-two-col {{ data_get($theme, 'settings.home.sidebar_enabled', true) ? '' : 'without-sidebar' }}">
    <div class="a-card-panel">
        <div class="a-tabs-head"><h2>{{ $options['feed_title'] ?? '最新发布' }}</h2><nav><a href="/posts">文章</a><a href="/files">资源</a><a href="/images">图集</a></nav></div>
        <div class="{{ $feedLayout === 'grid' ? 'a-card-grid' : 'a-feed-list' }}">@forelse($feed as $item)@include('themes.style-a-blue-gaming.partials.cards', ['item' => $item, 'mode' => $feedLayout])@empty<p class="site-empty">暂无已发布内容。</p>@endforelse</div>
        <a class="a-load-more" href="/posts">更多内容</a>
    </div>
    @if(data_get($theme, 'settings.home.sidebar_enabled', true))<aside class="a-side-stack">
        @if($options['home_profile'] ?? true)<section class="a-profile-card">
            <img src="{{ auth()->user()?->avatar_url ?: asset('theme-assets/a-avatar.png') }}" alt="" width="48" height="48">
            <div><h3>{{ auth()->user()?->name ?? $siteName }}</h3><a href="{{ auth()->check() ? '/user' : '/login' }}">{{ auth()->check() ? '个人中心' : '登录' }}</a></div>
            <div class="a-profile-stats"><strong>{{ $stats['contents'] }}<span>内容</span></strong><strong>{{ $stats['users'] }}<span>用户</span></strong><strong>{{ $stats['authors'] }}<span>作者</span></strong><strong>{{ $stats['downloads'] }}<span>下载</span></strong></div>
        </section>@endif
        @if($options['home_vip'] ?? true)<section class="a-vip-mini"><div><h3>VIP</h3><p>{{ $vipLevels->first()?->name ?? '会员中心' }}</p></div><a href="/vip">查看套餐</a></section>@endif
        @include('themes.style-a-blue-gaming.partials.ranking', ['title' => '热门内容'])
        <section class="a-card-panel"><div class="a-section-title"><h3>创作者</h3><a href="/authors">更多</a></div>@foreach($authors->take(5) as $author)<a class="a-topic-row" href="/author/{{ $author->username ?: $author->id }}"><span>{{ $author->name }}</span><b>{{ $author->contents_count }} 作品</b></a>@endforeach</section>
    </aside>@endif
</section>
