<section class="a-card-panel">
    <h1>{{ $page === 'links' ? '友情链接' : ($currentAuthor->name ?? '作者') }}</h1>
    @if($page === 'links')
        @auth<form method="post" action="{{ route('links.submit') }}">@csrf<h2>申请友链</h2><p><label>名称 <input name="name" required maxlength="120"></label></p><p><label>网址 <input name="url" type="url" required maxlength="500"></label></p><p><label>介绍 <textarea name="description" maxlength="2000"></textarea></label></p><button class="a-primary">提交申请</button></form>@endauth
        @php $linkSettings = app(\App\Services\SiteSettings::class); @endphp
        @forelse($links as $link)<p><a href="{{ $linkSettings->get('links.redirect', false) ? route('links.redirect', $link) : $link->url }}" target="{{ $linkSettings->get('links.new_window', false) ? '_blank' : $link->target }}" rel="noopener{{ $link->nofollow || $linkSettings->get('links.nofollow', false) ? ' nofollow' : '' }}">{{ $link->name }}</a> {{ $link->category?->name }}</p>@empty<p>暂无链接</p>@endforelse
    @elseif($page === 'authors')
        @forelse($authors as $author)<article><h2><a href="{{ filled($author->username) ? route('authors.show', $author->username) : route('authors.index') }}">{{ $author->name }}</a></h2><p>{{ $author->bio }}</p><p>{{ $author->contents_count }} 篇内容</p></article>@empty<p>暂无作者</p>@endforelse
        {{ $authors->links() }}
    @else
        <p>{{ $currentAuthor->bio }}</p>
        @auth @if(auth()->id() !== $currentAuthor->id)<a href="{{ route('user.messages', ['recipient_id' => $currentAuthor->id]) }}">发送私信</a>@endif @endauth
        @forelse($contents as $item)@include('themes.style-a-blue-gaming.partials.cards', ['item' => $item, 'mode' => 'list'])@empty<p>暂无已发布内容</p>@endforelse
    @endif
</section>
