@php
    $section = $section ?? 'dashboard';
    $payload = [
        'section' => $section,
        'csrf' => csrf_token(),
        'today' => now()->format('Y-m-d'),
        'stats' => $stats ?? [],
        'contents' => collect($contents ?? [])->map(fn ($content) => [
            'id' => $content->id,
            'title' => $content->title,
            'status' => $content->status,
            'type' => $content->type,
            'created_at' => optional($content->created_at)->format('Y-m-d H:i'),
        ])->values(),
        'orders' => collect($orders ?? [])->map(fn ($order) => [
            'id' => $order->id,
            'title' => $order->order_no,
            'status' => $order->status,
            'type' => $order->pay_channel,
            'created_at' => optional($order->created_at)->format('Y-m-d H:i'),
        ])->values(),
        'themes' => collect($themes ?? [])->map(fn ($themeItem) => [
            'id' => $themeItem->id,
            'name' => $themeItem->name,
            'slug' => $themeItem->slug,
            'version' => $themeItem->version,
            'preview' => $themeItem->preview,
            'is_active' => (bool) $themeItem->is_active,
            'settings_url' => route('admin.themes.settings', $themeItem, false),
        ])->values(),
        'plugins' => collect($plugins ?? [])->map(fn ($plugin) => [
            'id' => $plugin->id,
            'name' => $plugin->name,
            'slug' => $plugin->slug,
            'version' => $plugin->version,
            'enabled' => (bool) $plugin->enabled,
            'permissions' => $plugin->permissions ?? [],
            'toggle_url' => route('admin.plugins.toggle', $plugin, false),
            'settings_url' => route('admin.plugins.settings', $plugin, false),
        ])->values(),
        'layouts' => collect($layouts ?? [])->map(fn ($layout) => [
            'id' => $layout->id,
            'title' => $layout->title,
            'status' => $layout->status,
            'schema' => json_encode($layout->schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'save_url' => route('admin.page-builder.save', $layout, false),
        ])->values(),
        'theme_manifests' => $themeManifests ?? [],
        'plugin_manifests' => $pluginManifests ?? [],
        'routes' => [
            'theme_activate' => route('admin.themes.activate', [], false),
        ],
    ];
@endphp
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>zfy-blog 后台</title>
    @vite(['resources/js/admin.js'])
</head>
<body class="zfy-admin-body">
    <div id="admin-app"></div>
    <div hidden>
        @foreach(($themeManifests ?? []) as $slug => $manifest)
            <p>{{ $slug }} 配置：{{ implode(' / ', array_keys($manifest['settings_schema'] ?? [])) }}</p>
        @endforeach
        @foreach(($pluginManifests ?? []) as $slug => $manifest)
            <p>{{ $slug }} 事件：{{ implode(', ', $manifest['events'] ?? []) }}</p>
        @endforeach
    </div>
    <script id="admin-payload" type="application/json">@json($payload)</script>
</body>
</html>
