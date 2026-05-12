<header class="bg-white border-b border-ink-100 px-6 h-14 flex items-center gap-4">
    <h1 class="text-base font-semibold text-ink-900 flex-1">@yield('page_title', '后台')</h1>

    <a href="{{ url('/') }}" target="_blank" class="text-sm text-ink-500 hover:text-primary-600 hidden sm:inline">↗ 前台</a>

    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
        @auth
            <button @click="open = !open" class="flex items-center gap-2 hover:bg-ink-50 rounded-lg px-2 py-1.5">
                <img src="{{ auth()->user()->avatar_url }}" class="w-7 h-7 rounded-full" alt="">
                <span class="text-sm text-ink-700 hidden sm:inline">{{ auth()->user()->name }}</span>
            </button>
            <div x-show="open" x-cloak class="absolute right-0 top-full mt-1 bg-white rounded-lg shadow-lg border border-ink-100 w-44 py-1 z-30">
                <a href="{{ route('user.overview') }}" class="block px-4 py-2 text-sm text-ink-700 hover:bg-ink-50">个人中心</a>
                <a href="{{ route('admin.settings.index') }}" class="block px-4 py-2 text-sm text-ink-700 hover:bg-ink-50">系统设置</a>
                <div class="border-t my-1 border-ink-100"></div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">退出登录</button>
                </form>
            </div>
        @endauth
    </div>
</header>
