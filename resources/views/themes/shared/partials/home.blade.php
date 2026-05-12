<section class="hero-grid">
    <div class="hero-card">
        <div>
            <h1>{{ $heroTitle }}</h1>
            <p>{{ $heroSub }}</p>
            <a class="primary-btn" href="/files">{{ $isCreative ? '探索更多资源' : ($isMarket ? '立即探索' : '立即查看') }}</a>
        </div>
    </div>
    <aside class="profile-card">
        <img src="/assets/zfy/placeholders/avatar.svg" alt="">
        <strong>{{ $isCreative ? '小桃子' : ($isMarket ? 'Zfy_123' : 'zfy小站长') }}</strong>
        <span>Lv.6 创意达人</span>
        <div class="mini-stats">
            <b>128</b><b>1.2k</b><b>3421</b><b>56</b>
            <span>发布</span><span>粉丝</span><span>获赞</span><span>作品</span>
        </div>
    </aside>
    <aside class="vip-card">
        <h3>zfy-blog VIP</h3>
        <p>开通 VIP，享受更多特权</p>
        <div class="benefits"><span>专属资源</span><span>高速下载</span><span>会员折扣</span><span>优先客服</span></div>
    </aside>
</section>

<section class="channel-strip">
    @foreach($categories as $category)
        <a href="/c/{{ $category->slug }}">
            <span>{{ mb_substr($category->name, 0, 1) }}</span>
            <strong>{{ $category->name }}</strong>
            <small>{{ $category->contents_count ?? rand(120, 3200) }}+ 内容</small>
        </a>
    @endforeach
</section>

<section class="content-layout">
    <div class="main-feed">
        <div class="section-head">
            <h2>{{ $isCreative ? '推荐' : ($isMarket ? '热门资源推荐' : '最新动态') }}</h2>
            <div><a class="active">全部</a><a>资源发布</a><a>攻略教程</a><a>社区话题</a></div>
        </div>
        <div class="{{ $isMarket || $isCreative ? 'card-grid' : 'feed-list' }}">
            @foreach(($isMarket || $isCreative ? $resources->merge($images)->take(6) : $contents->take(6)) as $item)
                @include('themes.shared.partials.item-card', ['item' => $item])
            @endforeach
        </div>
    </div>
    <aside class="sidebar">
        <div class="panel">
            <h3>资源排行榜</h3>
            @foreach($rankings as $rank)
                <a class="rank-row" href="/content/{{ $rank->slug }}"><b>{{ $loop->iteration }}</b><img src="{{ $rank->cover_url }}" alt=""><span>{{ $rank->title }}</span><em>{{ number_format($rank->view_count) }}</em></a>
            @endforeach
        </div>
        <div class="panel">
            <h3>{{ $isCreative ? '活跃创作者' : '社区热议' }}</h3>
            <p>隐藏结局触发条件详解 <span>235 回复</span></p>
            <p>新手必看：如何安全下载资源 <span>89 回复</span></p>
            <p>大家心中最低估的插件是哪个？ <span>64 回复</span></p>
        </div>
    </aside>
</section>
