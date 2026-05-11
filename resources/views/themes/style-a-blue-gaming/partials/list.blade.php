@php
    $allItems = $contents->merge($resources)->merge($images)->unique('id')->take(12);
    $titles = [
        'posts-channel' => ['攻略教程', '图文攻略、版本评测、玩法技巧与社区经验'],
        'images-channel' => ['MOD社区', '精选图集、角色设定、壁纸与玩家创作'],
        'files-channel' => ['游戏资源', '精品资源、MOD、工具、存档与下载专区'],
        'category-page' => [$currentCategory->name ?? '分类聚合', $currentCategory->description ?? '按分类聚合优质内容'],
        'tag-page' => ['#'.($currentTag->name ?? '热门标签'), '同标签下的资源、攻略和玩家讨论'],
        'search' => ['搜索结果', '根据关键词筛选资源、教程和社区内容'],
        'rank' => ['排行榜', '按热度、下载、收藏与评论综合排序'],
        'points-store' => ['积分商城', '使用积分兑换资源、优惠券和会员权益'],
        'authors' => ['作者列表', '发现活跃创作者和优质资源发布者'],
        'author-profile' => ['作者主页', '作者作品、数据、粉丝与社区动态'],
        'links' => ['友情链接', '合作站点、工具平台与推荐社区'],
    ];
    [$title, $subtitle] = $titles[$page] ?? ['zfy-blog', '游戏资源社区'];
    $isFiles = $page === 'files-channel';
    $isCreativeGallery = $tone === 'creative' && in_array($page, ['images-channel', 'category-page', 'tag-page', 'search'], true);
    $isRank = $page === 'rank';
    $isAuthors = in_array($page, ['authors', 'author-profile'], true);
    $tone = $themeTone ?? 'blue';
@endphp

@if($isFiles && $tone === 'market')
    <section class="b-download-layout">
        <aside class="b-category-sidebar">
            <h3>资源分类</h3>
            @foreach(['全部资源','网站源码','软件工具','插件扩展','模板主题','游戏资源','设计素材','视频教程','编程开发','文档资料'] as $label)
                <a class="{{ $loop->first ? 'active' : '' }}" href="/files">{{ $label }}</a>
            @endforeach
            <div class="b-vip-box"><strong>开通VIP会员</strong><p>全站资源免费下载</p><a href="/vip">立即开通</a></div>
            <div class="b-service-list"><span>安全保障</span><span>极速下载</span><span>售后无忧</span><span>优质资源</span></div>
        </aside>
        <div class="b-download-main">
            <section class="b-download-hero" style="--theme-hero:url('{{ asset('theme-assets/b-files-art.png') }}')">
                <div>
                    <h1>海量优质资源下载</h1>
                    <p>精选源码、软件、教程、素材等一站式下载服务</p>
                    <div class="b-hero-stats"><strong>128,594<span>资源总数</span></strong><strong>23,156<span>今日下载</span></strong><strong>8,929<span>会员总数</span></strong><strong>99.9%<span>好评率</span></strong></div>
                </div>
            </section>
            <div class="b-tool-strip"><a>每日签到<span>签到领积分</span></a><a>积分商城<span>积分兑换好礼</span></a><a>优惠券<span>领取优惠券</span></a><a>会员中心<span>管理会员权益</span></a></div>
            <section class="b-product-panel">
                <div class="b-filter-tabs"><nav><a class="active">推荐</a><a>最新</a><a>最热</a><a>免费</a><a>VIP专享</a></nav><span>默认排序 · 发布时间 · 下载量 · 价格</span></div>
                <div class="b-product-grid">
                    @foreach($resources->merge($contents)->take(6) as $item)
                        <article class="b-product-card">
                            <a href="/content/{{ $item->slug }}"><img src="{{ asset('theme-assets/b-product-'.(($loop->index % 6) + 1).'.png') }}" alt="{{ $item->title }}"></a>
                            <span class="b-tag">{{ $loop->odd ? '热门' : 'VIP免费' }}</span>
                            <h3><a href="/content/{{ $item->slug }}">{{ $item->title }}</a></h3>
                            <p>{{ $item->subtitle ?? '响应式设计 · 商用授权' }}</p>
                            <div class="b-card-tags"><span>PHP</span><span>企业网站</span><span>响应式</span></div>
                            <footer><strong>¥{{ data_get($item, 'pricing.price', 39) ?: 39 }}.90</strong><em>VIP ¥{{ data_get($item, 'pricing.vip_price', 19) ?: 19 }}.90</em><button>购</button></footer>
                        </article>
                    @endforeach
                </div>
                <a class="a-load-more" href="/files">加载更多资源</a>
            </section>
        </div>
        <aside class="b-shop-sidebar">
            @include('themes.style-a-blue-gaming.partials.ranking', ['title' => '下载排行榜'])
            <div class="b-deal-card"><h3>限时优惠 <span>23:59:45</span></h3><img src="{{ $resources->first()->cover_url ?? '' }}" alt=""><strong>企业网站源码套餐</strong><b>¥199.00</b><a href="/vip">立即抢购</a></div>
            <div class="b-cart-card"><h3>我的购物车 <span>3</span></h3>@foreach($resources->take(3) as $item)<p><span>{{ $item->title }}</span><b>¥{{ data_get($item, 'pricing.price', 39) ?: 39 }}.90</b></p>@endforeach<a>去结算</a></div>
            <div class="b-coupon-card"><h3>优惠券</h3><p><strong>¥20</strong> 全站通用优惠券</p><p><strong>8折</strong> VIP专享优惠券</p></div>
        </aside>
    </section>
