<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

/**
 * 前台用户认证。
 *
 * 注意：后台登录在 App\Http\Controllers\Admin\AuthController，二者共享 User 但路由分开。
 * 注册成功默认分配 USER 角色。
 */
class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
            'remember' => 'sometimes|boolean',
        ]);

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $ok = Auth::attempt(
            [$field => $data['login'], 'password' => $data['password']],
            (bool) ($data['remember'] ?? false)
        );

        $this->logLogin($request, $data['login'], $ok ? 'success' : 'failed');

        if (! $ok) {
            throw ValidationException::withMessages(['login' => '账号或密码错误']);
        }

        $request->user()->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => 'required|string|min:3|max:64|alpha_dash|unique:users,username',
            'email'    => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:8|max:60|confirmed',
        ]);

        $user = User::create([
            'name'     => $data['username'],
            'username' => $data['username'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'status'   => 'active',
        ]);

        // 兜底：USER 角色不存在时不致命
        if (Role::where('name', 'USER')->exists()) {
            $user->assignRole('USER');
        }

        Auth::login($user);

        return redirect()->route('home')->with('status', '欢迎加入 zfy-blog 🎉');
    }

    public function showForgot(): View
    {
        return view('auth.forgot');
    }

    public function forgot(Request $request): RedirectResponse
    {
        // Sprint 2 完整实现邮件流程；此处先占位
        return back()->with('status', '密码找回流程将在 Sprint 2 完整实现，请联系管理员重置。');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }

    protected function logLogin(Request $request, ?string $username, string $result, ?string $reason = null): void
    {
        try {
            LoginLog::create([
                'user_id'      => Auth::id(),
                'username'     => $username,
                'ip'           => $request->ip(),
                'ua'           => substr($request->userAgent() ?? '', 0, 500),
                'result'       => $result,
                'reason'       => $reason,
                'attempted_at' => now(),
            ]);
        } catch (\Throwable) {
            // 日志写入失败不影响登录
        }
    }
}
