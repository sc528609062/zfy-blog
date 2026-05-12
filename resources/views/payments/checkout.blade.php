<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>订单支付 - zfy-blog</title>
    @vite(['resources/css/app.css'])
</head>
<body class="auth-page">
    <section class="auth-card checkout-card">
        <h1>订单支付</h1>
        <p>订单号：{{ $order->order_no }}</p>
        <div class="panel">
            @foreach($order->items as $item)
                <div class="filter-row">{{ $item->title }} <span>¥{{ $item->unit_price }}</span></div>
            @endforeach
        </div>
        <h2>¥{{ $order->total_amount }}</h2>
        <p>当前支付网关：{{ $payment->gateway ?? $order->pay_channel }}。这是沙箱占位支付单，后台可配置真实商户密钥。</p>
        <p>订单状态：{{ $order->status }} · <a href="/orders/{{ $order->order_no }}/status">查看 JSON 状态</a></p>
        @if($payment)
            <form method="post" action="{{ route('payments.query', $payment) }}">
                @csrf
                <button class="primary-btn">主动查单</button>
            </form>
        @endif
        <a class="primary-btn" href="/user/orders">查看订单</a>
    </section>
</body>
</html>
