@php
    $hero = $featured;
    $feed = $contents->merge($resources)->merge($images)->unique('id')->take(6);
    $tone = $themeTone ?? 'blue';
    $heroImage = match ($tone) {
        'market' => asset('theme-assets/b-home-hero.png'),
        'creative' => asset('theme-assets/c-home-hero.png'),
        default => asset('theme-assets/a-home-hero.png'),
    };
    $heroTitle = match ($tone) {
        'market' => '优质资源 · 一站购齐',
        'creative' => '创意有趣，资源无限',
        default => '幻境之旅 · 新版本上线',
    };
    $heroSub = match ($tone) {
        'market' => '网站源码 / 设计素材 / 教程课程 / 插件扩展',
        'creative' => '加入 zfy-blog，发现优质资源，连接有趣的创作者！',
        default => '全新地图开放，隐藏剧情与强力装备等你探索',
    };
@endphp

@if($tone === 'market')
<section class="b-home-layout">
    <aside class="b-category-sidebar">
        <h3>全部分类</h3>
        @foreach(['网站源码','设计素材','插件扩展','教程课程','文档模板','软件工具'] as $label)
            <a href="/files">{{ $label }}<small>{{ $loop->odd ? '企业网站、博客、博客' : 'UI组件、图标、样机' }}</small></a>
        @endforeach
    </aside>
    <div class="b-home-main">
        <section class="b-main-hero" style="--theme-hero:url('{{ asset('theme-assets/b-home-art.png') }}')">
            <h1>优质资源 · 一站购齐</h1>
            <p>网站源码 / 设计素材 / 教程课程 / 插件扩展</p>
            <div class="b-hero-stats"><strong>10,246+<span>优质资源</span></strong><strong>28,563+<span>注册用户</span></strong><strong>98.6%<span>好评率</span></strong></div>
            <a href="/files">立即探索</a>
        </section>
        <div class="b-channel-icons">@foreach($categories->take(6) as $category)<a href="/c/{{ $category->slug }}"><span>{{ mb_substr($category->name,0,1) }}</span><strong>{{ $category->name }}</strong><small>{{ rand(1200,3400) }}+</small></a>@endforeach</div>
        <section class="b-product-panel">
            <div class="a-section-title"><h2>热门资源推荐</h2><a href="/files">更多资源</a></div>
            <div class="b-home-products">
                @foreach($resources->merge($images)->take(4) as $item)
                    <article><img src="{{ asset('theme-assets/b-product-'.(($loop->index % 6) + 1).'.png') }}" alt="{{ $item->title }}"><span>{{ $loop->odd ? 'NEW' : 'SALE' }}</span><h3>{{ $item->title }}</h3><p>{{ $item->subtitle ?? 'Vue3 + Element Plus' }}</p><footer><strong>¥{{ data_get($item,'pricing.price',59) ?: 59 }}</strong><em>VIP ¥{{ data_get($item,'pricing.vip_price',39) ?: 39 }}</em></footer></article>
                @endforeach
            </div>
        </section>
        <section class="b-news-panel">
            <div class="a-tabs-head"><h2>最新发布</h2><nav><a class="active">最新发布</a><a>热门文章</a><a>教程更新</a><a>行业资讯</a></nav></div>
            @foreach($contents->take(3) as $item)
                <a href="/content/{{ $item->slug }}"><img src="{{ $item->cover_url }}" alt=""><span><strong>{{ $item->title }}</strong><small>{{ $item->excerpt }}</small></span><b>前端开发</b></a>
            @endforeach
        </section>
    </div>
    <aside class="b-shop-sidebar">
        <div class="b-market-vip"><h3>zfy-blog VIP</h3><p>全站资源低价下载</p><div><span>全部资源</span><span>专属折扣</span><span>优先客服</span><span>每月礼包</span></div></div>
        <div class="b-coupon-card"><h3>优惠券中心</h3><p><strong>¥20</strong> 全站通用券</p><p><strong>¥50</strong> 全站通用券</p><p><strong>¥100</strong> 满站通用券</p></div>
        @include('themes.style-a-blue-gaming.partials.ranking', ['title' => '资源热销榜'])
        <div class="b-data-card"><h3>平台数据统计</h3><p>10,246+ 资源总数</p><p>28,563+ 注册用户</p><p>3,456+ 今日访问</p><p>98.6% 用户好评</p></div>
    </aside>
