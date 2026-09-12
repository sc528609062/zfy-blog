<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PDO;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class UpdateWorkerTest extends TestCase
{
    public function test_failed_migration_restores_files_and_database_and_can_reopen(): void
    {
        $root = storage_path('framework/testing/worker-'.Str::uuid());
        $work = $root.'/storage/app/private/updates/'.Str::uuid();
        File::ensureDirectoryExists($work.'/files');
        File::ensureDirectoryExists($root.'/storage/framework');
        try {
            $database = $root.'/database.sqlite';
            $pdo = new PDO('sqlite:'.$database);
            $pdo->exec('CREATE TABLE original (value TEXT)');
            $pdo->exec("INSERT INTO original VALUES ('retained')");
            $pdo = null;
            $original = "<?php exit(0);\n";
            $broken = '<?php if (($argv[1] ?? "") === "migrate") { $db = new PDO("sqlite:".__DIR__."/database.sqlite"); $db->exec("CREATE TABLE introduced (id INTEGER)"); $db->exec("DELETE FROM original"); exit(1); }';
            File::put($root.'/artisan', $original);
            File::put($root.'/.zfy-release.json', json_encode(['files' => ['artisan' => hash('sha256', $original)]]));
            File::put($work.'/files/artisan', $broken);
            $key = openssl_pkey_new(['private_key_bits' => 2048, 'config' => base_path('tests/fixtures/openssl.cnf')]);
            $public = openssl_pkey_get_details($key)['key'];
            $manifest = json_encode(['type' => 'core', 'files' => ['artisan' => hash('sha256', $broken)]]);
            openssl_sign($manifest, $signature, $key, OPENSSL_ALGO_SHA256);
            File::put($work.'/release.json', $manifest);
            File::put($work.'/release.sig', base64_encode($signature));
            File::put($work.'/state.json', json_encode(['id' => basename($work), 'status' => 'ready']));
            File::put($work.'/job.json', json_encode(['root' => $root, 'work' => $work, 'php' => PHP_BINARY, 'public_key' => $public, 'database' => ['driver' => 'sqlite', 'name' => $database]]));
            File::copy(base_path('scripts/update-worker.php'), $work.'/worker.php');
            $process = new Process([PHP_BINARY, '-d', 'disable_functions=', $work.'/worker.php', $work.'/job.json']);
            $process->run();
            $this->assertSame(1, $process->getExitCode(), $process->getErrorOutput());
            $this->assertSame('rolled-back', json_decode(File::get($work.'/state.json'), true)['status']);
            $this->assertSame($original, File::get($root.'/artisan'));
            $pdo = new PDO('sqlite:'.$database);
            $this->assertSame('retained', $pdo->query('SELECT value FROM original')->fetchColumn());
            $this->assertSame(0, (int) $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE name = 'introduced'")->fetchColumn());
            $pdo = null;
            $this->assertFileExists($root.'/storage/framework/down');
            $process = new Process([PHP_BINARY, '-d', 'disable_functions=', $work.'/worker.php', $work.'/job.json', 'finish']);
            $process->run();
            $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
            $this->assertFileDoesNotExist($root.'/storage/framework/down');
            $this->assertFileDoesNotExist($work.'/job.json');
        } finally {
            $pdo = null;
            File::deleteDirectory($root);
        }
    }
}
