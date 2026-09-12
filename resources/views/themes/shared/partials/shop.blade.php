@if($cartMode ?? false)
    @include('themes.shared.partials.cart')
@else
<section class="a-card-panel">
    <h1>商品</h1>
    <a href="/cart">购物车</a>
    @forelse($products as $product)
        <article style="border-bottom:1px solid #e5e7eb;padding:20px 0">
            <h2>{{ $product->title }}</h2><p>¥{{ $product->sale_price ?? $product->price }}</p>
            @auth<form method="post" action="{{ route('products.buy', $product) }}">@csrf
                @if($product->variants->where('status', 'active')->isNotEmpty())<label>规格 <select name="variant_id" required>@foreach($product->variants->where('status', 'active') as $variant)<option value="{{ $variant->id }}">{{ $variant->title }} - {{ $variant->price ?? $product->price }}</option>@endforeach</select></label>@endif
                <label>数量 <input type="number" name="quantity" value="1" min="1" max="100" required></label>
                <label>优惠码 <input name="coupon" maxlength="80"></label>
                @if($product->type === 'physical')<p><label>收货人、电话与地址 <textarea name="address" required maxlength="1000"></textarea></label></p>@endif
                <select name="gateway" aria-label="支付方式"><option value="balance">余额</option><option value="points">积分</option></select>
                <button class="a-primary">购买</button>
                <button formaction="/cart/products/{{ $product->id }}" formnovalidate>加入购物车</button>
            </form>@else<a href="/login">登录购买</a>@endauth
        </article>
    @empty<p>暂无商品</p>@endforelse
    {{ $products->links() }}
</section>
@endif
