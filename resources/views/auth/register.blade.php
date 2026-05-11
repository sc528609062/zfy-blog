<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>注册 zfy-blog</title>
    @vite(['resources/css/app.css'])
</head>
<body class="auth-page">
    <form method="post" action="/register" class="auth-card">
        @csrf
        <h1>注册账号</h1>
        <p>购买资源、收藏内容、申请作者、开通 VIP。</p>
        <label>昵称<input name="name" required></label>
        <label>邮箱<input type="email" name="email" required></label>
        <label>密码<input type="password" name="password" required></label>
        <button class="primary-btn">立即注册</button>
        <a href="/login">已有账号，去登录</a>
    </form>
</body>
</html>
