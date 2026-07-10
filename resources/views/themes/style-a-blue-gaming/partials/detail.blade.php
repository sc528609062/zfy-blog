@php
    $detail = $content ?? $featured;
    $cover = $detail->cover_url ?? '/assets/zfy/placeholders/cover-blue.svg';
    $isFile = $page === 'file-detail' || ($detail->type ?? '') === 'files';
    $isImages = $page === 'images-detail' || ($detail->type ?? '') === 'images';
    $tone = $themeTone ?? 'blue';
    $markdownTheme = data_get($detail->block_json, 'presentation.markdown_theme')
        ?: data_get($theme, 'settings.content-detail.markdown_theme', 'juejin');
    $codeTheme = data_get($detail->block_json, 'presentation.code_theme')
        ?: data_get($theme, 'settings.content-detail.code_theme', 'atom-one-dark');
@endphp

<section class="a-detail-layout">
    <aside class="a-left-nav">
        <h3>全部分类</h3>
        @foreach($categories->take(10) as $category)
            <a href="/c/{{ $category->slug }}"><span>{{ mb_substr($category->name, 0, 1) }}</span>{{ $category->name }}</a>
        @endforeach
        <div class="a-vip-ad">
            <strong>开通VIP会员</strong>
            <p>享受无限下载特权</p>
            <a href="/vip">立即开通</a>
        </div>
    </aside>

    <article class="a-detail-main">
        <nav class="a-breadcrumb">首页 > 攻略教程 > {{ $detail->title ?? '内容详情' }}</nav>
        <div class="a-detail-head">
            <div class="a-labels"><span>攻略教程</span><span>角色扮演</span><span>精华</span></div>
            <h1>{{ $detail->title ?? '《幻境之旅》全剧情流程详解与隐藏任务攻略' }}</h1>
            <p>{{ $detail->excerpt ?? '包含全章节流程指引、隐藏任务触发条件及丰厚奖励获取方法。' }}</p>
            <div class="a-detail-meta">
                <img src="{{ $detail->author->avatar_url ?? '/assets/zfy/placeholders/avatar.svg' }}" alt="作者头像">
                <span>{{ $detail->author->name ?? 'zfy小助手' }}</span>
                <span>{{ optional($detail->published_at)->format('Y-m-d H:i') ?? '2026-05-11 14:30' }}</span>
                <span>{{ number_format($detail->view_count ?? 28600) }} 阅读</span>
                <span>{{ $detail->comment_count ?? 156 }} 评论</span>
                <button>关注</button>
            </div>
        </div>

        @if($isFile)
            <section class="a-download-box">
                <img src="{{ $cover }}" alt="{{ $detail->title }}">
                <div>
                    <h2>资源下载</h2>
                    <p>版本：v1.4.2 · 大小：32.6GB · 语言：简体中文</p>
                    <div><b>¥{{ data_get($detail, 'pricing.price', 68) }}</b><em>VIP ¥{{ data_get($detail, 'pricing.vip_price', 54) }}</em></div>
                </div>
            </section>
        @elseif($isImages)
            <section class="a-gallery-strip">
                @foreach($rankings->take(4) as $rank)
                    <img src="{{ $rank->cover_url }}" alt="{{ $rank->title }}">
                @endforeach
            </section>
        @else
            <section class="a-gallery-strip">
                <img src="{{ $cover }}" alt="{{ $detail->title }}">
                @foreach($rankings->take(3) as $rank)
                    <img src="{{ $rank->cover_url }}" alt="{{ $rank->title }}">
                @endforeach
            </section>
        @endif

        <div
            class="a-article article-content markdown-body"
            data-markdown-theme="{{ $markdownTheme }}"
            data-code-theme="{{ $codeTheme }}"
        >
            {!! $detail->rendered_html ?? '<p>这里是编辑器渲染后的内容。支持隐藏内容、下载块、提示块、代码块、图集和视频等复杂结构。</p>' !!}
        </div>

        @if(data_get($detail, 'pricing.price', 0) > 0 || $isFile)
            <form method="post" action="/buy/{{ $detail->slug }}" class="a-paywall">
                @csrf
                <h3>此处内容需要开通 VIP 或购买后查看</h3>
                <p>包含隐藏任务触发条件、高级装备获取方式和资源下载地址。</p>
                <select name="gateway">
                    <option value="alipay_official">支付宝官方</option>
                    <option value="wechat_official">微信官方</option>
                    <option value="hupijiao_v3">虎皮椒 V3</option>
                    <option value="epay">易支付</option>
                </select>
                <button class="a-primary">立即购买</button>
            </form>
        @endif

        <div class="a-action-row"><button>赞 (256)</button><button>收藏 (892)</button><button>分享</button><button>举报</button></div>

        @include('themes.style-a-blue-gaming.partials.comments')
    </article>

    <aside class="a-side-stack">
        <div class="a-card-panel a-author-box">
            <h3>作者信息</h3>
            <img src="{{ $detail->author->avatar_url ?? '/assets/zfy/placeholders/avatar.svg' }}" alt="作者头像">
            <h2>{{ $detail->author->name ?? 'zfy小助手' }} <span>LV.6</span></h2>
            <p>资深游戏攻略作者，专注资源整理与玩法分享。</p>
            <div><strong>342<span>文章</span></strong><strong>1.2万<span>粉丝</span></strong><strong>8.6万<span>获赞</span></strong></div>
            <a href="/authors">关注作者</a>
        </div>
        @include('themes.style-a-blue-gaming.partials.ranking', ['title' => '相关推荐'])
    </aside>
</section>
