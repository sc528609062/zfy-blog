@php
    $cover = $item->cover_image ?: 'https://picsum.photos/seed/' . $item->id . '/600/400';
    $typeMeta = $item->type_meta;
@endphp
<article class="card-hover group">
    <a href="{{ $item->url }}" class="block aspect-[5/3] overflow-hidden bg-ink-100">
        <img src="{{ $cover }}" alt="{{ $item->title }}" loading="lazy"
             class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
    </a>
    <div class="p-4">
        <div class="flex items-center gap-1.5 text-xs mb-2">
            @if($item->category)
                <a href="{{ route('taxonomy.category', $item->category->slug) }}" class="badge-primary">{{ $item->category->name }}</a>
            @endif
            <span class="badge-muted">{{ $typeMeta['label'] ?? $item->type }}</span>
            @if($item->visibility === \App\Models\Content::VISIBILITY_VIP)
                <span class="badge-vip">VIP</span>
            @elseif($item->price > 0)
                <span class="badge bg-red-50 text-red-600">¥{{ $item->price }}</span>
            @endif
        </div>
        <h3 class="font-semibold text-ink-900 group-hover:text-primary-600 line-clamp-2 mb-2">
            <a href="{{ $item->url }}">{{ $item->title }}</a>
        </h3>
        <div class="flex items-center justify-between text-xs text-ink-500 mt-3">
            <span class="flex items-center gap-1.5">
                @if($item->author)
                    <img src="{{ $item->author->avatar_url }}" class="w-5 h-5 rounded-full" alt="">
                    {{ $item->author->name }}
                @else
                    匿名
                @endif
            </span>
            <span class="flex items-center gap-2">
                <span>👁 {{ $item->view_count }}</span>
                <span>💬 {{ $item->comment_count }}</span>
            </span>
        </div>
    </div>
</article>
