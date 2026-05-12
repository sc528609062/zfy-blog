<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\ChannelController;
use App\Http\Controllers\Web\ContentController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\PageController;
use App\Http\Controllers\Web\SearchController;
use App\Http\Controllers\Web\TaxonomyController;
use App\Http\Controllers\Web\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (前台)
|--------------------------------------------------------------------------
|
| 前台路由使用当前激活主题渲染。所有路由都需要 zfy.installed 中间件，
| 未安装则强制跳转 /install。
|
*/

Route::middleware('zfy.installed')->group(function () {

    // 首页与频道
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/posts', [ChannelController::class, 'posts'])->name('channel.posts');
    Route::get('/images', [ChannelController::class, 'images'])->name('channel.images');
    Route::get('/files', [ChannelController::class, 'files'])->name('channel.files');

    // 内容
    Route::get('/content/{content:slug}', [ContentController::class, 'show'])->name('content.show');
    Route::post('/content/{content:slug}/unlock', [ContentController::class, 'unlock'])->name('content.unlock');
    Route::get('/p/{content:slug}', [PageController::class, 'show'])
        ->where('content', '[a-z0-9\-]+')
        ->name('page.show');

    // 分类与标签
    Route::get('/c/{category:slug}', [TaxonomyController::class, 'category'])->name('taxonomy.category');
    Route::get('/tag/{tag:slug}', [TaxonomyController::class, 'tag'])->name('taxonomy.tag');

    // 搜索 / 排行 / 作者列表（M5+ 完整实现，先留路由）
    Route::get('/search', [SearchController::class, 'index'])->name('search');
    Route::view('/rank', 'frontend.rank')->name('rank');
    Route::view('/authors', 'frontend.authors')->name('authors');
    Route::view('/author/{username}', 'frontend.author-profile')
        ->where('username', '[A-Za-z0-9_\-]+')
        ->name('author.profile');
    Route::view('/links', 'frontend.links')->name('links');

    // 商业化前台页（Sprint 3-4 实现）
    Route::view('/vip', 'frontend.vip')->name('vip.index');
    Route::view('/points-store', 'frontend.points-store')->name('points.store');

    // 登录 / 注册 / 找回密码
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
        Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [AuthController::class, 'register'])->name('register.attempt');
        Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('password.request');
        Route::post('/forgot-password', [AuthController::class, 'forgot'])->name('password.email');
    });

    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

    // 用户中心
    Route::middleware('auth')->prefix('user')->name('user.')->group(function () {
        Route::get('/', [UserController::class, 'overview'])->name('overview');
        Route::view('/orders', 'frontend.user.orders')->name('orders');
        Route::view('/downloads', 'frontend.user.downloads')->name('downloads');
        Route::view('/wallet', 'frontend.user.wallet')->name('wallet');
        Route::view('/points', 'frontend.user.points')->name('points');
        Route::view('/vip', 'frontend.user.vip')->name('vip');
        Route::view('/author', 'frontend.user.author-workspace')->name('author');
    });
});

require __DIR__ . '/install.php';
require __DIR__ . '/admin.php';
