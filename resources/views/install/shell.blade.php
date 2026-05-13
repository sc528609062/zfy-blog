<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>安装 zfy-blog</title>
    @vite(['resources/js/install.js'])
</head>
<body class="zfy-install-body">
    <div id="install-app"></div>
    <script id="install-payload" type="application/json">@json($payload)</script>
</body>
</html>
