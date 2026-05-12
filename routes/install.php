<?php

use App\Http\Controllers\Install\InstallController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Install Routes
|--------------------------------------------------------------------------
|
| 安装器路由。中间件 EnsureNotInstalled 保证只有未安装时才能访问。
| 5 步流程：环境检查 → 数据库 → Redis → 站点信息 → 创建管理员 → 完成。
|
*/

Route::middleware(['web', 'zfy.not-installed'])->prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'index'])->name('index');

    Route::get('/environment', [InstallController::class, 'environment'])->name('environment');

    Route::get('/database', [InstallController::class, 'database'])->name('database');
    Route::post('/database', [InstallController::class, 'storeDatabase'])->name('database.store');

    Route::get('/redis', [InstallController::class, 'redis'])->name('redis');
    Route::post('/redis', [InstallController::class, 'storeRedis'])->name('redis.store');

    Route::get('/site', [InstallController::class, 'site'])->name('site');
    Route::post('/site', [InstallController::class, 'storeSite'])->name('site.store');

    Route::get('/admin', [InstallController::class, 'admin'])->name('admin');
    Route::post('/admin', [InstallController::class, 'storeAdmin'])->name('admin.store');

    // finalize 用 GET：由 admin 表单提交后通过 redirect 触发，执行迁移/seed/建管理员/写 lock。
    Route::get('/finalize', [InstallController::class, 'finalize'])->name('finalize');
    Route::get('/complete', [InstallController::class, 'complete'])->name('complete');
});
