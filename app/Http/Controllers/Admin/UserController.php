<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->with('roles')->latest();
        if ($q = $request->input('q')) {
            $query->where(function ($qb) use ($q) {
                $qb->where('name', 'like', "%{$q}%")
                   ->orWhere('username', 'like', "%{$q}%")
                   ->orWhere('email', 'like', "%{$q}%");
            });
        }
        return view('admin.users.index', [
            'users' => $query->paginate(20)->withQueryString(),
            'roles' => Role::all(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.edit', [
            'user'  => new User(),
            'roles' => Role::all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:80',
            'username' => 'required|string|max:64|alpha_dash|unique:users,username',
            'email'    => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:8|max:60',
            'status'   => 'required|in:active,banned,pending',
            'role'     => 'nullable|exists:roles,name',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'username' => $data['username'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'status'   => $data['status'],
        ]);

        if (! empty($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return redirect()->route('admin.users.index')->with('status', '用户已创建');
    }

    public function show(User $user): View
    {
        return view('admin.users.show', ['user' => $user->load('roles')]);
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user'  => $user->load('roles'),
            'roles' => Role::all(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:80',
            'username' => 'required|string|max:64|alpha_dash|unique:users,username,' . $user->id,
            'email'    => 'required|email|max:150|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|max:60',
            'status'   => 'required|in:active,banned,pending',
            'role'     => 'nullable|exists:roles,name',
        ]);

        $user->name     = $data['name'];
        $user->username = $data['username'];
        $user->email    = $data['email'];
        $user->status   = $data['status'];
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        if (array_key_exists('role', $data)) {
            $user->syncRoles($data['role'] ? [$data['role']] : []);
        }

        return back()->with('status', '已保存');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->hasRole('SUPER_ADMIN')) {
            return back()->withErrors(['delete' => '不能删除超级管理员']);
        }
        $user->delete();
        return back()->with('status', '已删除');
    }
}
