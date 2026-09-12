<?php

// Verify before extracting into a new empty site directory.
if (PHP_SAPI !== 'cli' || count($argv) !== 3) {
    throw new RuntimeException('Usage: php verify-installation.php RELEASE_DIRECTORY TRUSTED_PUBLIC_KEY_FILE');
}
$directory = rtrim($argv[1], '/\\');
$json = file_get_contents($directory.'/installation.json');
$signature = base64_decode(trim(file_get_contents($directory.'/installation.sig')), true);
if (! $signature || openssl_verify($json, $signature, file_get_contents($argv[2]), OPENSSL_ALGO_SHA256) !== 1) {
    throw new RuntimeException('Installation signature rejected.');
}
$manifest = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
if (($manifest['type'] ?? '') !== 'installation' || ! hash_equals($manifest['sha256'], hash_file('sha256', $directory.'/installation.zip'))) {
    throw new RuntimeException('Installation archive rejected.');
}
$archive = new PharData($directory.'/installation.zip');
$prefix = 'phar://'.str_replace('\\', '/', realpath($directory.'/installation.zip')).'/';
$seen = [];
foreach (new RecursiveIteratorIterator($archive) as $file) {
    $path = substr(str_replace('\\', '/', $file->getPathname()), strlen($prefix));
    if ($file->isLink() || isset($seen[strtolower($path)]) || ! isset($manifest['files'][$path]) || preg_match('~(^/|(^|/)\.\.?(/|$)|:|[\x00-\x1f]|[ .](/|$))~', $path) || preg_match('~(^|/)(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(?:\.|/|$)~i', $path) || ! hash_equals($manifest['files'][$path], hash_file('sha256', $file->getPathname()))) {
        throw new RuntimeException('Installation file rejected: '.$path);
    }
    $seen[strtolower($path)] = true;
}
if (count($seen) !== count($manifest['files'])) {
    throw new RuntimeException('Installation files missing.');
}
echo 'Verified installation '.$manifest['version']."\n";
