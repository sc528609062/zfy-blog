@if(isset($content))
    <article>
        <h1>{{ $content->title }}</h1>
        @if(filled(data_get($content->access_rules, 'password_hash')) && !app(\App\Services\ContentPasswordAccess::class)->allows($content, auth()->user()))
            <form method="post" action="/content/{{ $content->slug }}/unlock">@csrf<label>内容密码 <input type="password" name="password" required maxlength="128"></label><button>解锁内容</button>@if($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif</form>
        @endif
        @if($canAccess ?? false)
            {!! $content->rendered_html !!}
            {!! app(\App\Services\GalleryService::class)->render($content, auth()->user()) !!}
            @foreach($content->attachments()->where('role', 'download')->get() as $attachment)
                <form method="post" action="{{ route('downloads.create', $content->slug) }}">@csrf<input type="hidden" name="attachment_id" value="{{ $attachment->id }}"><button>下载 {{ $attachment->media?->name ?? '附件' }}</button></form>
            @endforeach
        @else
            <p>{{ $content->excerpt }}</p>
            @if(bccomp((string) data_get($content->pricing, 'price', '0'), '0', 2) > 0)
            @auth
                <form method="post" action="/buy/{{ $content->slug }}">@csrf<label>支付方式<select name="gateway"><option value="balance">余额</option><option value="points">积分</option></select></label><button>购买 ¥{{ data_get($content->pricing, 'price', 0) }}</button></form>
            @else
                <a href="{{ route('login') }}">登录</a>
            @endauth
            @endif
        @endif
    </article>
@elseif($page === 'vip')
    @include('themes.shared.partials.vip')
@elseif($page === 'shop')
    @include('themes.shared.partials.shop')
@elseif($page === 'cart')
    @include('themes.shared.partials.cart')
@elseif($page === 'points-store')
    @include('themes.shared.partials.points-store')
@elseif(str_starts_with($page, 'user-') || $page === 'author-workspace')
    @include('themes.shared.partials.account')
@elseif(in_array($page, ['links', 'authors', 'author-profile'], true))
    @include('themes.shared.partials.directory')
@else
    @foreach($contents as $item)
        <article><h2><a href="{{ route('contents.show', $item->slug) }}">{{ $item->title }}</a></h2><p>{{ $item->excerpt }}</p></article>
    @endforeach
    {{ $pagination->links() }}
@endif
