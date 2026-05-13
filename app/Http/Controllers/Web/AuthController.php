<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

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
            Auth::login($user, true);
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
            'password' => ['required', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'username' => str($data['email'])->before('@')->slug(),
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
        $user->assignRole('USER');
        Auth::login($user);

        return redirect('/user');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
