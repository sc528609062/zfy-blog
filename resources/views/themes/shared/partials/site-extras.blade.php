@if(isset($pagination) && (str_ends_with($page, '-channel') || in_array($page, ['category-page', 'tag-page', 'topic-page', 'author-profile'], true)))
    <div class="a-shell">{{ $pagination->links() }}</div>
@endif
@if(isset($widgets))
    @foreach($widgets->get('footer', collect())->merge($widgets->get(str_starts_with($page, 'user-') ? 'user-center' : 'sidebar', collect())) as $widget)
        <section class="a-shell site-widget">
            <h3>{{ $widget->title }}</h3>
            @if($definition = app(\App\Support\Zfy\ExtensionRegistry::class)->get('widget', $widget->type))
                {!! \Mews\Purifier\Facades\Purifier::clean(($definition['render'])($widget->config ?? [], ['page' => $page])) !!}
            @elseif($widget->type === 'recent-posts')
                @foreach($posts as $item)<p><a href="{{ route('contents.show', $item->slug) }}">{{ $item->title }}</a></p>@endforeach
            @elseif($widget->type === 'html')
                {!! app(\App\Services\ContentMarkdownRenderer::class)->render((string) data_get($widget->config, 'text', ''), true) !!}
            @else
                <p style="white-space:pre-wrap">{{ data_get($widget->config, 'text', '') }}</p>
            @endif
        </section>
    @endforeach
@endif
@if(isset($navigation) && $navigation->has('footer'))
    <nav class="a-shell site-footer-nav">@include('themes.shared.partials.navigation-items', ['items' => $navigation->get('footer')->items])</nav>
@endif
