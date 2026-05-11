@php
    $mode = $mode ?? 'list';
    $cover = $item->cover_url ?? 'https://images.unsplash.com/photo-1511512578047-dfb367046420?auto=format&fit=crop&w=900&q=80';
    $typeLabel = match ($item->type ?? 'post') {
        'files' => '资源发布',
        'images' => '图集精选',
        'page' => '专题页面',
        default => '攻略教程',
    };
    $price = data_get($item, 'pricing.price', 0);
@endphp
<article class="a-item-card a-item-{{ $mode }}">
    <a href="/content/{{ $item->slug ?? '#' }}" class="a-item-cover">
        <img src="{{ $cover }}" alt="{{ $item->title ?? '资源封面' }}">
    </a>
    <div class="a-item-body">
        <span class="a-badge">{{ $typeLabel }}</span>
        <h3><a href="/content/{{ $item->slug ?? '#' }}">{{ $item->title ?? '精选资源标题' }}</a></h3>
        <p>{{ $item->excerpt ?? '精选游戏资源、攻略教程与社区内容。' }}</p>
        <div class="a-meta">
            <span>{{ $item->author->name ?? 'zfy小助手' }}</span>
            <span>{{ number_format($item->view_count ?? 2860) }} 浏览</span>
            <span>{{ $item->comment_count ?? 86 }} 评论</span>
            @if($price > 0)
                <strong>¥{{ $price }}</strong>
            @else
                <strong class="free">免费</strong>
            @endif
        </div>
    </div>
    @if($mode === 'resource')
        <a class="a-small-btn" href="/content/{{ $item->slug ?? '#' }}">下载</a>
    @endif
</article>
