<?php

namespace App\Http\Middleware;

use App\Services\Install\InstallationState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstalled
{
    public function __construct(private readonly InstallationState $state) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->state->installed()) {
            return $next($request);
        }

        if ($request->routeIs('install.*')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => '系统尚未安装，请先完成安装向导。',
                'install_url' => route('install.show', [], false),
            ], 503);
        }

        return redirect()->route('install.show');
    }
}
