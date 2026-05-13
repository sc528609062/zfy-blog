<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\PaymentController;
use App\Http\Middleware\EnsureBackendAccess;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/install', [InstallController::class, 'show'])->name('install.show');
Route::get('/install/status', [InstallController::class, 'status'])->name('install.status');
Route::post('/install', [InstallController::class, 'store'])->name('install.store');

Route::middleware(EnsureInstalled::class)->group(function (): void {
Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('/posts', [SiteController::class, 'channel'])->defaults('type', 'posts')->name('posts.index');
Route::get('/images', [SiteController::class, 'channel'])->defaults('type', 'images')->name('images.index');
Route::get('/files', [SiteController::class, 'channel'])->defaults('type', 'files')->name('files.index');
Route::get('/c/{category:slug}', [SiteController::class, 'category'])->name('categories.show');
Route::get('/tag/{tag:slug}', [SiteController::class, 'tag'])->name('tags.show');
Route::get('/content/{slug}', [SiteController::class, 'content'])->name('contents.show');
Route::get('/p/{slug}', [SiteController::class, 'page'])->name('pages.show');
Route::get('/search', [SiteController::class, 'generic'])->defaults('page', 'search')->name('search');
Route::get('/rank', [SiteController::class, 'generic'])->defaults('page', 'rank')->name('rank');
Route::get('/vip', [SiteController::class, 'generic'])->defaults('page', 'vip')->name('vip');
Route::get('/points-store', [SiteController::class, 'generic'])->defaults('page', 'points-store')->name('points.store');
Route::get('/authors', [SiteController::class, 'generic'])->defaults('page', 'authors')->name('authors.index');
Route::get('/author/{username}', [SiteController::class, 'generic'])->defaults('page', 'author-profile')->name('authors.show');
Route::get('/links', [SiteController::class, 'generic'])->defaults('page', 'links')->name('links');
Route::post('/buy/{slug}', [SiteController::class, 'buy'])->name('contents.buy');

Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'authenticate'])->name('login.store');
Route::get('/register', [AuthController::class, 'register'])->name('register');
Route::post('/register', [AuthController::class, 'store'])->name('register.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/user', [SiteController::class, 'generic'])->defaults('page', 'user-overview')->name('user.overview');
Route::get('/user/orders', [SiteController::class, 'generic'])->defaults('page', 'user-orders')->name('user.orders');
Route::get('/user/downloads', [SiteController::class, 'generic'])->defaults('page', 'user-downloads')->name('user.downloads');
Route::get('/user/wallet', [SiteController::class, 'generic'])->defaults('page', 'user-wallet')->name('user.wallet');
Route::get('/user/points', [SiteController::class, 'generic'])->defaults('page', 'user-points')->name('user.points');
Route::get('/user/vip', [SiteController::class, 'generic'])->defaults('page', 'user-vip')->name('user.vip');
Route::get('/user/author', [SiteController::class, 'generic'])->defaults('page', 'author-workspace')->name('user.author');

Route::post('/payments/{gateway}/notify', [PaymentController::class, 'notify'])->name('payments.notify');
Route::post('/payments/{payment}/query', [PaymentController::class, 'query'])->name('payments.query');
Route::get('/orders/{order:order_no}/status', [PaymentController::class, 'status'])->name('orders.status');

Route::prefix('admin')->name('admin.')->middleware(['auth', EnsureBackendAccess::class])->group(function () {
    Route::get('/', [AdminController::class, 'page'])->defaults('section', 'dashboard')->name('dashboard');
    Route::get('/{section}', [AdminController::class, 'page'])
        ->where('section', '[A-Za-z0-9-]+')
        ->name('section');
    Route::post('/themes/activate', [AdminController::class, 'activateTheme'])->name('themes.activate');
    Route::post('/themes/{theme}/settings', [AdminController::class, 'saveThemeSetting'])->name('themes.settings');
    Route::post('/page-builder/{layout}', [AdminController::class, 'savePageLayout'])->name('page-builder.save');
    Route::post('/plugins/{plugin}/toggle', [AdminController::class, 'togglePlugin'])->name('plugins.toggle');
    Route::post('/plugins/{plugin}/settings', [AdminController::class, 'savePluginSetting'])->name('plugins.settings');
});
});
