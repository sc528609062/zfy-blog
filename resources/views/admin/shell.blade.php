@php
    $section = $section ?? 'dashboard';
    $adminTone = match ($theme['slug'] ?? 'style-a-blue-gaming') {
        'style-b-marketplace' => 'market',
        'style-c-creative' => 'creative',
        default => 'blue',
    };
    $sectionTitle = [
        'dashboard' => '仪表盘',
        'contents' => '内容管理',
        'editor' => '内容编辑器',
        'media' => '媒体库',
        'comments' => '评论审核',
        'orders' => '订单支付管理',
        'users' => '用户与角色',
        'themes' => '主题管理',
        'page-builder' => '页面构建器',
        'plugins' => '插件管理',
        'installer' => 'Web 安装器',
        'updater' => '在线升级中心',
    ][$section] ?? '后台';
@endphp
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $sectionTitle }} - zfy-blog 后台</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="a-admin-body admin-{{ $adminTone }}">
    <aside class="a-admin-sidebar">
        <a class="a-admin-brand" href="/admin"><span>Z</span><strong>zfy-blog</strong><small>游戏资源站长台</small></a>
        @foreach([
            'dashboard' => '仪表盘',
            'contents' => '游戏管理',
            'media' => '资源管理',
            'editor' => '新建文章',
            'comments' => '评论管理',
            'orders' => '订单管理',
            'users' => '用户列表',
            'themes' => '主题管理',
            'page-builder' => '页面构建器',
            'plugins' => '插件管理',
            'installer' => '安装器',
            'updater' => '升级中心',
        ] as $key => $label)
            <a href="/admin/{{ $key }}" class="{{ $section === $key ? 'active' : '' }}"><span>{{ mb_substr($label, 0, 1) }}</span>{{ $label }}</a>
        @endforeach
        <div class="a-admin-tip"><strong>创作小贴士</strong><p>Ctrl + S 保存草稿，Ctrl + Enter 发布文章。</p></div>
    </aside>

    <main class="a-admin-main">
        <header class="a-admin-topbar">
            <nav><a href="/">首页</a><a href="/files">资源</a><a href="/posts">攻略</a><a href="/rank">排行榜</a></nav>
            <form action="/search"><input name="q" placeholder="搜索资源、攻略、资讯..."><button>搜索</button></form>
            <a class="a-publish" href="/admin/editor">发布</a>
            <a class="a-vip-chip" href="/vip">VIP</a>
            <span class="a-bell">3</span>
            <a class="a-avatar" href="/user"><img src="https://api.dicebear.com/8.x/adventurer/svg?seed=admin" alt="管理员"></a>
        </header>

        <section class="a-admin-title">
            <div>
                <h1>{{ $sectionTitle }}</h1>
                <p>欢迎回来，管理员！今天是 {{ now()->format('Y-m-d') }}</p>
            </div>
            <div class="a-admin-actions"><button>刷新</button><a href="/">访问前台</a></div>
        </section>

        @if(session('status'))<div class="a-notice">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="a-error-list">{{ implode('；', $errors->all()) }}</div>@endif

        @if($section === 'dashboard')
            <section class="a-admin-stats">
                <div><span>用户总数</span><strong>24,568</strong><small>较昨日 +128</small><i></i></div>
                <div><span>资源总数</span><strong>{{ number_format($stats['contents'] ?? 0) }}</strong><small>较昨日 +32</small><i></i></div>
                <div><span>今日订单数</span><strong>{{ number_format($stats['orders'] ?? 0) }}</strong><small>较昨日 +23</small><i></i></div>
                <div><span>今日收入（元）</span><strong>6,782.50</strong><small>较昨日 +1,256.30</small><i></i></div>
                <div><span>VIP用户数</span><strong>3,685</strong><small>较昨日 +56</small><i></i></div>
            </section>
            <section class="a-admin-grid">
                <div class="a-admin-card span-2">
                    <div class="a-section-title"><h2>数据趋势</h2><nav><a class="active">用户增长</a><a>资源下载</a><a>订单金额</a></nav></div>
                    <div class="a-chart-line"><span></span><span></span><span></span><span></span><span></span><span></span></div>
                </div>
                <div class="a-admin-card">
                    <div class="a-section-title"><h2>系统概览</h2></div>
                    @foreach(['PHP版本 8.2.12','服务器时间 '.now()->format('Y-m-d H:i:s'),'运行环境 Linux / Nginx','数据库 MySQL 8.0.32','队列任务 23 个待处理'] as $sys)
                        <p class="a-system-row">{{ $sys }}</p>
                    @endforeach
                </div>
                <div class="a-admin-card">
                    <div class="a-section-title"><h2>待审核内容</h2></div>
                    @include('admin.partials.a-table', ['rows' => $contents, 'kind' => 'contents'])
                </div>
                <div class="a-admin-card">
                    <div class="a-section-title"><h2>最新订单</h2></div>
                    @include('admin.partials.a-table', ['rows' => $orders, 'kind' => 'orders'])
                </div>
                <div class="a-admin-card span-2">
                    <div class="a-section-title"><h2>系统健康状态</h2></div>
                    <div class="a-health-grid">@foreach(['CPU使用率','内存使用率','磁盘使用率','网络流量','数据库连接','PHP进程'] as $health)<div><b>{{ rand(8, 58) }}%</b><span>{{ $health }}</span><em>正常</em></div>@endforeach</div>
                </div>
                <div class="a-admin-card">
                    <div class="a-section-title"><h2>快捷操作</h2></div>
                    <div class="a-quick-grid">@foreach(['清理缓存','数据备份','更新数据','系统设置','添加资源','发布公告','查看日志','主题管理'] as $quick)<button>{{ $quick }}</button>@endforeach</div>
                </div>
            </section>
        @elseif($section === 'editor')
            <section class="a-editor-page">
                <div class="a-editor-main">
                    <div class="a-editor-head"><a href="/admin/contents">返回</a><strong>正在编辑：《幻境战纪》1.2版本更新解析</strong><span>已保存</span></div>
                    <div class="a-editor-tabs"><button class="active">所见即所得</button><button>Markdown</button><button>分栏对照</button></div>
                    <div class="a-editor-toolbar">@foreach(['段落','B','I','U','S','A','链接','引用','列表','图片','视频','表情','更多'] as $tool)<button>{{ $tool }}</button>@endforeach</div>
                    <article class="a-editor-canvas" contenteditable="true">
                        <h1>《幻境战纪》1.2版本更新解析：新角色、玩法与优化一览</h1>
                        <p class="note">本文基于官方更新公告及测试服体验整理，数据截至 {{ now()->format('Y-m-d') }}。</p>
                        <p>各位冒险者们好！本次更新带来了全新角色、玩法、副本挑战以及大量优化内容。</p>
                        <img src="https://images.unsplash.com/photo-1511512578047-dfb367046420?auto=format&fit=crop&w=1400&q=85" alt="编辑器封面">
                        <h2>一、全新角色：星域旅者</h2>
                        <ul><li>角色定位：远程输出</li><li>元素属性：风</li><li>核心技能：星风领域</li></ul>
                    </article>
                </div>
                <aside class="a-editor-side">
                    <h3>发布设置</h3>
                    <label>发布状态<select><option>草稿</option><option>公开</option></select></label>
                    <label>发布时间<input value="{{ now()->format('Y-m-d H:i:s') }}"></label>
                    <label>文章分类<input value="游戏攻略"></label>
                    <label>文章标签<input value="幻境战纪，版本更新"></label>
                    <label>封面图<div class="a-cover-picker">更换封面</div></label>
                    <label>SEO 标题<input value="幻境战纪 1.2版本更新解析"></label>
                    <button>保存草稿</button><button class="primary">发布文章</button>
                </aside>
            </section>
        @elseif($section === 'themes')
            <section class="a-admin-grid cards">
                @foreach($themes as $themeItem)
                    <article class="a-admin-card a-theme-card {{ $themeItem->is_active ? 'is-active' : '' }}">
                        <img class="a-theme-preview" src="/{{ $themeItem->preview }}" alt="{{ $themeItem->name }}">
                        <div class="a-theme-card-head">
                            <h3>{{ $themeItem->name }}</h3>
                            <span>{{ $themeItem->is_active ? '当前启用' : '可切换' }}</span>
                        </div>
                        <p>{{ $themeItem->slug }} · v{{ $themeItem->version }}</p>
                        @php($manifest = $themeManifests[$themeItem->slug] ?? null)
                        @if($manifest)
                            <p>模板：{{ implode(' / ', $manifest['templates'] ?? []) }}</p>
                            <p>配置：{{ implode(' / ', array_keys($manifest['settings_schema'] ?? [])) }}</p>
                        @endif
                        <div class="a-theme-actions">
                            <a href="/{{ $themeItem->preview }}" target="_blank" rel="noreferrer">查看预览图</a>
                            <a href="/">访问前台</a>
                        </div>
                        <form method="post" action="{{ route('admin.themes.activate') }}">
                            @csrf
                            <input type="hidden" name="slug" value="{{ $themeItem->slug }}">
                            <button class="a-primary" @disabled($themeItem->is_active)>{{ $themeItem->is_active ? '已启用' : '启用此主题' }}</button>
                        </form>
                        <form method="post" action="{{ route('admin.themes.settings', $themeItem) }}" class="a-setting-form">
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
            <section class="a-builder-page">
                <aside>@foreach(['行/列布局','内容流模块','分类模块','轮播模块','榜单模块','VIP模块','广告位','自定义HTML'] as $component)<button>{{ $component }}</button>@endforeach</aside>
                <div class="a-builder-canvas">
                    @foreach($layouts as $layout)
                        <form method="post" action="{{ route('admin.page-builder.save', $layout) }}" class="a-builder-block">
                            @csrf
                            <input name="title" value="{{ $layout->title }}">
                            <select name="status"><option value="published" @selected($layout->status === 'published')>已发布</option><option value="draft" @selected($layout->status === 'draft')>草稿</option></select>
                            <textarea name="schema" rows="12">{{ json_encode($layout->schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
                            <button class="a-primary">保存布局 JSON</button>
                        </form>
                    @endforeach
                </div>
                <aside><h3>配置面板</h3><label>响应式断点<input value="desktop/tablet/mobile"></label><label>模块间距<input value="24"></label></aside>
            </section>
        @elseif($section === 'plugins')
            <section class="a-admin-grid cards">
                @foreach($plugins as $plugin)
                    <article class="a-admin-card">
                        <h3>{{ $plugin->name }}</h3>
                        <p>{{ $plugin->slug }} · v{{ $plugin->version }}</p>
                        @php($manifest = $pluginManifests[$plugin->slug] ?? null)
                        @if($manifest)
                            <p>兼容：{{ $manifest['compatible'] ?? '^1.0' }}</p>
                            <p>事件：{{ implode(', ', $manifest['events'] ?? []) }}</p>
                        @endif
                        <p>权限：{{ implode(', ', $plugin->permissions ?? []) }}</p>
                        <form method="post" action="{{ route('admin.plugins.toggle', $plugin) }}">@csrf<button class="a-primary">{{ $plugin->enabled ? '禁用插件' : '启用插件' }}</button></form>
                        <form method="post" action="{{ route('admin.plugins.settings', $plugin) }}" class="a-setting-form">@csrf<input name="key" value="sandbox_enabled"><input name="value" placeholder="true / false"><button>保存配置</button></form>
                    </article>
                @endforeach
            </section>
        @elseif(in_array($section, ['orders', 'contents', 'comments', 'users', 'media', 'installer', 'updater'], true))
            <section class="a-admin-card">
                <div class="a-table-toolbar"><strong>{{ $sectionTitle }}</strong><span><button>新建</button><button>导出</button><button>批量操作</button></span></div>
                @include('admin.partials.a-table', ['rows' => $section === 'orders' ? $orders : $contents, 'kind' => $section === 'orders' ? 'orders' : 'contents', 'large' => true])
            </section>
        @endif
    </main>
</body>
</html>
