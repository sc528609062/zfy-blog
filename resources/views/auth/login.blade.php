<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>登录 zfy-blog</title>
    @vite(['resources/css/app.css'])
</head>
<body class="auth-page">
    <form method="post" action="/login" class="auth-card">
        @csrf
        <h1>登录 zfy-blog</h1>
        <p>进入内容商业平台后台、用户中心和作者工作台。</p>
        <label>邮箱<input type="email" name="email" value="admin@zfy-blog.test" required></label>
        <label>密码<input type="password" name="password" value="zfy-blog-123456" required></label>
        @error('email')<span class="error">{{ $message }}</span>@enderror
        <button class="primary-btn">登录</button>
        <a href="/register">注册账号</a>
    </form>
</body>
</html>
