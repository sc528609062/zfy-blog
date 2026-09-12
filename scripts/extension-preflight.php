<?php

use App\Services\ExtensionCodeLoader;
use App\Services\ThemePackageLoader;
use Illuminate\Contracts\Console\Kernel;

if (PHP_SAPI !== 'cli') {
    exit(1);
}
putenv('ZFY_SAFE_MODE=true');
$_ENV['ZFY_SAFE_MODE'] = $_SERVER['ZFY_SAFE_MODE'] = 'true';
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$slug = $argv[1] ?? '';
if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
    exit(1);
}
$type = $argv[2] ?? 'plugin';
$slugs = $type === 'theme' ? app(ThemePackageLoader::class)->chain($slug) : [$slug];
foreach ($slugs as $slug) {
    $provider = app(ExtensionCodeLoader::class)->provider($type, $slug);
    if ($provider) {
        $provider->register();
        if (method_exists($provider, 'boot')) {
            app()->call([$provider, 'boot']);
        }
    }
}
echo "Extension startup preflight passed.\n";
