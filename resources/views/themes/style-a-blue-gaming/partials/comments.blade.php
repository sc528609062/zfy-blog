@php
    $comments = $comments ?? collect([]);
    $commentCount = $content->comment_count ?? $comments->count();
@endphp

<section class="a-comments-section">
    <div class="a-section-title">
        <h2>评论 ({{ $commentCount }})</h2>
    </div>

    @auth
        <form class="a-comment-form" method="post" action="/content/{{ $content->slug ?? '#' }}/comments">
            @csrf
            <img src="{{ auth()->user()->avatar_url ?? '/assets/zfy/placeholders/avatar.svg' }}" alt="用户头像">
            <textarea name="content" placeholder="发表你的看法..." rows="3" required></textarea>
            <button type="submit" class="a-primary">发表评论</button>
        </form>
    @else
        <div class="a-comment-login">
            <p>登录后才能发表评论</p>
            <a href="/login" class="a-primary">立即登录</a>
        </div>
    @endauth

    <div class="a-comments-list">
        @forelse($comments->take(20) as $comment)
            <article class="a-comment-item">
                <img src="{{ $comment->user->avatar_url ?? '/assets/zfy/placeholders/avatar.svg' }}" alt="用户头像">
                <div class="a-comment-body">
                    <div class="a-comment-head">
                        <strong>{{ $comment->user->name ?? '匿名用户' }}</strong>
                        @if($comment->user->is_author ?? false)
                            <span class="a-badge">作者</span>
                        @endif
                        <time>{{ optional($comment->created_at)->diffForHumans() ?? '刚刚' }}</time>
                    </div>
                    <p>{{ $comment->content ?? '这是一条演示评论内容。' }}</p>
                    <div class="a-comment-actions">
                        <button class="a-comment-like">赞 ({{ $comment->likes_count ?? 0 }})</button>
                        <button class="a-comment-reply">回复</button>
                    </div>

                    @if($comment->replies->count() > 0)
                        <div class="a-comment-replies">
                            @foreach($comment->replies as $reply)
                                <article class="a-comment-reply-item">
                                    <img src="{{ $reply->user->avatar_url ?? '/assets/zfy/placeholders/avatar.svg' }}" alt="用户头像">
                                    <div>
                                        <strong>{{ $reply->user->name ?? '匿名用户' }}</strong>
                                        <span>回复</span>
                                        <strong>{{ $reply->parent->user->name ?? '楼主' }}</strong>
                                        <p>{{ $reply->content }}</p>
                                        <time>{{ optional($reply->created_at)->diffForHumans() ?? '刚刚' }}</time>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="a-empty-state">
                <p>暂无评论，快来发表第一条评论吧！</p>
            </div>
        @endforelse
    </div>

    @if($comments->count() > 20)
        <button class="a-load-more">加载更多评论</button>
    @endif
</section>
