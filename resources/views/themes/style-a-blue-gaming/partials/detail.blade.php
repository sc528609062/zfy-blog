@php
    $detail = $content ?? $featured;
    $cover = $detail->cover_url ?? '/assets/zfy/placeholders/cover-blue.svg';
    $isFile = $page === 'file-detail' || ($detail->type ?? '') === 'files';
    $isImages = $page === 'images-detail' || ($detail->type ?? '') === 'images';
    $tone = $themeTone ?? 'blue';
    $detailOptions = data_get($theme, 'settings.global', []);
    $markdownTheme = data_get($detail->block_json, 'presentation.markdown_theme')
        ?: data_get($theme, 'settings.content-detail.markdown_theme', 'juejin');
    $codeTheme = data_get($detail->block_json, 'presentation.code_theme')
        ?: data_get($theme, 'settings.content-detail.code_theme', 'atom-one-dark');
@endphp

<section class="a-detail-layout {{ ($detailOptions['detail_sidebar'] ?? true) ? '' : 'without-sidebar' }}">
    <article class="a-detail-main">
        <nav class="a-breadcrumb"><a href="/">首页</a> > {{ $detail->category?->name ?? '内容' }} > {{ $detail->title }}</nav>
        <div class="a-detail-head">
            @if($detailOptions['detail_tags'] ?? true)<div class="a-labels">@foreach($detail->tags as $tag)<a href="{{ route('tags.show', $tag->slug) }}">{{ $tag->name }}</a>@endforeach</div>@endif
            <h1>{{ $detail->title }}</h1>
            @if($detailOptions['detail_excerpt'] ?? true)<p>{{ $detail->excerpt }}</p>@endif
            <div class="a-detail-meta">
                <img src="{{ $detail->author->avatar_url ?? '/assets/zfy/placeholders/avatar.svg' }}" alt="作者头像">
                <span>{{ $detail->author->name ?? 'zfy小助手' }}</span>
                @if($detailOptions['detail_date'] ?? true)<time>{{ optional($detail->published_at)->format('Y-m-d H:i') }}</time>@endif
                @if($detailOptions['detail_views'] ?? true)<span>{{ number_format($detail->view_count ?? 0) }} 阅读</span>@endif
                <span>{{ $detail->comment_count ?? 0 }} 评论</span>
                @auth @if($detail->author?->is_author && $detail->author_id !== auth()->id())
                    @php $following = \Illuminate\Support\Facades\DB::table('user_follows')->where('user_id', auth()->id())->where('author_id', $detail->author_id)->exists(); @endphp
                    <form method="post" action="{{ route('authors.follow', $detail->author_id) }}">@csrf<input type="hidden" name="active" value="{{ $following ? 0 : 1 }}"><button>{{ $following ? '取消关注' : '关注' }}</button></form>
                @endif @endauth
            </div>
        </div>

        @if($isFile)
            <section class="a-download-box">
                <img src="{{ $cover }}" alt="{{ $detail->title }}">
                <div>
                    <h2>资源下载</h2>
                    <p>{{ $detail->attachments()->count() }} 个附件</p>
                    <div><b>¥{{ data_get($detail, 'pricing.price', 0) }}</b>@if(data_get($detail->access_rules, 'vip_free'))<em>会员免费</em>@endif</div>
                </div>
            </section>
        @elseif($detailOptions['detail_cover'] ?? true)
            <section class="a-gallery-strip">
                <img src="{{ $cover }}" alt="{{ $detail->title }}">
            </section>
        @endif

        <div
            class="a-article article-content markdown-body"
            data-markdown-theme="{{ $markdownTheme }}"
            data-code-theme="{{ $codeTheme }}"
        >
            {!! $detail->rendered_html ?? '' !!}
        </div>
        @if($canAccess ?? false){!! app(\App\Services\GalleryService::class)->render($detail, auth()->user()) !!}@endif

        @if(filled(data_get($detail->access_rules, 'password_hash')) && !app(\App\Services\ContentPasswordAccess::class)->allows($detail, auth()->user()))
                <form method="post" action="/content/{{ $detail->slug }}/unlock" class="a-paywall">@csrf<label>内容密码 <input type="password" name="password" required maxlength="128"></label><button class="a-primary">解锁内容</button>@if($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif</form>
        @endif
        @if(!($canAccess ?? true))
            @if(!in_array(data_get($detail->access_rules, 'visibility', 'public'), ['public', 'password'], true))
                <p>此内容需要{{ ['member' => '登录', 'vip' => '有效 VIP', 'comment' => '评论审核通过'][data_get($detail->access_rules, 'visibility')] ?? '相应权限' }}后查看。</p>
            @endif
            @if(bccomp((string) data_get($detail->pricing, 'price', '0'), '0', 2) > 0)
            <form method="post" action="/buy/{{ $detail->slug }}" class="a-paywall">
                @csrf
                <h3>购买后查看完整内容</h3>
                <p>¥{{ data_get($detail->pricing, 'price', 0) }} @if(data_get($detail->access_rules, 'vip_free')) · 会员免费 @endif</p>
                <select name="gateway">
                    @include('themes.shared.partials.payment-options')
                </select>
                <button class="a-primary">立即购买</button>
            </form>
            @endif
        @endif

        @if($isFile && ($canAccess ?? false) && $detail->attachments()->exists())
            @foreach($detail->attachments()->where('role', '!=', 'gallery')->get() as $attachment)
                <form method="post" action="{{ route('downloads.create', $detail->slug) }}">@csrf<input type="hidden" name="attachment_id" value="{{ $attachment->id }}"><button class="a-primary">下载 {{ \App\Models\Media::find($attachment->media_id)?->name ?? '附件' }}</button></form>
            @endforeach
        @endif

        @include('themes.style-a-blue-gaming.partials.comments')
        @auth
            <div class="a-action-row">
                @foreach(['like' => '点赞', 'favorite' => '收藏'] as $reaction => $label)
                    <form method="post" action="{{ route('contents.reaction', $detail->slug) }}">@csrf<input type="hidden" name="type" value="{{ $reaction }}"><input type="hidden" name="active" value="{{ in_array($reaction, $reactions ?? []) ? 0 : 1 }}"><button>{{ in_array($reaction, $reactions ?? []) ? '取消'.$label : $label }}</button></form>
                @endforeach
                <details><summary>举报</summary><form method="post" action="{{ route('user.requests') }}">@csrf<input type="hidden" name="type" value="report"><input type="hidden" name="content_id" value="{{ $detail->id }}"><textarea name="body" required maxlength="4000" aria-label="举报原因"></textarea><button>提交举报</button></form></details>
            </div>
        @endauth
    </article>

    @if($detailOptions['detail_sidebar'] ?? true)<aside class="a-side-stack">
        @if(data_get($theme, 'settings.content-detail.show_author_card', true))<div class="a-card-panel a-author-box">
            <h3>作者信息</h3>
            <img src="{{ $detail->author->avatar_url ?? '/assets/zfy/placeholders/avatar.svg' }}" alt="作者头像">
            <h2>{{ $detail->author?->name ?? '作者' }}</h2>
            <p>{{ $detail->author?->bio }}</p>
            <div><strong>{{ $detail->author?->contents()->published()->count() ?? 0 }}<span>内容</span></strong><strong>{{ \Illuminate\Support\Facades\DB::table('user_follows')->where('author_id', $detail->author_id)->count() }}<span>粉丝</span></strong><strong>{{ $detail->author?->contents()->published()->sum('like_count') ?? 0 }}<span>获赞</span></strong></div>
            <a href="{{ $detail->author?->is_author && filled($detail->author->username) ? route('authors.show', $detail->author->username) : route('authors.index') }}">作者主页</a>
        </div>@endif
        @if(data_get($theme, 'settings.content-detail.show_related', true))@include('themes.style-a-blue-gaming.partials.ranking', ['title' => '相关推荐'])@endif
    </aside>@endif
</section>
