@php
    $mode = $mode ?? 'list';
    $cardOptions = data_get($theme, 'settings.global', []);
    $cover = $item->cover_url ?: (($cardOptions['default_cover'] ?? '') ?: asset('theme-assets/'.match($themeTone ?? 'blue') { 'market' => 'b-product-1.png', 'creative' => 'c-card-1.png', default => 'a-home-art.png' }));
    $typeLabel = match ($item->type ?? 'post') {
        'files' => '资源发布',
        'images' => '图集精选',
        'page' => '专题页面',
        default => '攻略教程',
    };
    $price = data_get($item, 'pricing.price', 0);
@endphp
<article class="a-item-card a-item-{{ $mode }}">
    <a href="{{ route('contents.show', $item->slug) }}" class="a-item-cover">
        <img src="{{ $cover }}" alt="{{ $item->title ?? '资源封面' }}" loading="lazy" decoding="async" width="480" height="300">
    </a>
    <div class="a-item-body">
        <span class="a-badge">{{ $typeLabel }}</span>
        <h3><a href="{{ route('contents.show', $item->slug) }}">{{ $item->title }}</a></h3>
        @if($cardOptions['card_excerpt'] ?? true)<p>{{ $item->excerpt }}</p>@endif
        <div class="a-meta">
            @if($cardOptions['card_author'] ?? true)<span class="site-card-author">{{ $item->author->name ?? '作者' }}</span>@endif
            @if($cardOptions['card_stats'] ?? true)<span>{{ number_format($item->view_count ?? 0) }} 浏览</span><span>{{ $item->comment_count ?? 0 }} 评论</span>@endif
            @if($price > 0)
                <strong>¥{{ $price }}</strong>
            @else
                <strong class="free">免费</strong>
            @endif
        </div>
    </div>
    @if($mode === 'resource')
        <a class="a-small-btn" href="{{ route('contents.show', $item->slug) }}">查看</a>
    @endif
</article>
