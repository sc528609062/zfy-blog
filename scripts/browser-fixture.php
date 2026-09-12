<?php

use App\Models\Content;
use App\Models\Media;
use App\Models\User;
use Database\Seeders\CmsDemoSeeder;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

$root = dirname(__DIR__);
$database = $root.'/storage/framework/testing/browser.sqlite';
if (getenv('APP_ENV') !== 'testing' || getenv('DB_CONNECTION') !== 'sqlite' || str_replace('\\', '/', getenv('DB_DATABASE') ?: '') !== str_replace('\\', '/', $database)) {
    throw new RuntimeException('Browser fixture requires its dedicated SQLite database.');
}
if (! is_dir(dirname($database))) {
    mkdir(dirname($database), 0700, true);
}
if (! is_file($database)) {
    touch($database);
}
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
Artisan::call('migrate', ['--force' => true]);
Artisan::call('db:seed', ['--class' => CoreInstallSeeder::class, '--force' => true]);
Artisan::call('db:seed', ['--class' => CmsDemoSeeder::class, '--force' => true]);
$buyer = User::firstOrCreate(['email' => 'buyer@browser.test'], ['name' => 'Browser Buyer', 'username' => 'browser-buyer', 'password' => Hash::make('browser-test-123456'), 'email_verified_at' => now()]);
$buyer->assignRole('USER');
$buyer->wallet()->firstOrCreate([], ['balance' => 500]);
$content = Content::firstOrCreate(['slug' => 'browser-resource'], ['author_id' => User::where('username', 'admin')->value('id'), 'title' => 'Browser Resource', 'type' => 'files', 'status' => 'published', 'published_at' => now(), 'pricing' => ['price' => '12.00'], 'markdown_cache' => 'Purchased test document', 'excerpt' => 'Browser acceptance fixture']);
Storage::disk('local')->put('downloads/browser-fixture.txt', 'Private browser fixture download');
$media = Media::firstOrCreate(['path' => 'downloads/browser-fixture.txt', 'disk' => 'local'], ['user_id' => $buyer->id, 'type' => 'file', 'name' => 'browser-fixture.txt', 'mime' => 'text/plain', 'size' => 32, 'metadata' => ['private' => true]]);
$content->attachments()->firstOrCreate(['media_id' => $media->id], ['role' => 'download']);
echo "Isolated browser fixture ready.\n";
