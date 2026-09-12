<?php

use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\UpdateWriteBarrier;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->append(UpdateWriteBarrier::class);
        $middleware->web(append: [EnsureAccountActive::class]);
        $middleware->api(append: [EnsureAccountActive::class]);
        $middleware->validateCsrfTokens(except: [
            'install',
            'install/*',
            'payments/*/notify',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
