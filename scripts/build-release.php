<?php

use Symfony\Component\Process\Process;

// Run after composer install --no-dev and npm ci && npm run build in CI.
require dirname(__DIR__).'/vendor/autoload.php';

$root = dirname(__DIR__);
$version = $argv[1] ?? '';
$commit = $argv[2] ?? '';
$output = $argv[3] ?? $root.'/dist/release';
$privateKeyPath = getenv('ZFY_RELEASE_PRIVATE_KEY_FILE');
if (! preg_match('/^\d+\.\d+\.\d+$/', $version) || ! preg_match('/^[a-f0-9]{40}$/', $commit) || ! $privateKeyPath || ! is_file($privateKeyPath)) {
    throw new RuntimeException('Usage: build-release.php VERSION COMMIT OUTPUT; set ZFY_RELEASE_PRIVATE_KEY_FILE.');
}
if (! is_file($root.'/public/build/manifest.json')) {
    throw new RuntimeException('Build frontend assets before packaging.');
}
$configuration = require $root.'/config/zfy.php';
if ($configuration['version'] !== $version) {
    throw new RuntimeException('Release tag must match config/zfy.php version.');
}
if (! is_dir($output)) {
    mkdir($output, 0700, true);
}
if (file_exists($output.'/package.zip')) {
    throw new RuntimeException('Release archive already exists.');
}
$archive = new PharData($output.'/package.zip');
$files = [];
$sources = [];
$include = function (string $path) use ($root, &$sources, &$files): void {
    if (! is_file($root.'/'.$path) || is_link($root.'/'.$path)) {
        return;
    }
    $files[$path] = hash_file('sha256', $root.'/'.$path);
    $sources[$path] = $root.'/'.$path;
};
foreach (['app', 'bootstrap', 'config', 'database', 'resources', 'routes', 'vendor', 'public', 'scripts', 'schemas', 'docs', 'examples'] as $directory) {
    if (! is_dir($root.'/'.$directory)) {
        continue;
    }
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$directory, FilesystemIterator::SKIP_DOTS)) as $file) {
        if (! $file->isFile() || $file->isLink()) {
            continue;
        }
        $path = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
        if (str_starts_with($path, 'bootstrap/cache/') || str_starts_with($path, 'public/storage/') || $path === 'public/hot' || preg_match('~^database/.*\.sqlite~', $path)) {
            continue;
        }
        $include($path);
    }
}
foreach (['artisan', 'composer.json', 'composer.lock', 'package.json', 'package-lock.json', 'public/index.php', 'public/.htaccess'] as $path) {
    $include($path);
}
$archive->buildFromIterator(new ArrayIterator($sources));
unset($archive);
ksort($files);
$manifest = ['schema_version' => 1, 'type' => 'core', 'version' => $version, 'commit' => $commit, 'requires' => ['php' => '^8.2'], 'sha256' => hash_file('sha256', $output.'/package.zip'), 'files' => $files];
$json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
if (! openssl_sign($json, $signature, file_get_contents($privateKeyPath), OPENSSL_ALGO_SHA256)) {
    throw new RuntimeException('Release signing failed.');
}
file_put_contents($output.'/release.json', $json);
file_put_contents($output.'/release.sig', base64_encode($signature)."\n");
$installation = new PharData($output.'/installation.zip');
$installationFiles = $files;
foreach (['themes', 'plugins'] as $directory) {
    $tracked = new Process(['git', 'ls-files', '-z', '--', $directory], $root);
    $tracked->mustRun();
    foreach (array_filter(explode("\0", $tracked->getOutput())) as $path) {
        if (is_link($root.'/'.$path) || ! is_file($root.'/'.$path)) {
            throw new RuntimeException('Built-in extension file is unavailable.');
        }
        $sources[$path] = $root.'/'.$path;
        $installationFiles[$path] = hash_file('sha256', $root.'/'.$path);
    }
}
foreach (['.env.example', 'README.md'] as $path) {
    $sources[$path] = $root.'/'.$path;
    $installationFiles[$path] = hash_file('sha256', $root.'/'.$path);
}
$baseline = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
$installation->buildFromIterator(new ArrayIterator($sources));
$installation->addFromString('.zfy-release.json', $baseline);
$installationFiles['.zfy-release.json'] = hash('sha256', $baseline);
foreach (['storage/app/private', 'storage/app/public', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'bootstrap/cache'] as $directory) {
    $installation->addEmptyDir($directory);
}
unset($installation);
ksort($installationFiles);
$installationJson = json_encode([...$manifest, 'type' => 'installation', 'sha256' => hash_file('sha256', $output.'/installation.zip'), 'files' => $installationFiles], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
if (! openssl_sign($installationJson, $installationSignature, file_get_contents($privateKeyPath), OPENSSL_ALGO_SHA256)) {
    throw new RuntimeException('Installation signing failed.');
}
file_put_contents($output.'/installation.json', $installationJson);
file_put_contents($output.'/installation.sig', base64_encode($installationSignature)."\n");
echo 'Release built: '.$version."\n";
