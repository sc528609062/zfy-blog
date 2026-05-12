<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 当系统未安装时，强制跳转到 /install。
 *
 * 是否已安装 = storage/app/installed.lock 文件存在。
 * 例外：health 路由 /up、安装器自身路由、静态资源。
 */
class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isInstalled() && ! $this->shouldBypass($request)) {
            return redirect()->route('install.index');
        }

        return $next($request);
    }

    protected function isInstalled(): bool
    {
        return file_exists(config('zfy.install.lock_file'));
    }

    protected function shouldBypass(Request $request): bool
    {
        $path = '/' . ltrim($request->path(), '/');

        return $request->is('install*')
            || $request->is('up')
            || str_starts_with($path, '/build/')
            || str_starts_with($path, '/storage/')
            || str_starts_with($path, '/themes/');
    }
}