@elseif($isCreativeGallery)
    <section class="c-gallery-layout">
        <aside class="c-creator-sidebar">
            <a class="active" href="/">首页</a><a href="/images">发现</a><a href="/authors">关注</a><a href="/vip">活动</a><a href="/rank">排行榜</a>
            <h3>资源频道</h3>
            @foreach(['插画','壁纸','UI 设计','图标','模板','字体','3D 资源','配色'] as $label)
                <a href="/images">{{ $label }}</a>
            @endforeach
            <div class="c-promo-card"><strong>创作有价值的内容</strong><p>收获粉丝 & 收益</p><button>立即创作</button></div>
        </aside>
        <div class="c-gallery-main">
            <section class="c-gallery-head">
                <div class="c-channel-icon">✎</div>
                <div><h1>{{ $page === 'images-channel' ? '插画' : $title }}</h1><p>分享优秀的插画作品，交流创作灵感和技巧</p><div><span>作品 23,456</span><span>创作者 6,789</span><button>关注</button></div></div>
                <div class="c-head-art" style="--theme-hero:url('{{ asset('theme-assets/c-gallery-art.png') }}')">灵感无限 · 创意发光</div>
            </section>
            <div class="c-filter-cloud"><a>最新</a><a class="active">最热</a><a>精选</a><a>全部</a><a>扁平风</a><a>可爱风</a><a>手绘</a><a>二次元</a><a>水彩</a><a>更多</a></div>
            <section class="c-masonry-grid">
                @foreach($images->merge($resources)->merge($contents)->take(8) as $item)
                    <article class="c-masonry-card">
                        <a href="/content/{{ $item->slug }}"><img src="{{ asset('theme-assets/c-card-'.(($loop->index % 8) + 1).'.png') }}" alt="{{ $item->title }}"></a>
                        <h3>{{ $item->title }}</h3>
                        <p><img src="{{ $item->author->avatar_url ?? 'https://api.dicebear.com/8.x/adventurer/svg?seed=c-author-'.$loop->index }}" alt=""> {{ $item->author->name ?? '小雨在画画' }}</p>
                        <footer><span>♡ {{ number_format($item->like_count ?? 1200) }}</span><span>◎ {{ $item->comment_count ?? 23 }}</span><span>▱ {{ $item->download_count ?? 256 }}</span></footer>
                    </article>
                @endforeach
            </section>
        </div>
        <aside class="c-rightbar">
            <div class="c-side-card"><h3>推荐创作者</h3>@foreach(['星野梦','小雨在画画','甜味收藏家','卡卡西里','一只柚子'] as $name)<p><img src="https://api.dicebear.com/8.x/adventurer/svg?seed={{ urlencode($name) }}" alt=""><span>{{ $name }}<small>粉丝 {{ rand(4, 12) }}.{{ rand(1, 9) }}k</small></span><button>关注</button></p>@endforeach</div>
            <div class="c-side-card"><h3>热门标签</h3><div class="c-tags">@foreach(['可爱风','手绘','壁纸','二次元','风景','国风','治愈','水彩'] as $tag)<a># {{ $tag }}</a>@endforeach</div></div>
            <div class="c-award-card"><strong>创作激励计划</strong><p>参与活动赢取丰厚奖励</p><a href="/vip">立即参与</a></div>
        </aside>
    </section>
