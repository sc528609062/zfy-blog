<?php

use App\Models\Comment;
use App\Models\Content;
use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\CmsDemoSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

if (PHP_SAPI !== 'cli') {
    exit(1);
}
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$tables = ['users', 'wallets', 'wallet_transactions', 'points_accounts', 'points_transactions', 'orders', 'payments', 'contents', 'products', 'product_variants', 'comments', 'menus', 'menu_items'];
$before = [];
foreach ($tables as $table) {
    $before[$table] = DB::table($table)->orderBy('id')->get()->keyBy('id')->toArray();
}
Artisan::call('db:seed', ['--class' => CmsDemoSeeder::class, '--force' => true]);
foreach ($before as $table => $rows) {
    $after = DB::table($table)->whereIn('id', array_keys($rows))->orderBy('id')->get()->keyBy('id')->toArray();
    if (json_encode($rows) !== json_encode($after)) {
        throw new RuntimeException('Existing rows changed: '.$table);
    }
}
echo 'Demo seeder completed; existing rows and financial balances preserved.'.PHP_EOL;
echo json_encode(['contents' => Content::count(), 'products' => Product::count(), 'variants' => ProductVariant::count(), 'comments' => Comment::count()], JSON_THROW_ON_ERROR).PHP_EOL;
