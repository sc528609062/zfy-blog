<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\InviteCode;
use App\Models\User;
use App\Services\SiteSettings;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login()
    {
        return view('auth.login');
    }

    public function register()
    {
        return view('auth.register');
    }

    public function authenticate(Request $request)
    {
        $data = $request->validate([
            'login' => ['nullable', 'string', 'max:160', 'required_without:email'],
            'email' => ['nullable', 'string', 'max:160', 'required_without:login'],
            'password' => ['required', 'string'],
        ]);

        $login = (string) ($data['login'] ?? $data['email'] ?? '');
        $user = User::findForLogin($login);

        if ($user && Hash::check($data['password'], $user->password)) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->intended('/user');
        }

        return back()
            ->withInput($request->only('login', 'email'))
            ->withErrors(['login' => '账号或密码不正确。']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12), 'max:128'],
            'invite_code' => [Rule::requiredIf(app(SiteSettings::class)->get('registration.invite_required', false)), 'nullable', 'string', 'max:80'],
        ]);

        $user = DB::transaction(function () use ($data) {
            if (filled($data['invite_code'] ?? null)) {
                $invite = InviteCode::where('code', $data['invite_code'])->lockForUpdate()->first();
                if (! $invite || $invite->status !== 'active' || $invite->expires_at?->isPast() || $invite->used_count >= $invite->usage_limit) {
                    throw ValidationException::withMessages(['invite_code' => '邀请码无效或已用完。']);
                }
                $invite->increment('used_count');
            }
            $base = Str::slug(Str::before($data['email'], '@')) ?: 'user';
            $username = $base;
            while (User::where('username', $username)->exists()) {
                $username = $base.'-'.Str::lower(Str::random(8));
            }
            $user = User::create([
                'name' => $data['name'],
                'username' => $username,
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);
            $user->assignRole('USER');
            $user->wallet()->create(['balance' => 0]);
            $user->pointsAccount()->create(['points' => 0]);
            zfy_after_commit('zfy_user_registered', $user);
            DB::afterCommit(fn () => event(new Registered($user)));

            return $user;
        });
        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/user');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function forgotPassword()
    {
        return view('auth.password', ['reset' => false]);
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        PasswordBroker::sendResetLink($request->only('email'));

        return back()->with('status', '如果该邮箱已注册，重置链接将发送到邮箱。');
    }

    public function resetPassword(Request $request, string $token)
    {
        return view('auth.password', ['reset' => true, 'token' => $token, 'email' => $request->query('email', '')]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate(['token' => ['required'], 'email' => ['required', 'email'], 'password' => ['required', 'confirmed', Password::min(12)]]);
        $status = PasswordBroker::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            $user->tokens()->delete();
            event(new PasswordReset($user));
        });

        return $status === PasswordBroker::PASSWORD_RESET
            ? redirect('/login')->with('status', '密码已重置，请重新登录。')
            : back()->withErrors(['email' => '重置链接无效或已过期，请重新申请。']);
    }
}
