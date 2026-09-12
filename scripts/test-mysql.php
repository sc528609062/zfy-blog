<?php

use Symfony\Component\Process\Process;

// Requires an isolated MySQL instance on port 13307; never uses the site database.
require dirname(__DIR__).'/vendor/autoload.php';

$pdo = new PDO('mysql:host=127.0.0.1;port=13307;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('CREATE DATABASE IF NOT EXISTS zfy_isolated_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$environment = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_HOST' => '127.0.0.1', 'DB_PORT' => '13307', 'DB_DATABASE' => 'zfy_isolated_test', 'DB_USERNAME' => 'root', 'DB_PASSWORD' => '', 'DB_URL' => '', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'MAIL_MAILER' => 'array', 'ZFY_SAFE_MODE' => 'true'];
$process = new Process([PHP_BINARY, '-d', 'disable_functions=', 'vendor/bin/phpunit', ...array_slice($argv, 1)], dirname(__DIR__), $environment);
$process->setTimeout(300);
$process->run(fn ($type, $buffer) => print $buffer);
exit($process->getExitCode());
