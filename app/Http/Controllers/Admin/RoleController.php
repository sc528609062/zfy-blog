<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::with('permissions')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.edit', [
            'role'        => new Role(),
            'permissions' => Permission::all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:80|unique:roles,name',
            'permissions' => 'array',
        ]);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('status', '角色已创建');
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.edit', [
            'role'        => $role->load('permissions'),
            'permissions' => Permission::all(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'permissions' => 'array',
        ]);

        $role->syncPermissions($data['permissions'] ?? []);
        return back()->with('status', '权限已更新');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if (in_array($role->name, ['SUPER_ADMIN', 'USER'], true)) {
            return back()->withErrors(['delete' => '系统内置角色不可删除']);
        }
        $role->delete();
        return back()->with('status', '已删除');
    }
}
