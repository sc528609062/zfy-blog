@php
    $title    = $title    ?? '建设中';
    $subtitle = $subtitle ?? '该模块将在后续 Sprint 完整实现，敬请期待';
    $sprint   = $sprint   ?? null;
    $features = $features ?? [];
@endphp
<div class="max-w-3xl mx-auto px-4 py-16 text-center">
    <div class="w-20 h-20 mx-auto bg-primary-100 rounded-3xl flex items-center justify-center mb-6">
        <svg class="w-10 h-10 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z"/>
        </svg>
    </div>
    <h1 class="text-2xl font-bold text-ink-900 mb-2">{{ $title }}</h1>
    <p class="text-ink-500 mb-2">{{ $subtitle }}</p>
    @if($sprint)
        <span class="badge-primary inline-block mb-6">规划：{{ $sprint }}</span>
    @endif
    @if(count($features))
        <ul class="text-left max-w-md mx-auto space-y-2 text-sm text-ink-600 mt-8">
            @foreach($features as $feat)
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-accent-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    {{ $feat }}
                </li>
            @endforeach
        </ul>
    @endif

    <div class="mt-10">
        <a href="{{ route('home') }}" class="btn-secondary text-sm">返回首页</a>
    </div>
</div>
