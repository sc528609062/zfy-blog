<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\SettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'zfy.installed'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware(['auth', 'zfy.admin'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // 内容
        Route::get('/contents', [ContentController::class, 'index'])->name('contents.index');
        Route::get('/contents/create', [ContentController::class, 'create'])->name('contents.create');
        Route::post('/contents', [ContentController::class, 'store'])->name('contents.store');
        Route::get('/contents/{content}/edit', [ContentController::class, 'edit'])->name('contents.edit');
        Route::put('/contents/{content}', [ContentController::class, 'update'])->name('contents.update');
        Route::delete('/contents/{content}', [ContentController::class, 'destroy'])->name('contents.destroy');
        Route::post('/contents/{content}/publish', [ContentController::class, 'publish'])->name('contents.publish');
        Route::post('/contents/{content}/unpublish', [ContentController::class, 'unpublish'])->name('contents.unpublish');
        Route::post('/contents/{content}/trash', [ContentController::class, 'trash'])->name('contents.trash');

        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::resource('tags', TagController::class)->except(['show']);

        // 用户与权限
        Route::resource('users', UserController::class);
        Route::resource('roles', RoleController::class)->except(['show']);

        // 菜单
        Route::resource('menus', MenuController::class);
        Route::post('/menus/{menu}/items', [MenuController::class, 'storeItem'])->name('menus.items.store');
        Route::put('/menus/{menu}/items/{item}', [MenuController::class, 'updateItem'])->name('menus.items.update');
        Route::delete('/menus/{menu}/items/{item}', [MenuController::class, 'destroyItem'])->name('menus.items.destroy');
        Route::post('/menus/{menu}/items/reorder', [MenuController::class, 'reorderItems'])->name('menus.items.reorder');

        // 主题
        Route::get('/themes', [ThemeController::class, 'index'])->name('themes.index');
        Route::post('/themes/{slug}/activate', [ThemeController::class, 'activate'])->name('themes.activate');
        Route::get('/themes/{slug}/preview', [ThemeController::class, 'preview'])->name('themes.preview');

        // 系统设置
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

        // 商业化（Sprint 4 实现，先留路由占位）
        Route::view('/orders', 'admin.placeholder', ['title' => '订单管理', 'milestone' => 'Sprint 4 / M8'])->name('orders.index');
        Route::view('/vip-levels', 'admin.placeholder', ['title' => 'VIP 等级', 'milestone' => 'Sprint 3 / M7'])->name('vip.index');
        Route::view('/wallets', 'admin.placeholder', ['title' => '钱包管理', 'milestone' => 'Sprint 4 / M8'])->name('wallets.index');
        Route::view('/points', 'admin.placeholder', ['title' => '积分管理', 'milestone' => 'Sprint 4 / M8'])->name('points.index');
        Route::view('/withdrawals', 'admin.placeholder', ['title' => '提现管理', 'milestone' => 'Sprint 5 / M9'])->name('withdrawals.index');
        Route::view('/plugins', 'admin.placeholder', ['title' => '插件管理', 'milestone' => 'Sprint 7 / M12'])->name('plugins.index');
        Route::view('/page-builder', 'admin.placeholder', ['title' => '页面构建器', 'milestone' => 'Sprint 6 / M11'])->name('page-builder.index');
        Route::view('/media', 'admin.placeholder', ['title' => '媒体库', 'milestone' => 'Sprint 2 / M4'])->name('media.index');
        Route::view('/comments', 'admin.placeholder', ['title' => '评论管理', 'milestone' => 'Sprint 3 / M6'])->name('comments.index');
    });
});
