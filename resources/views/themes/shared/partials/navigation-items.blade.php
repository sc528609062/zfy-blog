@foreach($items as $item)
    @if($item->children->isNotEmpty())
        <details class="zfy-nav-group"><summary>{{ $item->title }}</summary><div><a href="{{ $item->url }}" target="{{ data_get($item->meta, 'target', '_self') }}" rel="noopener">{{ $item->title }}</a>@include('themes.shared.partials.navigation-items', ['items' => $item->children])</div></details>
    @else
        <a href="{{ $item->url }}" target="{{ data_get($item->meta, 'target', '_self') }}" rel="noopener" class="{{ request()->url() === url($item->url) ? 'active' : '' }}">{{ $item->title }}</a>
    @endif
@endforeach
