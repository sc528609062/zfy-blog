<?php

if (PHP_SAPI !== 'cli') {
    exit(1);
}
require dirname(__DIR__).'/vendor/autoload.php';
use Symfony\Component\Process\Process;

$root = dirname(__DIR__);
$work = $root.'/storage/framework/testing/release-'.bin2hex(random_bytes(8));
mkdir($work, 0700, true);
$key = openssl_pkey_new(['private_key_bits' => 2048, 'config' => $root.'/tests/fixtures/openssl.cnf']);
openssl_pkey_export($key, $private, null, ['config' => $root.'/tests/fixtures/openssl.cnf']);
file_put_contents($work.'/private.pem', $private);
file_put_contents($work.'/public.pem', openssl_pkey_get_details($key)['key']);
$run = function (array $arguments) use ($root, $work) {
    $process = new Process([PHP_BINARY, '-d', 'disable_functions=', ...$arguments], $root, ['ZFY_RELEASE_PRIVATE_KEY_FILE' => $work.'/private.pem']);
    $process->setTimeout(600)->mustRun();
    echo $process->getOutput();
};
try {
    $configuration = require $root.'/config/zfy.php';
    $git = new Process(['git', 'rev-parse', 'HEAD'], $root);
    $git->mustRun();
    $run(['scripts/build-release.php', $configuration['version'], trim($git->getOutput()), $work.'/release']);
    $run(['scripts/verify-installation.php', $work.'/release', $work.'/public.pem']);
    foreach (['plugin' => 'editorial-tools', 'theme' => 'native-journal'] as $type => $slug) {
        $run(['artisan', 'zfy:extension', 'validate', $slug, '--type='.$type, '--directory=examples/'.$type.'s/'.$slug]);
        $run(['artisan', 'zfy:extension', 'pack', $slug, '--type='.$type, '--directory=examples/'.$type.'s/'.$slug, '--output='.$work.'/'.$slug.'.zip']);
        $run(['scripts/sign-extension.php', $type, $work.'/'.$slug.'.zip', trim($git->getOutput()), $work.'/'.$slug.'-signed']);
    }
    echo 'Local packaging rehearsal complete: '.$work."\n";
} finally {
    unlink($work.'/private.pem');
}
