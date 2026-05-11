<article class="item-card">
    <img src="{{ $item->cover_url }}" alt="">
    <div class="item-body">
        <span class="badge">{{ $item->type === 'files' ? '资源发布' : ($item->type === 'images' ? '插画绘画' : '攻略教程') }}</span>
        <h3><a href="/content/{{ $item->slug }}">{{ $item->title }}</a></h3>
        <p>{{ $item->excerpt }}</p>
        <div class="meta">
            <span>{{ $item->author->name ?? 'zfy小站长' }}</span>
            <span>{{ number_format($item->view_count) }} 浏览</span>
            <span>{{ $item->comment_count }} 评论</span>
            @if(($item->pricing['price'] ?? 0) > 0)
                <strong>¥{{ $item->pricing['price'] }}</strong>
            @endif
        </div>
    </div>
</article>
