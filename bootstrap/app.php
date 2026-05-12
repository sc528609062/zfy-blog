<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\EnsureNotInstalled;
use App\Http\Middleware\InjectActiveTheme;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: env('ZFY_API_PREFIX', 'api/v1'),
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'zfy.installed'     => EnsureInstalled::class,
            'zfy.not-installed' => EnsureNotInstalled::class,
            'zfy.admin'         => EnsureAdmin::class,
            'zfy.theme'         => InjectActiveTheme::class,
        ]);

        $middleware->web(append: [
            InjectActiveTheme::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
