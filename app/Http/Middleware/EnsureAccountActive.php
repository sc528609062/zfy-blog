<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAccountActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user() ?? $request->user('sanctum');
        if ($user?->is_banned && ($request->is('admin', 'admin/*') || ! $request->isMethodSafe())) {
            $appeal = $request->routeIs('user.requests', 'api.v1.requests') && $request->input('type') === 'appeal';
            abort_unless($appeal || $request->routeIs('logout'), 403, '账号已被停用，请到作者中心提交申诉。');
        }

        return $next($request);
    }
}
