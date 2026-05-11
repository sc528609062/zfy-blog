<div class="a-card-panel">
    <div class="a-section-title">
        <h3>{{ $title ?? '排行榜' }}</h3>
        <nav class="a-mini-tabs"><a class="active">今日</a><a>本周</a><a>本月</a></nav>
    </div>
    <div class="a-rank-list">
        @foreach($rankings->take(6) as $rank)
            <a href="/content/{{ $rank->slug }}" class="a-rank-row">
                <b>{{ $loop->iteration }}</b>
                <img src="{{ $rank->cover_url }}" alt="{{ $rank->title }}">
                <span>{{ $rank->title }}</span>
                <em>{{ number_format($rank->view_count ?? 0) }}</em>
            </a>
        @endforeach
    </div>
    <a class="a-load-more compact" href="/rank">查看全部</a>
</div>
