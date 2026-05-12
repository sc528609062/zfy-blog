@php($detail = $content ?? $featured)
<section class="detail-layout">
    <article class="detail-main">
        <img class="detail-cover" src="{{ $detail->cover_url }}" alt="">
        <div class="detail-head">
            <span class="badge">{{ strtoupper($detail->type) }}</span>
            <h1>{{ $detail->title }}</h1>
            <p>{{ $detail->excerpt }}</p>
            <div class="meta"><span>{{ $detail->author->name ?? 'zfy作者' }}</span><span>{{ number_format($detail->view_count) }} 浏览</span><span>{{ $detail->comment_count }} 评论</span></div>
        </div>
        <div class="article-content">{!! $detail->rendered_html !!}</div>
        @if(($detail->pricing['price'] ?? 0) > 0)
            <form method="post" action="/buy/{{ $detail->slug }}" class="paywall">
                @csrf
                <h3>隐藏内容 / 资源下载</h3>
                <p>普通价 ¥{{ $detail->pricing['price'] }}，VIP 价 ¥{{ $detail->pricing['vip_price'] ?? 0 }}，也可使用 {{ $detail->pricing['points'] ?? 0 }} 积分兑换。</p>
                <select name="gateway">
                    <option value="alipay_official">支付宝官方</option>
                    <option value="wechat_official">微信官方</option>
                    <option value="hupijiao_v3">虎皮椒 V3</option>
                    <option value="epay">易支付</option>
                </select>
                <button class="primary-btn">立即购买</button>
            </form>
        @endif
    </article>
    <aside class="sidebar">
        <div class="panel author-box">
            <img src="{{ $detail->author->avatar_url ?? '/assets/zfy/placeholders/avatar.svg' }}" alt="">
            <h3>{{ $detail->author->name ?? 'zfy作者' }}</h3>
            <p>签约创作者，收益分成和提现后台可管理。</p>
        </div>
        <div class="panel">
            <h3>相关推荐</h3>
            @foreach($rankings->take(5) as $rank)
                <a class="filter-row" href="/content/{{ $rank->slug }}">{{ $rank->title }} <span>{{ $rank->like_count }}</span></a>
            @endforeach
        </div>
    </aside>
</section>
