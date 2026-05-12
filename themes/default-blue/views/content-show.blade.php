@extends('layouts.app')

@section('title', $content->title)
@section('description', $content->excerpt ?? '')

@section('content')
    <article class="max-w-4xl mx-auto px-4 py-8">
        <header class="mb-6">
            <div class="flex flex-wrap items-center gap-2 text-xs text-ink-500 mb-3">
                @if($content->category)
                    <a href="{{ route('taxonomy.category', $content->category->slug) }}" class="badge-primary">{{ $content->category->name }}</a>
                @endif
                @if($content->visibility !== 'public')
                    <span class="badge-vip">{{ \App\Models\Content::class::class }}</span>
                @endif
                <span>·</span>
                <span>{{ $content->published_at?->format('Y-m-d') ?? $content->created_at->format('Y-m-d') }}</span>
                <span>·</span>
                <span>👁 {{ $content->view_count }}</span>
            </div>
            <h1 class="text-3xl font-bold text-ink-900 mb-4">{{ $content->title }}</h1>
            @if($content->author)
                <div class="flex items-center gap-3 text-sm text-ink-600">
                    <img src="{{ $content->author->avatar_url }}" class="w-10 h-10 rounded-full" alt="">
                    <div>
                        <div class="font-medium text-ink-700">{{ $content->author->name }}</div>
                        <div class="text-xs text-ink-400">{{ $content->author->bio ?? '' }}</div>
                    </div>
                </div>
            @endif
        </header>

        @if($content->cover_image)
            <img src="{{ $content->cover_image }}" alt="" class="w-full rounded-2xl mb-6">
        @endif

        <div class="bg-white rounded-2xl shadow-card border border-ink-100 p-6 md:p-10">
            @if($decision->allowed)
                <div class="prose prose-blue max-w-none">
                    {!! $content->rendered_html ?: '<p class="text-ink-400">该内容暂无正文，请通过后台编辑器添加。</p>' !!}
                </div>

                {{-- 资源下载 --}}
                @if($content->downloads->count())
                    <div class="mt-8 border-t border-ink-100 pt-6">
                        <h3 class="font-semibold text-ink-900 mb-3">资源下载</h3>
                        <ul class="space-y-2">
                            @foreach($content->downloads as $d)
                                @if($d->enabled)
                                    <li class="flex items-center justify-between gap-4 bg-ink-50/50 px-4 py-3 rounded-lg">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="badge-primary">{{ $d->platform }}</span>
                                            <span class="font-medium text-ink-700 truncate">{{ $d->label }}</span>
                                            @if($d->size_human)<span class="text-xs text-ink-400">{{ $d->size_human }}</span>@endif
                                        </div>
                                        <a href="{{ $d->url }}" target="_blank" rel="nofollow" class="btn-primary text-xs">下载</a>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- 图集 --}}
                @if($content->images->count())
                    <div class="mt-8 border-t border-ink-100 pt-6">
                        <h3 class="font-semibold text-ink-900 mb-3">图集</h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            @foreach($content->images as $img)
                                <a href="{{ $img->url }}" target="_blank" class="block aspect-square overflow-hidden rounded-lg bg-ink-100">
                                    <img src="{{ $img->url }}" alt="{{ $img->alt }}" loading="lazy" class="w-full h-full object-cover hover:scale-105 transition">
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                @include('partials.content-lock', ['decision' => $decision, 'content' => $content])
            @endif

            @if($content->tags->count())
                <div class="mt-8 border-t border-ink-100 pt-6">
                    <div class="text-xs text-ink-500 mb-2">标签</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($content->tags as $tag)
                            <a href="{{ route('taxonomy.tag', $tag->slug) }}" class="badge-muted hover:bg-primary-50 hover:text-primary-600">#{{ $tag->name }}</a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </article>
@endsection
