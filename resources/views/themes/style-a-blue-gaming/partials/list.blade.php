@php
    $tone = $themeTone ?? 'blue';
    $title = match ($page) {
        'posts-channel' => '文章', 'images-channel' => '图集', 'files-channel' => '资源下载',
        'category-page' => $currentCategory->name ?? '分类', 'tag-page' => '#'.($currentTag->name ?? '标签'),
        'topic-page' => $currentTopic->name ?? '专题', 'rank' => '排行榜', default => $siteName,
    };
    $sorts = ['latest' => '最新', 'popular' => '热门', 'downloads' => '下载量'];
@endphp
<section class="a-page-hero compact"><h1>{{ $title }}</h1><p>{{ $pagination->total() }} 条内容</p></section>
<section class="a-two-col">
    <div class="a-card-panel">
        <div class="a-tabs-head"><h2>{{ $title }}</h2><nav>@foreach($sorts as $sort => $label)<a class="{{ request('sort', $page === 'rank' ? 'popular' : 'latest') === $sort ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['sort' => $sort, 'page' => 1]) }}">{{ $label }}</a>@endforeach<a class="{{ request('free') ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['free' => request('free') ? null : 1, 'page' => 1]) }}">免费</a></nav></div>
        <div class="{{ $page === 'files-channel' ? 'a-resource-list' : 'a-card-grid' }}">
            @forelse($contents as $item)@include('themes.style-a-blue-gaming.partials.cards', ['item' => $item, 'mode' => $page === 'files-channel' ? 'resource' : 'grid'])@empty<p>没有符合条件的内容。</p>@endforelse
        </div>
    </div>
    <aside class="a-side-stack">
        <section class="a-card-panel"><h3>分类</h3>@foreach($categories as $category)<a class="a-topic-row" href="/c/{{ $category->slug }}"><span>{{ $category->name }}</span><b>{{ $category->contents_count }}</b></a>@endforeach</section>
        @include('themes.style-a-blue-gaming.partials.ranking', ['title' => '热门推荐'])
        @if($page === 'files-channel')<section class="a-card-panel"><h3>资源与订单</h3><a href="/shop">商品商城</a> · <a href="/cart">购物车</a> · <a href="/user/downloads">下载记录</a></section>@endif
    </aside>
</section>
