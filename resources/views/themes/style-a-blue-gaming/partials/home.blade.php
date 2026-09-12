@php
    $tone = $themeTone ?? 'blue';
    $hero = $featured;
    $heroImage = $hero?->cover_url ?: asset('theme-assets/'.match($tone) { 'market' => 'b-home-hero.png', 'creative' => 'c-home-hero.png', default => 'a-home-hero.png' });
    $feed = $contents->merge($resources)->merge($images)->unique('id')->take(12);
@endphp
@if(data_get($theme, 'settings.home.hero_enabled', true))
<section class="a-home-grid">
    <div class="a-hero" style="--hero-image:url('{{ $heroImage }}')">
        <div><h1>{{ $hero?->title ?? $siteName }}</h1><p>{{ $hero?->excerpt }}</p><a class="a-primary" href="{{ $hero ? route('contents.show', $hero->slug) : '/posts' }}">查看内容</a></div>
    </div>
    <aside class="a-profile-card">
        <img src="{{ auth()->user()?->avatar_url ?: asset('theme-assets/a-avatar.png') }}" alt="">
        <div><h3>{{ auth()->user()?->name ?? $siteName }}</h3><a href="{{ auth()->check() ? '/user' : '/login' }}">{{ auth()->check() ? '个人中心' : '登录' }}</a></div>
        <div class="a-profile-stats"><strong>{{ $stats['contents'] }}<span>内容</span></strong><strong>{{ $stats['users'] }}<span>用户</span></strong><strong>{{ $stats['authors'] }}<span>作者</span></strong><strong>{{ $stats['downloads'] }}<span>下载</span></strong></div>
    </aside>
    <aside class="a-vip-mini"><div><h3>VIP</h3><p>{{ $vipLevels->first()?->name ?? '会员中心' }}</p></div><a href="/vip">查看套餐</a></aside>
</section>
@endif
@if(data_get($theme, 'settings.home.channels_enabled', true))
<section class="a-section">
    <div class="a-section-title"><h2>内容分类</h2><a href="/posts">全部内容</a></div>
    <div class="a-channel-grid">@foreach($categories->take(8) as $category)<a href="/c/{{ $category->slug }}"><span>{{ mb_substr($category->name, 0, 1) }}</span><strong>{{ $category->name }}</strong><small>{{ $category->contents_count }} 内容</small></a>@endforeach</div>
</section>
@endif
<section class="a-two-col" @if(!data_get($theme, 'settings.home.sidebar_enabled', true)) style="grid-template-columns:minmax(0,1fr)" @endif>
    <div class="a-card-panel">
        <div class="a-tabs-head"><h2>最新发布</h2><nav><a href="/posts">文章</a><a href="/files">资源</a><a href="/images">图集</a></nav></div>
        <div class="{{ $tone === 'creative' ? 'a-card-grid' : 'a-feed-list' }}">@forelse($feed as $item)@include('themes.style-a-blue-gaming.partials.cards', ['item' => $item, 'mode' => $tone === 'creative' ? 'grid' : 'list'])@empty<p>暂无已发布内容。</p>@endforelse</div>
        <a class="a-load-more" href="/posts">更多内容</a>
    </div>
    @if(data_get($theme, 'settings.home.sidebar_enabled', true))<aside class="a-side-stack">
        @include('themes.style-a-blue-gaming.partials.ranking', ['title' => '热门内容'])
        <section class="a-card-panel"><div class="a-section-title"><h3>创作者</h3><a href="/authors">更多</a></div>@foreach($authors->take(5) as $author)<a class="a-topic-row" href="/author/{{ $author->username ?: $author->id }}"><span>{{ $author->name }}</span><b>{{ $author->contents_count }} 作品</b></a>@endforeach</section>
    </aside>@endif
</section>
