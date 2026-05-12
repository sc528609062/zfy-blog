@php
    $footerMenu = \App\Models\Menu::with('items')->where('location', 'footer')->first();
@endphp
<footer class="bg-ink-900 text-ink-300 mt-16">
    <div class="max-w-7xl mx-auto px-4 py-10 grid md:grid-cols-4 gap-8">
        <div>
            <div class="text-white font-bold text-lg flex items-center gap-2">
                <span class="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M3 6h18l-2 12H5L3 6z"/></svg>
                </span>
                {{ $site['name'] ?? 'zfy-blog' }}
            </div>
            <p class="text-sm text-ink-400 mt-2">{{ $site['tagline'] ?? '' }}</p>
        </div>

        @if($footerMenu)
            <div>
                <div class="text-white font-semibold mb-3">关于</div>
                <ul class="space-y-1.5 text-sm">
                    @foreach($footerMenu->rootItems as $item)
                        @if($item->enabled)
                            <li><a href="{{ $item->resolveUrl() }}" class="hover:text-white">{{ $item->label }}</a></li>
                        @endif
                    @endforeach
                </ul>
            </div>
        @endif

        <div>
            <div class="text-white font-semibold mb-3">社区</div>
            <ul class="space-y-1.5 text-sm">
                <li><a href="{{ route('rank') }}" class="hover:text-white">排行榜</a></li>
                <li><a href="{{ route('authors') }}" class="hover:text-white">作者列表</a></li>
                <li><a href="{{ route('vip.index') }}" class="hover:text-white">VIP 会员</a></li>
                <li><a href="{{ route('points.store') }}" class="hover:text-white">积分商城</a></li>
            </ul>
        </div>

        <div>
            <div class="text-white font-semibold mb-3">运行</div>
            <ul class="space-y-1.5 text-xs text-ink-400">
                <li>zfy-blog v{{ $site['version'] ?? config('zfy.version') }}</li>
                <li>Laravel {{ app()->version() }} · PHP {{ PHP_VERSION }}</li>
                <li>主题：{{ $zfyTheme->name ?? '默认' }}</li>
            </ul>
        </div>
    </div>
    <div class="border-t border-ink-800">
        <div class="max-w-7xl mx-auto px-4 py-4 flex flex-col md:flex-row justify-between items-center gap-2 text-xs text-ink-400">
            <span>{{ $site['copyright'] ?? '' }}</span>
            @if($site['icp'] ?? false)
                <span>{{ $site['icp'] }}</span>
            @endif
        </div>
    </div>
</footer>
