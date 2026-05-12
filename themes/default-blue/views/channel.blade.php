@extends('layouts.app')

@section('title', $title ?? '内容列表')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-6 flex items-end justify-between gap-4">
            <h1 class="text-2xl font-bold text-ink-900">{{ $title }}</h1>
            <div class="text-sm text-ink-500">共 {{ $contents->total() ?? $contents->count() }} 条</div>
        </div>

        @if($contents->count())
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($contents as $item)
                    @include('partials.content-card', ['item' => $item])
                @endforeach
            </div>

            @if(method_exists($contents, 'links'))
                <div class="mt-8">
                    {{ $contents->links() }}
                </div>
            @endif
        @else
            <div class="card p-12 text-center text-ink-400">
                <p>暂无内容</p>
            </div>
        @endif
    </div>
@endsection