@elseif($isFiles)
    <section class="a-market-layout">
        @include('themes.style-a-blue-gaming.partials.filters')
        <div>
            <section class="a-resource-hero" style="--hero-image:url('{{ $featured->cover_url ?? 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=1600&q=85' }}')">
                <span>精品推荐</span>
                <h1>{{ $tone === 'market' ? '企业官网响应式模板' : '幻想纪元：重生' }}</h1>
                <p>{{ $tone === 'market' ? 'Vue3 + Element Plus · 商用授权 · 售后支持' : '完整中文豪华版 · 赠送修改器 + 全 DLC' }}</p>
                <div><b>¥68.00</b><em>VIP ¥54.40</em><a href="/files">立即下载</a></div>
            </section>
            <div class="a-feature-strip">
                <div><b>今日更新</b><span>128 个资源更新</span></div>
                <div><b>精品推荐</b><span>编辑精选资源</span></div>
                <div><b>会员专享</b><span>VIP 免费下载</span></div>
                <div><b>限时特惠</b><span>每日优惠不停</span></div>
            </div>
            <section class="a-card-panel">
                <div class="a-tabs-head"><h2>最新资源</h2><nav><a class="active">最新资源</a><a>热门资源</a><a>精华资源</a><a>即将发布</a></nav></div>
                <div class="a-resource-list">
                    @foreach($resources->merge($contents)->take(8) as $item)
                        @include('themes.style-a-blue-gaming.partials.cards', ['item' => $item, 'mode' => 'resource'])
                    @endforeach
                </div>
            </section>
        </div>
        <aside class="a-side-stack">
            @include('themes.style-a-blue-gaming.partials.user-mini')
            @include('themes.style-a-blue-gaming.partials.ranking', ['title' => '下载排行榜'])
        </aside>
    </section>
@elseif($isRank)
    <section class="a-page-hero compact">
        <h1>{{ $title }}</h1>
        <p>{{ $subtitle }}</p>
    </section>
    <section class="a-card-panel">
        <div class="a-rank-board">
            @foreach($rankings->take(10) as $rank)
                <a href="/content/{{ $rank->slug }}" class="a-rank-card">
                    <b>{{ $loop->iteration }}</b>
                    <img src="{{ $rank->cover_url }}" alt="{{ $rank->title }}">
                    <strong>{{ $rank->title }}</strong>
                    <span>{{ number_format($rank->view_count) }} 热度 · {{ number_format($rank->download_count ?? 0) }} 下载</span>
                </a>
            @endforeach
        </div>
    </section>
@elseif($isAuthors)
    <section class="a-page-hero author">
        <h1>{{ $title }}</h1>
        <p>{{ $subtitle }}</p>
    </section>
    <section class="a-author-grid">
        @foreach(['zfy小助手','攻略组-星辰','设计师阿之','MOD达人','风之旅人','夜雨听风'] as $author)
            <article class="a-author-card">
                <img src="https://api.dicebear.com/8.x/adventurer/svg?seed={{ urlencode($author) }}" alt="{{ $author }}">
                <h3>{{ $author }}</h3>
                <p>签约创作者，专注游戏资源、攻略和社区内容。</p>
                <div><span>342 文章</span><span>1.2万 粉丝</span><span>8.6万 获赞</span></div>
                <a href="/author/{{ str($author)->slug() }}">查看主页</a>
            </article>
        @endforeach
    </section>
@else
    <section class="a-page-hero compact">
        <h1>{{ $title }}</h1>
        <p>{{ $subtitle }}</p>
    </section>
    <section class="a-two-col">
        <div class="a-card-panel">
            <div class="a-tabs-head"><h2>{{ $title }}</h2><nav><a class="active">综合</a><a>最新</a><a>热门</a><a>免费</a></nav></div>
            <div class="a-card-grid">
                @foreach($allItems as $item)
                    @include('themes.style-a-blue-gaming.partials.cards', ['item' => $item, 'mode' => 'grid'])
                @endforeach
            </div>
        </div>
        <aside class="a-side-stack">
            @include('themes.style-a-blue-gaming.partials.filters')
            @include('themes.style-a-blue-gaming.partials.ranking', ['title' => '热门推荐'])
        </aside>
    </section>
@endif
