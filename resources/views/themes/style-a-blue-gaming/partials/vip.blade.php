@php
    $plans = $vipLevels->values();
    $tone = $themeTone ?? 'blue';
@endphp
@if($tone === 'market')
<section class="b-vip-layout">
    <aside class="b-category-sidebar vip-menu">
        @foreach(['会员中心','我的订单','我的资源','我的收藏','优惠券','资金管理','发布资源','我的店铺','消息中心','账号设置'] as $label)
            <a class="{{ $loop->first ? 'active' : '' }}" href="/vip">{{ $label }}</a>
        @endforeach
        <div class="b-vip-box"><strong>邀请好友 赚佣金</strong><p>最高可得 20% 返利</p><a>立即邀请</a></div>
    </aside>
    <div class="b-vip-main">
        <section class="b-vip-title"><h1>VIP会员</h1><p>开通VIP会员，享受更多专属特权和超值优惠</p></section>
        <section class="b-vip-banner" style="--theme-hero:url('{{ asset('theme-assets/b-vip-banner.png') }}')"><h2>尊享会员特权，海量资源免费下载</h2><p>加入超过 120,000+ 用户的选择</p></section>
        <div class="b-vip-plans">
            @foreach([['月度会员','19.90','/月'],['季度会员','49.90','/3月'],['年度会员','168.00','/年'],['永久会员','298.00','']] as [$name,$price,$unit])
                <article class="{{ $loop->first ? 'active' : '' }}"><h3>{{ $name }}</h3><strong>¥{{ $price }}<span>{{ $unit }}</span></strong><small>原价 ¥{{ $loop->first ? '29.90' : '398.00' }}</small><button>立即开通</button></article>
            @endforeach
        </div>
        <section class="a-card-panel"><div class="a-section-title"><h2>会员特权对比</h2></div><table class="a-ui-table"><tbody>@foreach(['资源免费下载','专属折扣','VIP专属资源','高速下载通道','优先客服支持','资源更新提醒','专属优惠券','免费发布资源'] as $row)<tr><td>{{ $row }}</td><td>月度会员</td><td>季度会员</td><td>年度会员</td><td>永久会员</td></tr>@endforeach</tbody></table></section>
    </div>
    <aside class="b-payment-sidebar"><h3>支付信息</h3><p><span>商品信息</span><b>年度会员</b></p><p><span>优惠券</span><b>-¥20.00</b></p><p><span>应付金额</span><strong>¥148.00</strong></p>@foreach(['支付宝支付','微信支付','余额支付','银行卡支付','云闪付支付'] as $pay)<label><input type="radio" @checked($loop->first)> {{ $pay }}</label>@endforeach<button>立即支付 ¥148.00</button></aside>
</section>
@elseif($tone === 'creative')
<section class="c-vip-layout">
    <aside class="c-creator-sidebar vip-menu">
        @foreach(['首页','资源广场','创作者','灵感集市','活动中心','VIP会员','我的收藏','我的下载','消息通知','收益中心'] as $label)
            <a class="{{ $label === 'VIP会员' ? 'active' : '' }}" href="/vip">{{ $label }}</a>
        @endforeach
        <div class="c-user-card"><img src="/assets/zfy/placeholders/avatar.svg" alt=""><strong>小z奶芙</strong><span>Lv.6 创意达人</span><p>286 收藏 · 1.2k 粉丝 · 89 资源</p></div>
    </aside>
    <div class="c-vip-main">
        <section class="c-vip-hero" style="--theme-hero:url('{{ asset('theme-assets/c-vip-hero.png') }}')"><div><h1>解锁无限创意可能</h1><p>海量优质资源免费下载，专属特权助力创作</p></div><span>VIP</span></section>
        <div class="c-vip-plans">@foreach([['月度会员','18','/月'],['季度会员','45','/季'],['年度会员','158','/年'],['永久会员','398','/永久']] as [$name,$price,$unit])<article class="{{ $loop->first ? 'active' : '' }}"><h3>{{ $name }}</h3><strong>¥{{ $price }}<span>{{ $unit }}</span></strong><button>{{ $loop->first ? '当前选择' : '选择套餐' }}</button><p>✓ 解锁 VIP 权益<br>✓ 适合创作者使用</p></article>@endforeach</div>
        <section class="a-card-panel"><div class="a-section-title"><h2>会员尊享特权</h2></div><div class="a-perk-grid">@foreach(['全站资源免费下载','高速下载通道','专属资源标识','每日更新提醒','专属客服支持','会员专属活动'] as $perk)<div><span>{{ mb_substr($perk,0,1) }}</span><strong>{{ $perk }}</strong><p>创意工作更轻松</p></div>@endforeach</div></section>
        <section class="a-card-panel"><div class="a-section-title"><h2>会员权益对比</h2></div><table class="a-ui-table"><tbody>@foreach(['免费资源','高速下载','专属资源','资源收藏数量','每日更新提醒','专属客服','会员活动','会员标识'] as $row)<tr><td>{{ $row }}</td><td>普通用户</td><td>月度会员</td><td>年度会员</td><td>永久会员</td></tr>@endforeach</tbody></table></section>
    </div>
    <aside class="c-payment-sidebar"><div><h3>订单信息</h3><p><span>所选套餐</span><b>月度会员</b></p><p><span>订单金额</span><strong>¥18.00</strong></p><p><span>应付金额</span><strong>¥18.00</strong></p></div><div><h3>支付方式</h3>@foreach(['微信支付','支付宝支付','QQ钱包','银行卡支付'] as $pay)<label><input type="radio" @checked($loop->first)> {{ $pay }}</label>@endforeach<button>立即支付 ¥18.00</button></div></aside>
