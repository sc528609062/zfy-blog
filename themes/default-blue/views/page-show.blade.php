@extends('layouts.app')

@section('title', $content->title)

@section('content')
    <article class="max-w-3xl mx-auto px-4 py-10">
        <h1 class="text-3xl font-bold text-ink-900 mb-6">{{ $content->title }}</h1>
        <div class="bg-white rounded-2xl shadow-card border border-ink-100 p-6 md:p-10 prose prose-blue max-w-none">
            {!! $content->rendered_html ?: '<p class="text-ink-400">这是一个独立页面，请通过后台编辑器添加正文。</p>' !!}
        </div>
    </article>
@endsection
