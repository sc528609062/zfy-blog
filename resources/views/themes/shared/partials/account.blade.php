@php
    $user = $accountUser;
    $vip = $user->vip;
    $activeVip = $vip?->isActive();
    $statuses = ['pending' => '待支付', 'paid' => '已支付', 'cancelled' => '已取消', 'expired' => '已过期', 'refunded' => '已退款'];
@endphp
<section class="a-account-layout a-account-workspace">
    @include('themes.style-a-blue-gaming.partials.account-menu')
    <div class="a-account-main">
        @if($page === 'user-overview')
        <section class="a-user-hero">
            <img src="{{ $user->avatar_url ?: asset('theme-assets/a-avatar.png') }}" alt="{{ $user->name }}">
            <div><h1>{{ $user->name }}</h1><p>UID: {{ $user->id }}</p></div>
            <a href="/user/settings">编辑资料</a>
            <form method="post" action="/logout">@csrf<button type="submit">退出登录</button></form>
        </section>
        <section class="a-stat-cards">
            <div><span>钱包余额</span><strong>¥{{ number_format($user->wallet?->balance ?? 0, 2) }}</strong><a href="/user/wallet">余额明细</a></div>
            <div><span>积分</span><strong>{{ $user->pointsAccount?->points ?? 0 }}</strong><a href="/user/points">积分明细</a></div>
            <div><span>我的会员</span><strong>{{ $activeVip ? $vip->level?->name : '普通用户' }}</strong><a href="/user/vip">会员信息</a></div>
        </section>
        @endif
        <nav><a href="/user/notifications">通知</a> · <a href="/user/messages">私信</a> · <a href="/user/favorites">我的收藏</a> · <a href="/shop">商品</a></nav>
        @if($user->is_banned)<p role="alert">账号已停用：{{ $user->ban_reason }}</p>@endif
        @if($page === 'user-overview' && !$user->is_banned && app(\App\Services\SiteSettings::class)->get('community.checkin_enabled', true))
            <form method="post" action="{{ route('user.checkin') }}">@csrf<button @disabled($checkedInToday)>{{ $checkedInToday ? '今日已签到' : '每日签到' }}</button></form>
        @endif
        @if($page === 'user-editor')
            <form class="a-card-panel" method="post" action="{{ $editingContent ? route('user.contents.update', $editingContent) : route('user.contents.store') }}">
                @csrf @if($editingContent) @method('PATCH') @endif
                <h2>{{ $editingContent ? '编辑内容' : '新建内容' }}</h2>
                <p><label>标题 <input name="title" value="{{ old('title', $editingContent?->title) }}" required maxlength="180"></label></p>
                <p><label>类型 <select name="type">@foreach(array_diff(config('zfy.content_types'), ['page']) as $key)<option value="{{ $key }}" @selected(old('type', $editingContent?->type ?? 'post') === $key)>{{ ['post' => '文章', 'images' => '图集', 'files' => '资源'][$key] ?? (app(\App\Support\Zfy\ExtensionRegistry::class)->get('content_type', $key)['label'] ?? $key) }}</option>@endforeach</select></label>
                <label>分类 <select name="category_id"><option value="">未分类</option>@foreach($editorCategories as $category)<option value="{{ $category->id }}" @selected((string) old('category_id', $editingContent?->category_id) === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></label></p>
                <p><label>摘要 <textarea name="excerpt" rows="3" maxlength="1000">{{ old('excerpt', $editingContent?->excerpt) }}</textarea></label></p>
                <p><label>正文 <textarea name="markdown_cache" rows="22" required maxlength="200000" style="width:100%;box-sizing:border-box">{{ old('markdown_cache', $editingContent?->markdown_cache) }}</textarea></label></p>
                <p><label>标签 <input name="tags" value="{{ old('tags', $editingContent?->tags->pluck('name')->implode(',')) }}" maxlength="500"></label></p>
                <button name="status" value="draft">保存草稿</button> <button class="a-primary" name="status" value="pending">提交审核</button> <a href="{{ route('user.author') }}">返回我的内容</a>
            </form>
        @elseif($page === 'user-settings')
            <section><h2>账户安全</h2><p>邮箱：{{ $user->hasVerifiedEmail() ? '已验证' : '未验证' }}</p>
                @unless($user->hasVerifiedEmail())<form method="post" action="{{ route('verification.send') }}">@csrf<button>发送验证邮件</button></form>@endunless
                <h3>登录设备</h3>@foreach($accountSessions as $device)<article><p>{{ $device->ip_address }} · {{ \Illuminate\Support\Str::limit($device->user_agent, 100) }}</p>@if($device->id !== session()->getId())<form method="post" action="/user/sessions/{{ $device->id }}">@csrf @method('DELETE')<button>退出此设备</button></form>@else<span>当前设备</span>@endif</article>@endforeach
            </section>
            <form class="a-card-panel" method="post" action="{{ route('user.profile') }}">
                @csrf @method('PUT')
                <h2>个人资料</h2>
                <p><label>昵称 <input name="name" value="{{ old('name', $user->name) }}" required maxlength="120"></label></p>
                <p><label>邮箱 <input name="email" type="email" value="{{ old('email', $user->email) }}" required></label></p>
                <p><label>个人介绍 <textarea name="bio" rows="4">{{ old('bio', $user->bio) }}</textarea></label></p>
                <p><label>当前密码 <input name="current_password" type="password" autocomplete="current-password"></label></p>
                <p><label>新密码 <input name="password" type="password" minlength="12" autocomplete="new-password"></label></p>
                <p><label>确认新密码 <input name="password_confirmation" type="password" autocomplete="new-password"></label></p>
                <button class="a-primary">保存资料</button>
            </form>
        @elseif($page === 'user-messages')
            <section><h2>私信</h2>
                @if(!$user->is_banned && app(\App\Services\SiteSettings::class)->get('community.messages_enabled', true))
                <form method="post" action="{{ route('messages.send') }}">@csrf<input type="hidden" name="request_id" value="{{ \Illuminate\Support\Str::uuid() }}"><p><label>收件人 UID <input type="number" name="recipient_id" min="1" required value="{{ request('recipient_id') }}"></label></p><p><label>消息 <textarea name="body" rows="4" maxlength="4000" required></textarea></label></p><button class="a-primary">发送私信</button></form>
                @endif
                @forelse($privateMessages as $message)<article><h3>{{ $message->sender?->name }} → {{ $message->recipient?->name }}</h3><p style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $message->body }}</p><small>{{ $message->created_at }} · {{ ['pending'=>'待审核','sent'=>'已发送','rejected'=>'未通过'][$message->status] }}{{ $message->read_at ? ' · 已读' : '' }}</small>@if($message->recipient_id === $user->id && !$message->read_at)<form method="post" action="{{ route('messages.read', $message) }}">@csrf<button>标为已读</button></form>@endif<a href="{{ route('user.messages', ['recipient_id' => $message->sender_id === $user->id ? $message->recipient_id : $message->sender_id]) }}">回复</a></article>@empty<p>暂无私信</p>@endforelse
                {{ $privateMessages->links() }}
            </section>
        @elseif($page === 'user-notifications')
            <section class="a-card-panel"><h2>通知</h2>@forelse($accountNotifications as $notice)<article><h3>{{ $notice->title }}</h3><p>{{ $notice->body }}</p>@if(!in_array($notice->id, $readNotifications))<form method="post" action="{{ route('notifications.read', $notice->id) }}">@csrf<button>标为已读</button></form>@endif</article>@empty<p>暂无通知</p>@endforelse</section>
        @elseif($page === 'user-favorites')
            <section class="a-card-panel"><h2>我的收藏</h2>@forelse($favorites as $item)<p><a href="{{ route('contents.show', $item->slug) }}">{{ $item->title }}</a></p>@empty<p>暂无收藏</p>@endforelse</section>
        @elseif($page === 'user-wallet' || $page === 'user-points')
            @if($page === 'user-wallet')
                @if($user->is_author)<section class="a-card-panel"><h2>创作收益</h2>@forelse($authorEarnings as $earning)<p>订单 {{ $earning->order_id }} · ¥{{ $earning->amount }} · {{ ['pending' => '冻结待结算', 'settled' => '已结算', 'reversed' => '退款冲回'][$earning->status] ?? $earning->status }} @if($earning->status === 'pending') · {{ $earning->available_at }}@endif</p>@empty<p>暂无收益</p>@endforelse{{ $authorEarnings->links() }}</section>@endif
                @if(app(\App\Services\Payment\PaymentManager::class)->available())
                    <form method="post" action="{{ route('user.recharge') }}">@csrf<h2>余额充值</h2><label>金额 <input name="amount" type="number" min="1" max="10000" step="0.01" required></label><select name="gateway" aria-label="充值方式">@include('themes.shared.partials.payment-options', ['externalOnly' => true])</select><button class="a-primary">充值</button></form>
                @else
                    <p>在线充值暂未开放</p>
                @endif
                <section class="a-card-panel"><h2>申请提现</h2><p>冻结金额：¥{{ number_format($user->wallet?->frozen_balance ?? 0, 2) }}</p>
                    <form method="post" action="{{ route('withdrawals.store') }}">@csrf
                        <p><label>金额 <input type="number" min="1" step="0.01" name="amount" required></label></p>
                        <p><label>收款方式 <select name="method"><option value="alipay">支付宝</option><option value="wechat">微信</option><option value="bank">银行卡</option></select></label></p>
                        <p><label>收款账户 <input name="account" required maxlength="500"></label></p><button class="a-primary">申请提现</button>
                    </form>
                    @foreach($withdrawals as $withdrawal)<p>¥{{ $withdrawal->amount }} · {{ ['pending' => '待处理', 'paid' => '已打款', 'rejected' => '已驳回'][$withdrawal->status] ?? $withdrawal->status }} · {{ $withdrawal->created_at }}</p>@endforeach
                </section>
            @endif
            @php $transactions = $page === 'user-wallet' ? $walletTransactions : $pointsTransactions; @endphp
            <section class="a-card-panel" style="overflow:auto"><h2>{{ $page === 'user-wallet' ? '余额明细' : '积分明细' }}</h2>
                <table class="a-ui-table"><thead><tr><th>说明</th><th>变动</th><th>结余</th><th>时间</th></tr></thead><tbody>
                    @forelse($transactions as $transaction)<tr><td>{{ $transaction->remark }}</td><td>{{ $transaction->amount ?? $transaction->points }}</td><td>{{ $transaction->balance_after }}</td><td>{{ $transaction->created_at }}</td></tr>
                    @empty<tr><td colspan="4">暂无流水</td></tr>@endforelse
                </tbody></table>{{ $transactions->links() }}
            </section>
        @elseif($page === 'user-vip')
            <section class="a-card-panel"><h2>我的会员</h2>
                <p>{{ $activeVip ? $vip->level?->name : '尚未开通会员' }}</p>
                @if($activeVip)<p>到期时间：{{ $vip->expires_at?->format('Y-m-d H:i') ?? '永久有效' }}</p>@endif
                @foreach($vipLevels as $level)
                    <form method="post" action="{{ route('vip.buy', $level) }}">@csrf
                        <h3>{{ $level->name }}</h3>
                        <p>{{ implode(' · ', $level->benefits ?? []) }}</p>
                        <select name="period" aria-label="会员周期">@include('themes.shared.partials.vip-periods')</select>
                        <select name="gateway" aria-label="支付方式"><option value="balance">余额</option><option value="points">积分</option></select>
                        <button class="a-primary">购买会员</button>
                    </form>
                @endforeach
            </section>
        @elseif($page === 'user-downloads')
            <section class="a-card-panel" style="overflow:auto"><h2>我的下载</h2>
                <table class="a-ui-table"><thead><tr><th>内容</th><th>状态</th><th>时间</th></tr></thead><tbody>
                    @forelse($accountDownloads as $download)<tr><td>{{ $download->title ?: '内容已删除' }}</td><td>{{ $download->status === 'allowed' ? '已授权' : '未授权' }}</td><td>{{ $download->created_at }}</td></tr>
                    @empty<tr><td colspan="3">暂无下载记录</td></tr>@endforelse
                </tbody></table>{{ $accountDownloads->links() }}
            </section>
        @elseif($page === 'author-workspace')
            <section class="a-card-panel"><h2>申请与认证</h2><form method="post" action="{{ route('user.requests') }}">@csrf<select name="type" aria-label="申请类型"><option value="author">作者申请</option><option value="verification">身份认证</option><option value="appeal">申诉</option></select><p><textarea name="body" required maxlength="4000" aria-label="申请说明"></textarea></p><button class="a-primary">提交申请</button></form>@foreach($accountRequests as $application)<p>{{ $application->body }} · {{ $application->status }} · {{ $application->reply }}</p>@endforeach</section>
            <section class="a-card-panel"><h2>我的内容</h2>
                @if($user->canSubmitContent())<a href="{{ route('user.editor') }}">新建内容</a>@endif
                @forelse($authorContents as $item)<p>{{ $item->title }} · {{ ['draft' => '草稿', 'pending' => '待审核', 'published' => '已发布'][$item->status] ?? $item->status }}
                    @if($item->status === 'published')<a href="{{ route('contents.show', $item->slug) }}">查看</a>@endif
                    @if($user->canSubmitContent() && $item->type !== 'page')<a href="{{ route('user.editor', ['content' => $item->id]) }}">编辑</a>@endif
                </p>@empty<p>暂无内容</p>@endforelse
                {{ $authorContents->links() }}
            </section>
        @else
            <section class="a-card-panel" style="overflow:auto"><h2>我的订单</h2>
                <table class="a-ui-table"><thead><tr><th>订单号</th><th>内容</th><th>金额</th><th>状态</th><th>操作</th></tr></thead><tbody>
                    @forelse($accountOrders as $order)<tr>
                        <td>{{ $order->order_no }}</td><td>{{ $order->items->pluck('title')->implode('，') }}
                            @foreach($order->items as $orderItem)@foreach($deliveredCards->get($orderItem->id, collect()) as $card)<p><code>{{ $card->code_payload }}</code></p>@endforeach @endforeach
                        </td><td>¥{{ number_format($order->total_amount, 2) }}@if(data_get($order->meta, 'payment_breakdown.points'))<p>{{ data_get($order->meta, 'payment_breakdown.points') }} 积分 + ¥{{ data_get($order->meta, 'payment_breakdown.balance') }}</p>@endif</td><td>{{ $statuses[$order->status] ?? $order->status }}</td>
                        <td>@if($order->status === 'pending' && !$order->expires_at?->isPast())
                            <form method="post" action="{{ route('orders.pay', $order->order_no) }}">@csrf<select name="gateway" aria-label="支付方式">@if(in_array($order->pay_channel, ['balance', 'points'], true))<option value="balance">余额</option><option value="points">积分</option>@else<option value="{{ $order->pay_channel }}">原订单支付渠道</option>@endif</select>@if(in_array($order->pay_channel, ['balance', 'points'], true))<label>余额支付抵扣积分<input type="number" name="points" min="0" step="1" value="0" max="{{ min($user->pointsAccount?->points ?? 0, (int) bcmul((string) $order->total_amount, '10', 0)) }}"></label>@endif<button>支付</button></form>
                            <form method="post" action="{{ route('orders.cancel', $order->order_no) }}">@csrf<button>取消</button></form>
                        @elseif($order->status === 'paid' && $order->paid_amount > 0)
                            @php $shipment = \App\Models\Shipment::where('order_id', $order->id)->first(); @endphp
                            @if($shipment)<p>{{ $shipment->carrier }} {{ $shipment->tracking_no }}</p>@if($shipment->status === 'shipped')<form method="post" action="{{ route('orders.receive', $order->order_no) }}">@csrf<button>确认收货</button></form>@endif @endif
                            @unless($order->refunds->whereIn('status', ['pending', 'processing'])->isNotEmpty())<details><summary>申请退款</summary><form method="post" action="{{ route('refunds.store', $order->order_no) }}">@csrf<input name="reason" required maxlength="255" aria-label="退款原因">@if($order->type === 'product')<label>退款金额<input name="amount" type="number" min="0.01" step="0.01" max="{{ bcsub((string) $order->paid_amount, (string) $order->refunds->where('status', 'refunded')->sum('amount'), 2) }}" aria-label="退款金额"></label>@endif<button>提交申请</button></form></details>@endunless
                        @endif
                        @foreach($order->refunds as $refund)<p>售后：{{ ['pending' => '待处理', 'processing' => '退款处理中', 'refunded' => '已退款', 'rejected' => '已驳回'][$refund->status] ?? $refund->status }} ¥{{ $refund->amount }}</p>
                            @if(data_get($refund->metadata, 'return.tracking_no'))<p>{{ data_get($refund->metadata, 'return.carrier') }} {{ data_get($refund->metadata, 'return.tracking_no') }} · {{ data_get($refund->metadata, 'return.received_at') ? '已验收' : '待验收' }}</p>@endif
                            @if(in_array($refund->status, ['pending', 'processing', 'refunded']) && $order->items->contains(fn ($item) => data_get($item->meta, 'type') === 'physical') && !data_get($refund->metadata, 'return.received_at'))<details><summary>填写退货物流</summary><form method="post" action="{{ route('refunds.return', $refund) }}">@csrf<label>物流公司 <input name="carrier" required maxlength="100" value="{{ data_get($refund->metadata, 'return.carrier') }}"></label><label>运单号 <input name="tracking_no" required maxlength="150" value="{{ data_get($refund->metadata, 'return.tracking_no') }}"></label><button>提交物流</button></form></details>@endif
                        @endforeach</td>
                    </tr>@empty<tr><td colspan="5">暂无订单</td></tr>@endforelse
                </tbody></table>{{ $accountOrders->links() }}
            </section>
        @endif
    </div>
</section>