</section>
@else
<section class="a-account-layout vip-layout">
    @include('themes.style-a-blue-gaming.partials.account-menu')
    <div class="a-account-main">
        <section class="a-vip-hero">
            <h1>开通VIP会员</h1>
            <p>享受专属特权，畅游游戏世界</p>
            <span>V</span>
        </section>
        <section class="a-card-panel">
            <div class="a-section-title"><h2>选择会员套餐</h2><small>开通即表示同意 zfy-blog 会员服务协议</small></div>
            <div class="a-plan-grid">
                @forelse($plans as $level)
                    <article class="a-plan-card {{ $loop->first ? 'active' : '' }}">
                        <h3>{{ $level->name }}</h3>
                        <strong>¥{{ $level->price_monthly ?: $level->price_yearly }}<span>/月</span></strong>
                        <p>年费 ¥{{ $level->price_yearly }} · {{ $level->discount_percent }}% 折扣</p>
                        <button>立即开通</button>
                    </article>
                @empty
                    @foreach([['月度会员',15],['季度会员',40],['年度会员',148],['永久会员',288]] as [$name,$price])
                        <article class="a-plan-card {{ $loop->first ? 'active' : '' }}"><h3>{{ $name }}</h3><strong>¥{{ $price }}<span>/月</span></strong><p>自动续费，可随时取消</p><button>立即开通</button></article>
                    @endforeach
                @endforelse
            </div>
        </section>
        <section class="a-card-panel">
            <div class="a-section-title"><h2>VIP专属特权</h2></div>
            <div class="a-perk-grid">
                @foreach(['高速下载','资源抢先','专属标识','无限下载','专属客服','积分加成','去广告','更多特权'] as $perk)
                    <div><span>{{ mb_substr($perk, 0, 1) }}</span><strong>{{ $perk }}</strong><p>享受更完整的会员体验</p></div>
                @endforeach
            </div>
        </section>
        <section class="a-card-panel">
            <div class="a-section-title"><h2>会员特权对比</h2></div>
            <table class="a-ui-table">
                <thead><tr><th>特权内容</th><th>普通用户</th><th>月度会员</th><th>年度会员</th><th>永久会员</th></tr></thead>
                <tbody>
                    @foreach(['下载速度','每日下载次数','资源抢先体验','专属客服','积分加成','去广告体验','付费资源下载'] as $row)
                        <tr><td>{{ $row }}</td><td>普通</td><td>✓</td><td>✓</td><td>✓</td></tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </div>
    <aside class="a-order-card">
        <h3>订单详情</h3>
        <p><span>选择套餐</span><b>月度会员</b></p>
        <p><span>会员时长</span><b>1个月</b></p>
        <p><span>应付金额</span><strong>¥15.00</strong></p>
        <h4>支付方式</h4>
        @foreach(['微信支付','支付宝支付','QQ钱包','银行卡支付'] as $pay)
            <label><input type="radio" name="pay" @checked($loop->first)> {{ $pay }}</label>
        @endforeach
        <button>立即支付 ¥15.00</button>
    </aside>
</section>
@endif
