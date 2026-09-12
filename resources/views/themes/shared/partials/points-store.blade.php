<section class="a-card-panel">
    <h1>积分商城</h1>
    @auth<p>可用积分：{{ auth()->user()->pointsAccount?->points ?? 0 }}</p>@endauth
    @forelse($storeItems as $item)
        <article style="border-bottom:1px solid #e5e7eb;padding:20px 0">
            <h2>{{ $item->title }}</h2><p>{{ $item->points_price }} 积分 · {{ $item->stock < 0 ? '不限量' : '剩余 '.$item->stock.' 件' }}</p>
            @auth<form method="post" action="{{ route('points.exchange', $item) }}">@csrf<input type="hidden" name="request_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><button class="a-primary" @disabled($item->stock === 0)>兑换</button></form>
            @else<a href="/login">登录兑换</a>@endauth
        </article>
    @empty<p>暂无兑换商品</p>@endforelse
    {{ $storeItems->links() }}
</section>
