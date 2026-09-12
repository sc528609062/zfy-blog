<?php

namespace Tests\Feature;

use App\Services\DatabaseBackup;
use App\Services\ExtensionMaintenance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PDO;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class MySqlUpdateRecoveryTest extends TestCase
{
    public function test_extension_ddl_and_files_restore_together_after_failure_and_interruption(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires isolated MySQL.');
        }
        $this->assertSame('13307', (string) DB::connection()->getConfig('port'));
        $this->assertSame('zfy_isolated_test', DB::connection()->getDatabaseName());
        $configuration = DB::connection()->getConfig();
        $connection = config('database.default');
        $storage = app()->storagePath();
        $database = 'zfy_extension_recovery_'.bin2hex(random_bytes(6));
        DB::statement('CREATE DATABASE `'.$database.'`');
        $slug = 'recovery-test-'.bin2hex(random_bytes(6));
        $target = base_path('plugins/'.$slug);
        $root = $storage.'/framework/testing/'.$slug;
        config(['database.connections.extension_recovery' => [...$configuration, 'database' => $database], 'database.default' => 'extension_recovery']);
        app()->useStoragePath($root.'/storage');
        try {
            DB::statement('CREATE TABLE original (value VARCHAR(80)) ENGINE=InnoDB');
            DB::table('original')->insert(['value' => 'retained']);
            File::ensureDirectoryExists($target);
            $manifest = ['slug' => $slug, 'version' => '1.0.0'];
            File::put($target.'/plugin.json', json_encode($manifest));
            $backup = storage_path('app/private/extensions/versions/old-'.$slug);
            File::ensureDirectoryExists(dirname($backup));
            File::copyDirectory($target, $backup);
            $files = ['type' => 'plugin', 'slug' => $slug, 'backup' => $backup];
            $maintenance = app(ExtensionMaintenance::class);
            try {
                $maintenance->run(function () use ($target) {
                    DB::statement('CREATE TABLE introduced (id INT)');
                    DB::table('original')->delete();
                    File::put($target.'/plugin.json', '{"version":"2.0.0"}');
                    throw new \RuntimeException('Failed extension DDL');
                }, fn () => $maintenance->restorePackageFiles($files), ['package_files' => $files]);
                $this->fail('Expected migration failure.');
            } catch (\RuntimeException $exception) {
                $this->assertSame('Failed extension DDL', $exception->getMessage());
            }
            $this->assertFalse(DB::getSchemaBuilder()->hasTable('introduced'));
            $this->assertSame('retained', DB::table('original')->value('value'));
            $this->assertSame($manifest, json_decode(File::get($target.'/plugin.json'), true));
            $snapshot = app(DatabaseBackup::class)->create();
            DB::table('original')->delete();
            File::put($target.'/plugin.json', '{"version":"2.0.0"}');
            File::put(storage_path('app/private/extensions/recovery.json'), json_encode(['backup' => $snapshot, 'context' => ['package_files' => $files]]));
            File::put(storage_path('app/private/updates/writes-paused'), 'extension-interrupted');
            $maintenance->recover();
            $this->assertSame('retained', DB::table('original')->value('value'));
            $this->assertSame($manifest, json_decode(File::get($target.'/plugin.json'), true));
            $this->assertFileDoesNotExist(storage_path('app/private/updates/writes-paused'));
        } finally {
            DB::purge('extension_recovery');
            config(['database.default' => $connection]);
            DB::statement('DROP DATABASE `'.$database.'`');
            app()->useStoragePath($storage);
            File::deleteDirectory($root);
            File::deleteDirectory($target);
        }
    }

    public function test_failed_mysql_ddl_restores_database_and_files_together(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires isolated MySQL.');
        }
        $this->assertSame('13307', (string) DB::connection()->getConfig('port'));
        $this->assertSame('zfy_isolated_test', DB::connection()->getDatabaseName());
        $pdo = new PDO('mysql:host=127.0.0.1;port=13307;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $database = 'zfy_update_recovery_'.bin2hex(random_bytes(6));
        $pdo->exec('CREATE DATABASE `'.$database.'`');
        $pdo->exec('USE `'.$database.'`');
        $root = storage_path('framework/testing/mysql-update-'.Str::uuid());
        $work = $root.'/storage/app/private/updates/'.Str::uuid();
        File::ensureDirectoryExists($work.'/files');
        File::ensureDirectoryExists($root.'/storage/framework');
        try {
            $pdo->exec('CREATE TABLE original (value VARCHAR(80)) ENGINE=InnoDB');
            $pdo->exec("INSERT INTO original VALUES ('retained')");
            $original = '<?php exit(0);';
            $broken = '<?php if (($argv[1] ?? "") === "migrate") { $pdo = new PDO("mysql:host=127.0.0.1;port=13307;dbname='.$database.'", "root", ""); $pdo->exec("CREATE TABLE introduced (id INT)"); $pdo->exec("DELETE FROM original"); exit(1); }';
            File::put($root.'/artisan', $original);
            File::put($root.'/.zfy-release.json', json_encode(['files' => ['artisan' => hash('sha256', $original)]]));
            File::put($work.'/files/artisan', $broken);
            $key = openssl_pkey_new(['private_key_bits' => 2048, 'config' => base_path('tests/fixtures/openssl.cnf')]);
            $manifest = json_encode(['type' => 'core', 'files' => ['artisan' => hash('sha256', $broken)]]);
            openssl_sign($manifest, $signature, $key, OPENSSL_ALGO_SHA256);
            File::put($work.'/release.json', $manifest);
            File::put($work.'/release.sig', base64_encode($signature));
            File::put($work.'/state.json', json_encode(['id' => basename($work), 'status' => 'ready']));
            File::put($work.'/job.json', json_encode(['root' => $root, 'work' => $work, 'php' => PHP_BINARY, 'public_key' => openssl_pkey_get_details($key)['key'], 'database' => ['driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 13307, 'name' => $database, 'user' => 'root', 'password' => '', 'dump' => config('backup.mysqldump'), 'mysql' => config('backup.mysql')]]));
            File::copy(base_path('scripts/update-worker.php'), $work.'/worker.php');
            $process = new Process([PHP_BINARY, '-d', 'disable_functions=', $work.'/worker.php', $work.'/job.json']);
            $process->setTimeout(60)->run();
            $this->assertSame(1, $process->getExitCode());
            $this->assertSame('rolled-back', json_decode(File::get($work.'/state.json'), true)['status']);
            $this->assertSame($original, File::get($root.'/artisan'));
            $this->assertSame('retained', $pdo->query('SELECT value FROM original')->fetchColumn());
            $this->assertSame(['original'], $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN));
            $this->assertFileExists($root.'/storage/framework/down');
            $process = new Process([PHP_BINARY, '-d', 'disable_functions=', $work.'/worker.php', $work.'/job.json', 'finish']);
            $process->run();
            $this->assertSame(0, $process->getExitCode());
            $this->assertFileDoesNotExist($root.'/storage/framework/down');
        } finally {
            $pdo->exec('DROP DATABASE `'.$database.'`');
            File::deleteDirectory($root);
        }
    }
}
