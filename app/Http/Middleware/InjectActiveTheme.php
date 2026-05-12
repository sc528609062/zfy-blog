<?php

namespace App\Http\Middleware;

use App\Domain\Theme\ThemeManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 将当前激活主题的视图路径注入到 view finder，让 view('home') 优先
 * 从 themes/{active}/views 解析。后台 Blade 不走主题，依然来自
 * resources/views/admin。
 */
class InjectActiveTheme
{
    public function __construct(protected ThemeManager $themes)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $this->themes->boot();

        return $next($request);
    }
}
