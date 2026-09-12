<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureTokenAbility
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user('sanctum');
        if (! $user || ! $user->currentAccessToken()) {
            return $next($request);
        }
        $appeal = $request->routeIs('api.v1.requests') && $request->input('type') === 'appeal';
        abort_if($user->is_banned && ! $appeal, 403, '账号已停用。');
        if ($request->is('api/v1/extensions/*')) {
            return $next($request);
        }
        if ($request->is('api/v1/auth/token')) {
            return $next($request);
        }
        $ability = $request->routeIs('api.v1.downloads.file') ? 'download' : ($request->isMethodSafe() ? 'read' : match (true) {
            $request->is('api/v1/*/orders', 'api/v1/orders/*', 'api/v1/cart*', 'api/v1/addresses*', 'api/v1/wallet/*', 'api/v1/points-store/*', 'api/v1/refunds/*') => 'orders',
            $request->is('api/v1/contents/*/downloads') => 'download',
            $request->is('api/v1/contents/*/comments', 'api/v1/contents/*/reaction', 'api/v1/authors/*/follow') => 'comment',
            default => 'profile',
        });
        abort_unless($user->tokenCan($ability), 403, '令牌缺少所需能力：'.$ability);

        return $next($request);
    }
}
