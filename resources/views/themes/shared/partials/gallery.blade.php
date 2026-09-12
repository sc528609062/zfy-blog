<div class="zfy-content-gallery" data-gallery>
    @foreach($items as $item)
        <figure>
            <a href="{{ $item['url'] }}" data-gallery-image data-caption="{{ $item['title'] }}"><img src="{{ $item['url'] }}" alt="{{ $item['title'] }}" loading="lazy" width="{{ $item['width'] }}" height="{{ $item['height'] }}"></a>
            <figcaption><strong>{{ $item['title'] }}</strong><p>{{ $item['caption'] }}</p>@if($item['copyright'])<small>{{ $item['copyright'] }}</small>@endif</figcaption>
            @if($item['original_download'])
                @auth<form method="post" action="{{ route('downloads.create', $content->slug) }}">@csrf<input type="hidden" name="attachment_id" value="{{ $item['id'] }}"><button>下载原图</button></form>@else<a href="{{ route('login') }}">登录下载原图</a>@endauth
            @endif
        </figure>
    @endforeach
</div>
