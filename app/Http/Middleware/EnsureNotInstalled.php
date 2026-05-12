<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 当系统已安装时，禁止访问安装器，跳回首页。
 */
class EnsureNotInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (file_exists(config('zfy.install.lock_file'))) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
