<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class UpdateResumeTest extends TestCase
{
    public function test_interrupted_file_replacement_resumes_with_existing_journal(): void
    {
        $root = storage_path('framework/testing/resume-'.Str::uuid());
        $work = $root.'/storage/app/private/updates/'.Str::uuid();
        File::ensureDirectoryExists($work.'/files');
        File::ensureDirectoryExists($work.'/original');
        File::ensureDirectoryExists($root.'/storage/framework');
        try {
            $db = new \PDO('sqlite:'.$root.'/database.sqlite');
            $db->exec('CREATE TABLE retained (id INTEGER)');
            $db = null;
            $original = '<?php exit(0);';
            $next = '<?php /* next version */ exit(0);';
            File::put($root.'/artisan', $next);
            File::put($work.'/original/artisan', $original);
            File::put($work.'/files/artisan', $next);
            File::put($root.'/.zfy-release.json', json_encode(['files' => ['artisan' => hash('sha256', $original)]]));
            File::put($work.'/journal.json', json_encode(['artisan' => true]));
            File::put($work.'/state.json', json_encode(['id' => basename($work), 'status' => 'installing']));
            File::put($root.'/storage/framework/down', '{}');
            File::put(dirname($work).'/writes-paused', basename($work));
            File::copy($root.'/database.sqlite', $work.'/database.sqlite');
            File::put($work.'/backup-complete.json', json_encode(['sha256' => hash_file('sha256', $work.'/database.sqlite')]));
            $key = openssl_pkey_new(['private_key_bits' => 2048, 'config' => base_path('tests/fixtures/openssl.cnf')]);
            $manifest = json_encode(['type' => 'core', 'files' => ['artisan' => hash('sha256', $next)]]);
            openssl_sign($manifest, $signature, $key, OPENSSL_ALGO_SHA256);
            File::put($work.'/release.json', $manifest);
            File::put($work.'/release.sig', base64_encode($signature));
            File::put($work.'/job.json', json_encode(['root' => $root, 'work' => $work, 'php' => PHP_BINARY, 'public_key' => openssl_pkey_get_details($key)['key'], 'database' => ['driver' => 'sqlite', 'name' => $root.'/database.sqlite']]));
            $process = new Process([PHP_BINARY, '-d', 'disable_functions=', base_path('scripts/update-worker.php'), $work.'/job.json', 'resume']);
            $process->run();
            $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
            $this->assertSame('completed', json_decode(File::get($work.'/state.json'), true)['status']);
            $this->assertSame($original, File::get($work.'/original/artisan'));
            $this->assertSame($next, File::get($root.'/artisan'));
            $this->assertFileDoesNotExist($root.'/storage/framework/down');
        } finally {
            File::deleteDirectory($root);
        }
    }
}
