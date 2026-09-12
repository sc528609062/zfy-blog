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
        <p>支付方式：{{ ['balance' => '余额', 'points' => '积分'][$order->pay_channel] ?? $order->pay_channel }}</p>
        <p>订单状态：{{ $order->status === 'paid' ? '已支付' : '待支付' }}</p>
        @if($order->status === 'paid' && data_get($order->meta, 'content_slug'))<a href="{{ route('contents.show', data_get($order->meta, 'content_slug')) }}">查看内容</a>@endif
        @if($payment)
            @if(data_get($payment->response_payload, 'qr_code'))<img src="{{ route('payments.qr', $payment) }}" alt="付款二维码" width="280" height="280" style="max-width:100%;height:auto">@endif
            @if(data_get($payment->response_payload, 'checkout_url'))<a class="primary-btn" href="{{ data_get($payment->response_payload, 'checkout_url') }}">前往支付</a>@endif
            <form method="post" action="{{ route('payments.query', $payment) }}">
                @csrf
                <button class="primary-btn">主动查单</button>
            </form>
        @endif
        <a class="primary-btn" href="/user/orders">查看订单</a>
    </section>
</body>
</html>
