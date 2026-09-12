<section class="zfy-cart">
    <h1>购物车</h1>
    @if(session('status'))<p role="status">{{ session('status') }}</p>@endif
    @if($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
    @forelse($cartItems as $item)
        <article>
            <div><h2>{{ $item['title'] }}</h2><p>{{ $item['variant'] }} <strong>¥{{ $item['subtotal'] }}</strong></p>@unless($item['available'])<span>商品不可售</span>@endunless</div>
            <form method="post" action="/cart/items/{{ $item['id'] }}">@csrf @method('PATCH')<label>数量 <input name="quantity" type="number" min="1" max="100" value="{{ $item['quantity'] }}" required></label><button>更新</button></form>
            <form method="post" action="/cart/items/{{ $item['id'] }}">@csrf @method('DELETE')<button aria-label="移除 {{ $item['title'] }}">移除</button></form>
        </article>
    @empty<p>购物车为空</p><a href="/shop">浏览商品</a>@endforelse
    @if(count($cartItems))
        <form method="post" action="/cart/checkout" class="zfy-cart-checkout">@csrf
            <input type="hidden" name="request_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
            <label>收货地址 <select name="address_id"><option value="">无需配送</option>@foreach($addresses as $address)<option value="{{ $address->id }}" @selected($address->is_default)>{{ $address->recipient }} {{ $address->region }} {{ $address->address }}</option>@endforeach</select></label>
            <label>优惠码 <input name="coupon" maxlength="80"></label>
            <label>支付方式 <select name="gateway">@include('themes.shared.partials.payment-options')</select></label>
            <output class="zfy-cart-quote" aria-live="polite"></output>
            <button type="button" class="zfy-cart-quote-refresh">更新报价</button>
            <button class="a-primary">提交订单</button>
        </form>
    @endif
    <h2>收货地址</h2>
    @foreach($addresses as $address)
        <details><summary>{{ $address->recipient }} {{ $address->region }} {{ $address->address }}</summary>
            <form method="post" action="/user/addresses/{{ $address->id }}" class="zfy-address-form">@csrf @method('PATCH')
                @include('themes.shared.partials.address-fields', ['address' => $address])<button>保存地址</button>
            </form>
            <form method="post" action="/user/addresses/{{ $address->id }}">@csrf @method('DELETE')<button>删除地址</button></form>
        </details>
    @endforeach
    <details><summary>添加地址</summary><form method="post" action="/user/addresses" class="zfy-address-form">@csrf @include('themes.shared.partials.address-fields', ['address' => null])<button>添加地址</button></form></details>
</section>
<style>.zfy-cart{width:100%;padding:20px;overflow-wrap:anywhere}.zfy-cart article{display:flex;gap:20px;align-items:center;flex-wrap:wrap;border-bottom:1px solid #d6d9df;padding:16px 0}.zfy-cart h2{font-size:18px}.zfy-cart input,.zfy-cart select{max-width:100%;padding:8px;border:1px solid #c7ccd3;background:transparent;color:inherit}.zfy-cart input[type=number]{width:80px}.zfy-cart button{padding:8px 14px;cursor:pointer}.zfy-cart-checkout,.zfy-address-form{display:grid;gap:12px;padding:20px 0}.zfy-cart label{display:grid;gap:6px}.zfy-cart details{padding:12px 0;border-bottom:1px solid #d6d9df}.zfy-cart summary{cursor:pointer}</style>
