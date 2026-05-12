<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('admin.auth.login');
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

        try {
            LoginLog::create([
                'user_id'      => Auth::id(),
                'username'     => $data['login'],
                'ip'           => $request->ip(),
                'ua'           => substr($request->userAgent() ?? '', 0, 500),
                'result'       => $ok ? 'success' : 'failed',
                'reason'       => $ok ? null : '后台登录失败',
                'attempted_at' => now(),
            ]);
        } catch (\Throwable) {}

        if (! $ok) {
            throw ValidationException::withMessages(['login' => '账号或密码错误']);
        }

        $user = $request->user();
        if (! $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN', 'EDITOR'])) {
            Auth::logout();
            throw ValidationException::withMessages(['login' => '该账号无管理后台访问权限']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
