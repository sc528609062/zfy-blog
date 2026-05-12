<section class="page-title">
    <h1>{{ [
        'posts-channel' => '文章频道',
        'images-channel' => '图集频道',
        'files-channel' => '资源下载',
        'category-page' => $currentCategory->name ?? '分类聚合',
        'tag-page' => '#'.($currentTag->name ?? '热门标签'),
        'search' => '搜索结果',
        'rank' => '排行榜',
        'points-store' => '积分商城',
        'authors' => '作者列表',
        'author-profile' => '作者主页',
        'links' => '友情链接',
    ][$page] ?? 'zfy-blog' }}</h1>
    <p>筛选、排序、权限、VIP 折扣和内容商业化模块均已接入统一数据结构。</p>
</section>
<section class="content-layout">
    <div class="main-feed">
        <div class="card-grid">
            @foreach($contents->merge($resources)->merge($images)->take(12) as $item)
                @include('themes.shared.partials.item-card', ['item' => $item])
            @endforeach
        </div>
    </div>
    <aside class="sidebar">
        <div class="panel">
            <h3>筛选</h3>
            @foreach($categories as $category)
                <a class="filter-row" href="/c/{{ $category->slug }}">{{ $category->name }} <span>{{ rand(80, 900) }}</span></a>
            @endforeach
        </div>
    </aside>
</section>
