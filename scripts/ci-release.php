<?php

// Shared entry point for GitHub Actions and a Gitee Go PHP/Node build job.
require dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Process\Process;

$tag = getenv('RELEASE_TAG') ?: '';
if (! preg_match('/^v?(\d+\.\d+\.\d+)$/', $tag, $match)) {
    throw new RuntimeException('RELEASE_TAG must name an immutable stable version tag.');
}
$git = new Process(['git', 'rev-parse', '--verify', 'refs/tags/'.$tag.'^{commit}'], dirname(__DIR__));
$git->mustRun();
$commit = trim($git->getOutput());
$head = new Process(['git', 'rev-parse', 'HEAD'], dirname(__DIR__));
$head->mustRun();
if ($commit !== trim($head->getOutput())) {
    throw new RuntimeException('Checkout does not match release tag.');
}
$key = tempnam(sys_get_temp_dir(), 'zfy-sign-');
try {
    chmod($key, 0600);
    if (! getenv('ZFY_RELEASE_PRIVATE_KEY')) {
        throw new RuntimeException('Release signing key is missing.');
    }
    file_put_contents($key, getenv('ZFY_RELEASE_PRIVATE_KEY'));
    $process = new Process([PHP_BINARY, __DIR__.'/build-release.php', $match[1], $commit], dirname(__DIR__), ['ZFY_RELEASE_PRIVATE_KEY_FILE' => $key, 'ZFY_RELEASE_PRIVATE_KEY' => false]);
    $process->setTimeout(600);
    $process->mustRun();
    echo $process->getOutput();
} finally {
    unlink($key);
}
