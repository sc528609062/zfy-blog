@extends('layouts.app')

@section('title', $title)

@section('content')
    <div class="max-w-5xl mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold text-ink-900 mb-4">搜索结果</h1>

        <form method="GET" action="{{ route('search') }}" class="flex mb-6 max-w-2xl">
            <input type="search" name="q" value="{{ $q }}" class="input flex-1 rounded-r-none" placeholder="搜索内容、作者、标签...">
            <button class="btn-primary rounded-l-none">搜索</button>
        </form>

        @if($q === '')
            <p class="text-ink-500">请输入关键字开始搜索</p>
        @elseif($contents->isEmpty())
            <div class="card p-12 text-center text-ink-400">未找到与 "<strong class="text-ink-700">{{ $q }}</strong>" 相关的内容</div>
        @else
            <div class="text-sm text-ink-500 mb-4">共找到 {{ $contents->total() }} 条结果</div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($contents as $item)
                    @include('partials.content-card', ['item' => $item])
                @endforeach
            </div>
            <div class="mt-8">{{ $contents->links() }}</div>
        @endif
    </div>
@endsection
