<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>zfy-blog 后台</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-body">
    <aside class="admin-sidebar">
        <strong>zfy-blog</strong>
        @foreach([
            'dashboard' => '仪表盘',
            'contents' => '内容管理',
            'editor' => '三模式编辑器',
            'media' => '媒体库',
            'comments' => '评论审核',
            'orders' => '订单支付',
            'users' => '用户角色',
            'themes' => '主题管理',
            'page-builder' => '页面构建器',
            'plugins' => '插件管理',
            'installer' => '安装器',
            'updater' => '升级中心',
        ] as $key => $label)
            <a class="{{ $section === $key ? 'active' : '' }}" href="/admin/{{ $key }}">{{ $label }}</a>
        @endforeach
    </aside>
    <main class="admin-main">
        <header class="admin-top">
            <div>
                <h1>{{ [
                    'dashboard' => '后台仪表盘',
                    'contents' => '内容列表',
                    'editor' => 'TipTap / ProseMirror 三模式编辑器',
                    'media' => '媒体库',
                    'comments' => '评论审核',
                    'orders' => '订单支付管理',
                    'users' => '用户与角色',
                    'themes' => '主题管理',
                    'page-builder' => '页面构建器',
                    'plugins' => '插件管理',
                    'installer' => 'Web 安装器',
                    'updater' => '在线升级中心',
                ][$section] ?? '后台' }}</h1>
                <p>当前主题：{{ $theme['name'] ?? 'Default' }}</p>
            </div>
            <a class="primary-btn" href="/">访问前台</a>
        </header>

        @if(session('status'))
            <div class="notice">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="error-list">{{ implode('；', $errors->all()) }}</div>
        @endif

        @if($section === 'themes')
            <section class="admin-grid">
                @foreach($themes as $themeItem)
                    <article class="admin-card theme-admin-card">
                        <img src="/{{ $themeItem->preview }}" alt="">
                        <h3>{{ $themeItem->name }}</h3>
                        <p>{{ $themeItem->slug }} · v{{ $themeItem->version }}</p>
                        @php($manifest = $themeManifests[$themeItem->slug] ?? null)
                        @if($manifest)
                            <p>模板：{{ implode(' / ', $manifest['templates'] ?? []) }}</p>
                            <p>配置：{{ implode(' / ', array_keys($manifest['settings_schema'] ?? [])) }}</p>
                        @endif
                        <form method="post" action="{{ route('admin.themes.activate') }}">
                            @csrf
                            <input type="hidden" name="slug" value="{{ $themeItem->slug }}">
                            <button class="primary-btn">{{ $themeItem->is_active ? '当前启用' : '启用主题' }}</button>
                        </form>
                        <form method="post" action="{{ route('admin.themes.settings', $themeItem) }}" class="setting-form">
                            @csrf
                            <input name="scope" value="global">
                            <input name="key" value="primary_color">
                            <input name="value" placeholder="主色，例如 #1684ff">
                            <button>保存配置</button>
                        </form>
                    </article>
                @endforeach
            </section>
        @elseif($section === 'page-builder')
            <section class="builder-admin">
                <aside>
                    <h3>组件库</h3>
                    @foreach(['行/列布局', '内容流模块', '分类模块', '轮播模块', '榜单模块', '作者模块', 'VIP模块', '广告位', '自定义HTML', '主题组件'] as $component)
                        <button>{{ $component }}</button>
                    @endforeach
                </aside>
                <div class="builder-canvas">
                    @foreach($layouts as $layout)
                        <form method="post" action="{{ route('admin.page-builder.save', $layout) }}" class="builder-block">
                            @csrf
                            <input name="title" value="{{ $layout->title }}">
                            <select name="status">
                                <option value="published" @selected($layout->status === 'published')>已发布</option>
                                <option value="draft" @selected($layout->status === 'draft')>草稿</option>
                            </select>
                            <textarea name="schema" rows="12">{{ json_encode($layout->schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
                            <button class="primary-btn">保存布局 JSON</button>
                        </form>
                    @endforeach
                </div>
                <aside>
                    <h3>配置面板</h3>
                    <label>响应式断点<input value="desktop/tablet/mobile"></label>
                    <label>模块间距<input value="24"></label>
                    <label>主题组件<input value="{{ $theme['slug'] }}"></label>
                </aside>
            </section>
        @elseif($section === 'plugins')
            <section class="admin-grid">
                @foreach($plugins as $plugin)
                    <article class="admin-card">
                        <h3>{{ $plugin->name }}</h3>
                        <p>{{ $plugin->slug }} · v{{ $plugin->version }}</p>
                        @php($manifest = $pluginManifests[$plugin->slug] ?? null)
                        @if($manifest)
                            <p>兼容：{{ $manifest['compatible'] ?? '^1.0' }}</p>
                            <p>事件：{{ implode(', ', $manifest['events'] ?? []) }}</p>
                        @endif
                        <p>权限：{{ implode(', ', $plugin->permissions ?? []) }}</p>
                        <form method="post" action="{{ route('admin.plugins.toggle', $plugin) }}">
                            @csrf
                            <button class="primary-btn">{{ $plugin->enabled ? '禁用' : '启用' }}</button>
                        </form>
                        <form method="post" action="{{ route('admin.plugins.settings', $plugin) }}" class="setting-form">
                            @csrf
                            <input name="key" value="sandbox_enabled">
                            <input name="value" placeholder="true / false">
                            <button>保存配置</button>
                        </form>
                    </article>
                @endforeach
            </section>
        @elseif(in_array($section, ['orders', 'contents', 'comments', 'users', 'media']))
            <section class="admin-card">
                <div class="table-toolbar"><strong>筛选与批量操作</strong><button>新建</button><button>导出</button></div>
                <table class="admin-table">
                    <thead><tr><th>ID</th><th>标题/对象</th><th>状态</th><th>类型</th><th>时间</th><th>操作</th></tr></thead>
                    <tbody>
                    @foreach(($section === 'orders' ? $orders : $contents) as $row)
                        <tr>
                            <td>{{ $row->id }}</td>
                            <td>{{ $row->title ?? $row->order_no }}</td>
                            <td><span class="badge">{{ $row->status }}</span></td>
                            <td>{{ $row->type }}</td>
                            <td>{{ $row->created_at?->format('Y-m-d') }}</td>
                            <td><a>编辑</a> <a>审核</a> <a>日志</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </section>
        @elseif($section === 'editor')
            <section class="editor-shell">
                <aside><button>块编辑器</button><button>富文本工具栏</button><button>Markdown 源码</button></aside>
                <article contenteditable="true">
                    <h1>请输入内容标题</h1>
                    <p>支持标题、列表、表格、代码、图片、图集、视频、音乐、Bilibili、提示、进度条、卡片、标签栏、时间轴、按钮、隐藏内容、下载、网盘下载等特殊内容块。</p>
                </article>
                <aside><h3>发布设置</h3><label>SEO标题<input></label><label>付费价格<input value="69"></label><label>VIP价格<input value="49"></label></aside>
            </section>
        @else
            <section class="stat-grid">
                <div><span>内容</span><strong>{{ $stats['contents'] }}</strong></div>
                <div><span>订单</span><strong>{{ $stats['orders'] }}</strong></div>
                <div><span>用户</span><strong>{{ $stats['users'] }}</strong></div>
                <div><span>主题</span><strong>{{ $stats['themes'] }}</strong></div>
            </section>
            <section class="admin-grid">
                <div class="admin-card"><h3>待审核内容</h3><p>投稿、评论、举报统一审核。</p></div>
                <div class="admin-card"><h3>支付网关</h3><p>支付宝、微信、虎皮椒、易支付已预留配置和回调入口。</p></div>
                <div class="admin-card"><h3>系统健康</h3><p>MySQL、Redis、队列、计划任务、升级中心。</p></div>
            </section>
        @endif
    </main>
</body>
</html>
