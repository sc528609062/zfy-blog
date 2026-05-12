@php
    $title    = $title ?? '建设中';
    $subtitle = $subtitle ?? '该模块将在后续 Sprint 完整实现';
    $sprint   = $sprint ?? null;
    $features = $features ?? [];
@endphp
<div class="card p-12 text-center">
    <div class="w-16 h-16 mx-auto bg-primary-50 text-primary-600 rounded-2xl flex items-center justify-center mb-4">
        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877"/></svg>
    </div>
    <h2 class="text-lg font-semibold text-ink-900 mb-1">{{ $title }}</h2>
    <p class="text-sm text-ink-500 mb-2">{{ $subtitle }}</p>
    @if($sprint)<span class="badge-primary mt-2 inline-block">{{ $sprint }}</span>@endif
    @if(count($features))
        <ul class="mt-6 text-left max-w-sm mx-auto text-sm text-ink-600 space-y-1.5">
            @foreach($features as $f)
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-accent-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    {{ $f }}
                </li>
            @endforeach
        </ul>
    @endif
</div>
