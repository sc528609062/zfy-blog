<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (Sprint 5 / M10)
|--------------------------------------------------------------------------
|
| 占位路由：完整 REST API 在 Sprint 5 实现，包含 /api/v1/contents、orders、
| wallet、points、downloads 等。当前仅返回版本和健康状态。
|
*/

Route::prefix('v1')->group(function () {
    Route::get('/ping', fn () => response()->json([
        'success' => true,
        'data' => [
            'version' => config('zfy.version'),
            'name'    => config('zfy.name'),
            'time'    => now()->toIso8601String(),
        ],
        'message' => 'ok',
    ]));

    Route::middleware('auth:sanctum')->get('/me', function (Request $r) {
        return response()->json([
            'success' => true,
            'data'    => $r->user(),
        ]);
    });
});
