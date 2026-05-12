@php
    $nav = [
        ['heading' => '工作台'],
        ['name' => '仪表盘',       'route' => 'admin.dashboard',       'icon' => 'home',     'perm' => null],

        ['heading' => '内容'],
        ['name' => '所有内容',     'route' => 'admin.contents.index',  'icon' => 'doc',      'perm' => 'content.view'],
        ['name' => '新建内容',     'route' => 'admin.contents.create', 'icon' => 'plus',     'perm' => 'content.create'],
        ['name' => '分类',         'route' => 'admin.categories.index','icon' => 'folder',   'perm' => 'taxonomy.manage'],
        ['name' => '标签',         'route' => 'admin.tags.index',      'icon' => 'tag',      'perm' => 'taxonomy.manage'],
        ['name' => '评论',         'route' => 'admin.comments.index',  'icon' => 'chat',     'perm' => 'comment.moderate'],
        ['name' => '媒体库',       'route' => 'admin.media.index',     'icon' => 'image',    'perm' => 'media.upload'],

        ['heading' => '商业化'],
        ['name' => '订单',         'route' => 'admin.orders.index',    'icon' => 'cart',     'perm' => 'order.view'],
        ['name' => '积分',         'route' => 'admin.points.index',    'icon' => 'star',     'perm' => 'points.adjust'],

        ['heading' => '外观'],
        ['name' => '主题',         'route' => 'admin.themes.index',    'icon' => 'brush',    'perm' => 'theme.manage'],
        ['name' => '菜单',         'route' => 'admin.menus.index',     'icon' => 'menu',     'perm' => 'menu.manage'],
        ['name' => '页面构建',     'route' => 'admin.page-builder.index','icon' => 'layout',  'perm' => 'page-builder.manage'],

        ['heading' => '用户与系统'],
        ['name' => '用户',         'route' => 'admin.users.index',     'icon' => 'users',    'perm' => 'user.view'],
        ['name' => '角色',         'route' => 'admin.roles.index',     'icon' => 'shield',   'perm' => 'role.manage'],
        ['name' => '插件',         'route' => 'admin.plugins.index',   'icon' => 'puzzle',   'perm' => 'plugin.manage'],
        ['name' => '设置',         'route' => 'admin.settings.index',  'icon' => 'cog',      'perm' => 'setting.manage'],
    ];
@endphp

<aside class="w-60 bg-ink-900 text-ink-300 flex flex-col shrink-0">
    <div class="px-5 py-4 border-b border-ink-800 flex items-center gap-2">
        <span class="w-8 h-8 bg-gradient-to-br from-primary-500 to-primary-700 rounded-lg flex items-center justify-center text-white">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M3 6h18l-2 12H5L3 6z"/></svg>
        </span>
        <div class="flex-1">
            <div class="text-white font-bold">zfy-blog</div>
            <div class="text-[10px] text-ink-400">v{{ config('zfy.version') }}</div>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto py-3 text-sm">
        @foreach($nav as $item)
            @if(isset($item['heading']))
                <div class="px-5 mt-3 mb-1 text-[10px] tracking-widest text-ink-500 uppercase">{{ $item['heading'] }}</div>
            @else
                @if(! $item['perm'] || auth()->user()?->can($item['perm']) || auth()->user()?->hasRole('SUPER_ADMIN'))
                    <a href="{{ \Illuminate\Support\Facades\Route::has($item['route']) ? route($item['route']) : '#' }}"
                       @class([
                            'flex items-center gap-2 px-5 py-2 hover:bg-ink-800 hover:text-white transition',
                            'bg-ink-800 text-white border-l-2 border-primary-500' => request()->routeIs($item['route']),
                       ])>
                        <span class="w-4 h-4 inline-block">@include('admin.partials.icon', ['name' => $item['icon']])</span>
                        {{ $item['name'] }}
                    </a>
                @endif
            @endif
        @endforeach
    </nav>

    <div class="border-t border-ink-800 p-4">
        <a href="{{ url('/') }}" target="_blank" class="text-xs text-ink-400 hover:text-white">↗ 访问前台</a>
    </div>
</aside>