</section>
@elseif($tone === 'creative')
<section class="c-home-layout">
    <aside class="c-creator-sidebar">
        <a class="active" href="/">首页</a><a href="/authors">我的关注</a><a href="/user/downloads">下载记录</a><a href="/admin/editor">我的投稿</a><a href="/user">我的消息</a>
        <h3>频道</h3>
        @foreach(['设计素材','插画绘画','摄影修图','视频音频','编程开发','学习成长','生活灵感','游戏资源'] as $label)<a href="/images">{{ $label }}</a>@endforeach
        <div class="c-promo-card"><strong>邀请好友</strong><p>一起创作 赢取VIP特权</p><button>立即邀请</button></div>
    </aside>
    <div class="c-home-main">
        <section class="c-main-hero" style="--theme-hero:url('{{ asset('theme-assets/c-home-art.png') }}')"><div><h1>创意有趣，资源无限</h1><p>加入 zfy-blog，发现优质资源，连接有趣的创作者！</p><a href="/images">探索更多资源</a></div><span></span></section>
        <div class="c-channel-strip">@foreach($categories->take(6) as $category)<a href="/c/{{ $category->slug }}"><span>{{ mb_substr($category->name,0,1) }}</span><strong>{{ $category->name }}</strong><small>{{ rand(9000,28000)/1000 }}k 资源</small></a>@endforeach</div>
        <section class="c-feed-panel"><div class="a-tabs-head"><h2>推荐</h2><nav><a class="active">推荐</a><a>最新</a><a>关注</a><a>热门</a></nav></div><div class="c-card-grid">@foreach($images->merge($resources)->merge($contents)->take(6) as $item)<article><img src="{{ asset('theme-assets/c-card-'.(($loop->index % 8) + 1).'.png') }}" alt=""><span>{{ $loop->odd ? '插画绘画' : '设计素材' }}</span><h3>{{ $item->title }}</h3><p>{{ $item->author->name ?? '创作者' }} · {{ number_format($item->view_count ?? 865) }} 浏览</p></article>@endforeach</div><a class="a-load-more">加载更多内容</a></section>
    </div>
    <aside class="c-rightbar">
        <div class="c-profile-card"><img src="/assets/zfy/placeholders/avatar.svg" alt=""><h3>小桃子 <span>VIP</span></h3><p>Lv.6 创意达人</p><div><strong>68<small>关注</small></strong><strong>1.2k<small>粉丝</small></strong><strong>328<small>获赞</small></strong><strong>56<small>作品</small></strong></div></div>
        <div class="c-vip-home"><h3>zfy-blog VIP</h3><p>开通 VIP，享受更多特权</p><a href="/vip">立即开通</a></div>
        @include('themes.style-a-blue-gaming.partials.ranking', ['title' => '资源排行榜'])
        <div class="c-side-card"><h3>活跃创作者</h3>@foreach(['鹿小萌','设计师阿Z','摄影小白'] as $name)<p><img src="/assets/zfy/placeholders/avatar.svg" alt=""><span>{{ $name }}<small>创作者</small></span><button>关注</button></p>@endforeach</div>
    </aside>
