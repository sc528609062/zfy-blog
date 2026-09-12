<!doctype html>
<html lang="zh-CN">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $reset ? '重置密码' : '找回密码' }}</title>@vite(['resources/css/app.css'])</head>
<body class="a-auth-body">
    <main class="a-auth-shell" style="display:block;max-width:560px;margin:60px auto">
        <form class="a-auth-form" method="post" action="{{ $reset ? route('password.update') : route('password.email') }}">
            @csrf
            <h1>{{ $reset ? '重置密码' : '找回密码' }}</h1>
            @if(session('status'))<p role="status">{{ session('status') }}</p>@endif
            @if($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
            <label>邮箱<input type="email" name="email" value="{{ old('email', $email ?? '') }}" required autocomplete="email"></label>
            @if($reset)
                <input type="hidden" name="token" value="{{ $token }}">
                <label>新密码<input type="password" name="password" minlength="12" required autocomplete="new-password"></label>
                <label>确认密码<input type="password" name="password_confirmation" minlength="12" required autocomplete="new-password"></label>
            @endif
            <button class="a-primary wide">{{ $reset ? '重置密码' : '发送重置链接' }}</button>
            <a href="/login">返回登录</a>
        </form>
    </main>
</body>
</html>
