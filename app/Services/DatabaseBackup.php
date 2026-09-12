<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class DatabaseBackup
{
    public function restore(string $path): void
    {
        $root = realpath(storage_path('app/private/backups'));
        $path = realpath($path);
        if (! $root || ! $path || dirname($path) !== $root || filesize($path) === 0) {
            throw new RuntimeException('Invalid database backup.');
        }
        $connection = DB::connection();
        if ($connection->transactionLevel() !== 0) {
            throw new RuntimeException('Cannot restore within a database transaction.');
        }
        $config = $connection->getConfig();
        $driver = $connection->getDriverName();
        if ($driver === 'sqlite') {
            $check = new \PDO('sqlite:'.$path);
            if ($check->query('PRAGMA integrity_check')->fetchColumn() !== 'ok') {
                throw new RuntimeException('SQLite backup integrity check failed.');
            }
            $check = null;
            DB::disconnect();
            foreach (['-wal', '-shm'] as $suffix) {
                File::delete($config['database'].$suffix);
            }
            if (! File::copy($path, $config['database'])) {
                throw new RuntimeException('Database restore failed.');
            }
        } elseif ($driver === 'mysql') {
            $objects = $connection->select('SHOW FULL TABLES');
            $connection->statement('SET FOREIGN_KEY_CHECKS=0');
            try {
                foreach ($objects as $object) {
                    [$name, $type] = array_values((array) $object);
                    $connection->statement('DROP '.($type === 'VIEW' ? 'VIEW' : 'TABLE').' `'.str_replace('`', '``', $name).'`');
                }
            } finally {
                $connection->statement('SET FOREIGN_KEY_CHECKS=1');
            }
            DB::disconnect();
            $input = fopen($path, 'r');
            try {
                $process = new Process([config('backup.mysql'), '--host='.$config['host'], '--port='.$config['port'], '--user='.$config['username'], '--', $config['database']], null, ['MYSQL_PWD' => (string) ($config['password'] ?? '')]);
                $process->setInput($input)->setTimeout(180)->run();
                if (! $process->isSuccessful()) {
                    throw new RuntimeException('Database restore failed.');
                }
            } finally {
                fclose($input);
            }
        } else {
            throw new RuntimeException('Unsupported database driver.');
        }
        DB::reconnect();
    }

    public function create(): string
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $directory = storage_path('app/private/backups');
        File::ensureDirectoryExists($directory, 0700);
        $path = $directory.'/database-'.now()->format('Ymd-His').'-'.Str::random(8).($driver === 'sqlite' ? '.sqlite' : '.sql');
        try {
            if ($driver === 'sqlite') {
                $connection->getPdo()->exec('VACUUM INTO '.$connection->getPdo()->quote($path));
            } elseif ($driver === 'mysql') {
                $configuration = $connection->getConfig();
                $process = new Process([
                    config('backup.mysqldump'), '--single-transaction', '--quick', '--skip-lock-tables', '--hex-blob',
                    '--host='.$configuration['host'], '--port='.$configuration['port'], '--user='.$configuration['username'],
                    '--result-file='.$path, '--', $configuration['database'],
                ], null, ['MYSQL_PWD' => (string) ($configuration['password'] ?? '')]);
                $process->setTimeout(120);
                $process->run();
                if (! $process->isSuccessful()) {
                    throw new RuntimeException('数据库备份失败，请检查 mysqldump 路径和数据库权限。');
                }
            } else {
                throw new RuntimeException('当前数据库类型不支持自动备份。');
            }
            if (! is_file($path) || filesize($path) === 0) {
                throw new RuntimeException('数据库备份文件为空。');
            }
            @chmod($path, 0600);

            return $path;
        } catch (\Throwable $exception) {
            File::delete($path);
            throw $exception;
        }
    }
}
