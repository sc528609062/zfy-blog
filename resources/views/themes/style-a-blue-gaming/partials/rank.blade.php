@php
    $tone = $themeTone ?? 'blue';
@endphp

<section class="a-page-hero compact">
    <h1>排行榜</h1>
    <p>按热度、下载、收藏与评论综合排序</p>
</section>

<section class="a-two-col">
    <div class="a-card-panel">
        <div class="a-tabs-head">
            <h2>内容排行</h2>
            <nav>
                <a class="active">综合</a>
                <a>今日</a>
                <a>本周</a>
                <a>本月</a>
                <a>全部</a>
            </nav>
        </div>

        <div class="a-rank-table">
            @foreach($rankings->take(50) as $rank)
                <article class="a-rank-item">
                    <span class="a-rank-number {{ $loop->iteration <= 3 ? 'top' : '' }}">{{ $loop->iteration }}</span>
                    <img src="{{ $rank->cover_url }}" alt="{{ $rank->title }}">
                    <div class="a-rank-info">
                        <h3><a href="/content/{{ $rank->slug }}">{{ $rank->title }}</a></h3>
                        <p>{{ $rank->excerpt ?? '精选游戏资源、攻略教程与社区内容。' }}</p>
                        <div class="a-meta">
                            <span>{{ $rank->author->name ?? 'zfy小助手' }}</span>
                            <span>{{ number_format($rank->view_count ?? 0) }} 浏览</span>
                            <span>{{ $rank->comment_count ?? 0 }} 评论</span>
                        </div>
                    </div>
                    <div class="a-rank-stats">
                        <strong>{{ number_format($rank->view_count ?? 0) }}</strong>
                        <small>热度值</small>
                    </div>
                </article>
            @endforeach
        </div>
    </div>

    <aside class="a-side-stack">
        <div class="a-card-panel">
            <div class="a-section-title"><h3>作者排行</h3></div>
            <div class="a-author-rank">
                @foreach(['zfy小助手', '攻略组-星辰', '设计师阿之', 'MOD达人', '风之旅人'] as $author)
                    <article>
                        <span>{{ $loop->iteration }}</span>
                        <img src="https://api.dicebear.com/8.x/adventurer/svg?seed={{ urlencode($author) }}" alt="{{ $author }}">
                        <div>
                            <strong>{{ $author }}</strong>
                            <small>{{ rand(100, 500) }} 作品</small>
                        </div>
                        <button>关注</button>
                    </article>
                @endforeach
            </div>
        </div>

        <div class="a-card-panel">
            <div class="a-section-title"><h3>分类排行</h3></div>
            <div class="a-category-rank">
                @foreach($categories->take(8) as $category)
                    <a href="/c/{{ $category->slug }}">
                        <span>{{ $loop->iteration }}</span>
                        <strong>{{ $category->name }}</strong>
                        <small>{{ number_format(($category->contents_count ?? 0) + 120) }} 内容</small>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="a-card-panel">
            <div class="a-section-title"><h3>标签排行</h3></div>
            <div class="a-filter-tags">
                @foreach(['角色扮演', '动作冒险', '策略模拟', '竞技对战', '独立游戏', '开放世界', '多人联机', '单机剧情'] as $tag)
                    <a href="/tag/{{ str($tag)->slug() }}">{{ $tag }}</a>
                @endforeach
            </div>
        </div>
    </aside>
</section>
