<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 只允许具有 SUPER_ADMIN / ADMIN / EDITOR 角色的用户访问后台。
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('admin.login');
        }

        $allowed = ['SUPER_ADMIN', 'ADMIN', 'EDITOR'];

        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole($allowed)) {
            return $next($request);
        }

        abort(403, '需要管理员或编辑权限');
    }
}
