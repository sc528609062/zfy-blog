<?php

use App\Http\Controllers\Api\V1\PlatformController;
use App\Http\Middleware\EnsureInstalled;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->middleware([EnsureInstalled::class, 'throttle:api'])->group(function () {
    Route::post('/auth/token', [PlatformController::class, 'token']);
    Route::get('/home', [PlatformController::class, 'home']);
    Route::get('/contents', [PlatformController::class, 'contents']);
    Route::get('/contents/{slug}', [PlatformController::class, 'content']);
    Route::get('/categories', [PlatformController::class, 'categories']);
    Route::get('/tags', [PlatformController::class, 'tags']);
    Route::get('/taxonomy', [PlatformController::class, 'taxonomy']);
    Route::get('/media', [PlatformController::class, 'media']);
    Route::get('/comments', [PlatformController::class, 'comments']);
    Route::get('/authors', [PlatformController::class, 'authors']);
    Route::get('/vip', [PlatformController::class, 'vip']);
    Route::get('/search', [PlatformController::class, 'search']);
    Route::get('/themes', [PlatformController::class, 'themes']);
    Route::get('/plugins', [PlatformController::class, 'plugins']);
    Route::get('/page-builder/{scope?}', [PlatformController::class, 'pageBuilder']);
    Route::get('/system/health', [PlatformController::class, 'health']);
    Route::get('/system/upgrade', [PlatformController::class, 'upgrade']);
    Route::get('/netease/playlist/{id}', [PlatformController::class, 'neteasePlaylist'])->whereNumber('id');
    Route::get('/netease/song/{id}', [PlatformController::class, 'neteaseSong'])->whereNumber('id');
    Route::get('/netease/song/{id}/stream', [PlatformController::class, 'neteaseSongStream'])->whereNumber('id');
    Route::get('/netease/song/{id}/lyric', [PlatformController::class, 'neteaseSongLyric'])->whereNumber('id');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [PlatformController::class, 'me']);
        Route::get('/orders', [PlatformController::class, 'orders']);
        Route::get('/orders/{order:order_no}', [PlatformController::class, 'order']);
        Route::post('/contents/{content:slug}/orders', [PlatformController::class, 'createContentOrder']);
        Route::post('/vip/{vipLevel:slug}/orders', [PlatformController::class, 'createVipOrder']);
        Route::post('/orders/{order:order_no}/pay/balance', [PlatformController::class, 'payOrderWithBalance']);
        Route::post('/orders/{order:order_no}/pay/points', [PlatformController::class, 'payOrderWithPoints']);
        Route::post('/contents/{content:slug}/comments', [PlatformController::class, 'storeComment']);
        Route::post('/contents/{content:slug}/downloads', [PlatformController::class, 'download']);
        Route::get('/wallet', [PlatformController::class, 'wallet']);
        Route::get('/points', [PlatformController::class, 'points']);
        Route::get('/downloads', [PlatformController::class, 'downloads']);
        Route::get('/notifications', [PlatformController::class, 'notifications']);
    });
});
