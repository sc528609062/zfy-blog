@php
    $query = request('q', '');
    $results = $contents->merge($resources)->merge($images)->unique('id');
    $resultCount = $results->count();
@endphp

<section class="a-page-hero compact search">
    <h1>搜索结果</h1>
    <p>找到 {{ $resultCount }} 个与 "{{ $query }}" 相关的结果</p>
    <form class="a-search-large" action="/search">
        <input name="q" value="{{ $query }}" placeholder="搜索游戏、资源、攻略..." autofocus>
        <button>搜索</button>
    </form>
</section>

<section class="a-two-col">
    <div class="a-card-panel">
        <div class="a-tabs-head">
            <h2>搜索结果 ({{ $resultCount }})</h2>
            <nav>
                <a class="active">全部</a>
                <a href="/search?q={{ $query }}&type=post">文章</a>
                <a href="/search?q={{ $query }}&type=files">资源</a>
                <a href="/search?q={{ $query }}&type=images">图集</a>
            </nav>
        </div>

        @if($results->count() > 0)
            <div class="a-feed-list">
                @foreach($results as $item)
                    @include('themes.style-a-blue-gaming.partials.cards', ['item' => $item, 'mode' => 'list'])
                @endforeach
            </div>
            <a class="a-load-more" href="#">加载更多结果</a>
        @else
            <div class="a-empty-state">
                <h3>没有找到相关内容</h3>
                <p>试试其他关键词，或浏览推荐内容</p>
                <a href="/" class="a-primary">返回首页</a>
            </div>
        @endif
    </div>

    <aside class="a-side-stack">
        <div class="a-card-panel">
            <div class="a-section-title"><h3>热门搜索</h3></div>
            <div class="a-filter-tags">
                @foreach(['幻境之旅', '角色扮演', '开放世界', 'MOD工具', '攻略教程', '隐藏任务', '装备获取', '新手指南'] as $tag)
                    <a href="/search?q={{ urlencode($tag) }}">{{ $tag }}</a>
                @endforeach
            </div>
        </div>

        @include('themes.style-a-blue-gaming.partials.ranking', ['title' => '热门推荐'])

        <div class="a-card-panel">
            <div class="a-section-title"><h3>搜索建议</h3></div>
            <ul class="a-simple-list">
                <li>使用更具体的关键词</li>
                <li>尝试不同的搜索词组合</li>
                <li>检查拼写是否正确</li>
                <li>使用标签进行筛选</li>
            </ul>
        </div>
    </aside>
</section>
