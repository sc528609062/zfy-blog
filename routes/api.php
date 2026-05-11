<?php

use App\Http\Controllers\Api\V1\PlatformController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('/home', [PlatformController::class, 'home']);
    Route::get('/contents', [PlatformController::class, 'contents']);
    Route::get('/contents/{slug}', [PlatformController::class, 'content']);
    Route::get('/taxonomy', [PlatformController::class, 'taxonomy']);
    Route::get('/authors', [PlatformController::class, 'authors']);
    Route::get('/themes', [PlatformController::class, 'themes']);
    Route::get('/plugins', [PlatformController::class, 'plugins']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [PlatformController::class, 'me']);
        Route::get('/orders', [PlatformController::class, 'orders']);
    });
});