</section>
@else
<section class="a-home-grid">
    <div class="a-hero" style="--hero-image:url('{{ $heroImage }}')">
        <div>
            <h1>{{ $heroTitle }}</h1>
            <p>{{ $heroSub }}</p>
            <a class="a-primary" href="{{ $hero ? '/content/'.$hero->slug : '/files' }}">{{ $tone === 'creative' ? '探索更多资源' : ($tone === 'market' ? '立即探索' : '立即查看') }}</a>
        </div>
        <div class="a-slider-dots"><span></span><span></span><span></span></div>
    </div>

    <aside class="a-profile-card">
        <img src="{{ asset('theme-assets/a-avatar.png') }}" alt="站长头像">
        <div>
            <h3>{{ $tone === 'creative' ? '小桃子' : ($tone === 'market' ? 'Zfy_123' : 'zfy小站长') }} <b>LV.{{ $tone === 'market' ? '6' : '8' }}</b></h3>
            <p>{{ $tone === 'market' ? '店铺评分 98.6%' : '经验值 2876 / 4200' }}</p>
            <div class="a-progress"><i style="width:68%"></i></div>
        </div>
        <div class="a-profile-stats">
            <strong>128<span>发布</span></strong>
            <strong>56<span>关注</span></strong>
            <strong>3421<span>粉丝</span></strong>
            <strong>1.2w<span>获赞</span></strong>
        </div>
    </aside>

    <aside class="a-vip-mini">
        <div>
            <h3>VIP</h3>
            <p>{{ $tone === 'market' ? '全站资源低价下载' : '开通 VIP 享专属特权' }}</p>
        </div>
        <a href="/vip">开通</a>
        <div class="a-vip-perks"><span>专属资源</span><span>高速下载</span><span>无限收藏</span><span>更多特权</span></div>
    </aside>
</section>
@endif

@if($tone === 'blue')
<section class="a-section">
    <div class="a-section-title">
            <h2>{{ $tone === 'market' ? '全部分类' : ($tone === 'creative' ? '频道' : '热门频道') }}</h2>
        <a href="/files">更多</a>
    </div>
    <div class="a-channel-grid">
        @foreach($categories->take(8) as $category)
            <a href="/c/{{ $category->slug }}">
                <span>{{ mb_substr($category->name, 0, 1) }}</span>
                <strong>{{ $category->name }}</strong>
                <small>{{ number_format(($category->contents_count ?? 120) + 120) }}+ 内容</small>
            </a>
        @endforeach
    </div>
</section>

<section class="a-two-col">
    <div class="a-card-panel">
        <div class="a-tabs-head">
            <h2>{{ $tone === 'market' ? '热门资源推荐' : ($tone === 'creative' ? '推荐' : '最新动态') }}</h2>
            <nav><a class="active">{{ $tone === 'creative' ? '推荐' : '全部' }}</a><a>{{ $tone === 'market' ? '最新发布' : '资源发布' }}</a><a>{{ $tone === 'creative' ? '关注' : '攻略教程' }}</a><a>{{ $tone === 'market' ? '行业资讯' : '社区话题' }}</a></nav>
        </div>
        <div class="a-feed-list">
            @foreach($feed as $item)
                @include('themes.style-a-blue-gaming.partials.cards', ['item' => $item, 'mode' => 'list'])
            @endforeach
        </div>
        <a class="a-load-more" href="/posts">加载更多</a>
    </div>

    <aside class="a-side-stack">
        @include('themes.style-a-blue-gaming.partials.ranking', ['title' => $tone === 'market' ? '资源热销榜' : '资源排行榜'])
        <div class="a-card-panel">
            <div class="a-section-title"><h3>{{ $tone === 'creative' ? '热门话题' : '社区热议' }}</h3><a href="/posts">更多</a></div>
            @foreach(['隐藏结局触发条件详解', '分享我珍藏的100+款经典游戏合集', '新手必看：如何安全下载游戏资源', '大家心中最低估的神作是哪款？'] as $topic)
                <a class="a-topic-row" href="/posts"><span>{{ $topic }}</span><b>{{ rand(64, 235) }} 回复</b></a>
            @endforeach
        </div>
    </aside>
</section>
@endif
