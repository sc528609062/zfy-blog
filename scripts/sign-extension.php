<?php

use App\Services\PackageManifestService;
use App\Services\Updates\ReleaseVerifier;
use Illuminate\Contracts\Console\Kernel;

if (PHP_SAPI !== 'cli') {
    exit(1);
}
$_ENV['ZFY_SAFE_MODE'] = $_SERVER['ZFY_SAFE_MODE'] = 'true';
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
putenv('ZFY_SAFE_MODE=true');
$app->make(Kernel::class)->bootstrap();
[$script, $type, $source, $commit, $output] = array_pad($argv, 5, '');
$keyPath = getenv('ZFY_RELEASE_PRIVATE_KEY_FILE');
if (! in_array($type, ['plugin', 'theme'], true) || ! is_file($source) || ! preg_match('/^[a-f0-9]{40}$/', $commit) || ! $keyPath || ! is_file($keyPath) || is_dir($output)) {
    throw new RuntimeException('Usage: sign-extension.php plugin|theme PACKAGE.zip COMMIT NEW_OUTPUT_DIRECTORY; set ZFY_RELEASE_PRIVATE_KEY_FILE.');
}
$archive = new PharData($source);
$prefix = 'phar://'.str_replace('\\', '/', realpath($source)).'/';
$files = [];
$package = null;
foreach (new RecursiveIteratorIterator($archive) as $file) {
    $path = substr(str_replace('\\', '/', $file->getPathname()), strlen($prefix));
    app(ReleaseVerifier::class)->path($path);
    if ($file->isLink()) {
        throw new RuntimeException('Symlinks are forbidden.');
    }
    $files[$path] = hash_file('sha256', $file->getPathname());
    if (preg_match('~^[a-z0-9-]+/'.$type.'\.json$~', $path)) {
        if ($package) {
            throw new RuntimeException('Duplicate package manifests.');
        }
        $package = json_decode(file_get_contents($file->getPathname()), true, 64, JSON_THROW_ON_ERROR);
    }
}
if (! $package || ! preg_match('/^\d+\.\d+\.\d+$/', $package['version'] ?? '') || app(PackageManifestService::class)->validateSchema($package, $type)) {
    throw new RuntimeException('Invalid stable extension manifest.');
}
foreach (array_keys($files) as $path) {
    if (! str_starts_with($path, $package['slug'].'/')) {
        throw new RuntimeException('Package must use one slug directory.');
    }
}
mkdir($output, 0700, true);
copy($source, $output.'/package.zip');
ksort($files);
$manifest = ['schema_version' => 1, 'type' => $type, 'slug' => $package['slug'], 'version' => $package['version'], 'commit' => $commit, 'requires' => $package['requires'] ?? ['php' => '^8.2'], 'sha256' => hash_file('sha256', $source), 'files' => $files];
$json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
if (! openssl_sign($json, $signature, file_get_contents($keyPath), OPENSSL_ALGO_SHA256)) {
    throw new RuntimeException('Signing failed.');
}
file_put_contents($output.'/release.json', $json);
file_put_contents($output.'/release.sig', base64_encode($signature)."\n");
echo 'Signed '.$type.' '.$package['slug'].' '.$package['version']."\n";
