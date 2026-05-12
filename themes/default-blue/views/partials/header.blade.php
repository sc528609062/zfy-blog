@php
    $primaryMenu = \App\Models\Menu::with('items')->where('location', 'primary')->first();
@endphp

<header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-ink-100">
    <div class="max-w-7xl mx-auto px-4 h-16 flex items-center gap-6">
        <a href="{{ route('home') }}" class="flex items-center gap-2 text-primary-700 font-bold text-xl shrink-0">
            <span class="w-9 h-9 bg-gradient-to-br from-primary-500 to-primary-700 rounded-xl flex items-center justify-center text-white shadow">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M3 6h18l-2 12H5L3 6zm5 0V4h8v2"/></svg>
            </span>
            <span class="hidden sm:inline">{{ $site['name'] ?? 'zfy-blog' }}</span>
        </a>

        <nav class="hidden md:flex items-center gap-1 flex-1">
            @if($primaryMenu)
                @foreach($primaryMenu->rootItems as $item)
                    @if($item->enabled)
                        <a href="{{ $item->resolveUrl() }}"
                           target="{{ $item->open_in }}"
                           class="px-3 py-2 text-sm text-ink-700 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition">
                            {{ $item->label }}
                        </a>
                    @endif
                @endforeach
            @endif
        </nav>

        <form method="GET" action="{{ route('search') }}" class="hidden lg:flex">
            <div class="relative">
                <input type="search" name="q" value="{{ request('q') }}"
                       placeholder="搜索内容、作者、标签..."
                       class="w-72 pl-10 pr-3 py-1.5 text-sm bg-ink-100/70 border-transparent rounded-full focus:bg-white focus:border-primary-300 focus:ring-primary-200 transition">
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-ink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m21 21-4.3-4.3"/></svg>
            </div>
        </form>

        <div class="flex items-center gap-2 shrink-0">
            @auth
                <a href="{{ route('user.overview') }}" class="btn-ghost text-sm flex items-center gap-2">
                    <img src="{{ auth()->user()->avatar_url }}" class="w-7 h-7 rounded-full" alt="">
                    <span class="hidden lg:inline">{{ auth()->user()->name }}</span>
                </a>
                @if(auth()->user()->isStaff())
                    <a href="{{ route('admin.dashboard') }}" class="btn-secondary text-xs">后台</a>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button class="btn-ghost text-sm">退出</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn-ghost text-sm">登录</a>
                <a href="{{ route('register') }}" class="btn-primary text-sm">注册</a>
            @endauth
        </div>
    </div>
</header>
