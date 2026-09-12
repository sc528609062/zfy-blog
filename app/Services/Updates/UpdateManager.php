<?php

namespace App\Services\Updates;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

class UpdateManager
{
    public function __construct(private readonly ReleaseClient $client, private readonly ReleaseVerifier $verifier) {}

    public function prepare(array $release): string
    {
        $id = (string) Str::uuid();
        $work = storage_path('app/private/updates/'.$id);
        File::ensureDirectoryExists($work, 0700);
        try {
            $this->client->download($release['manifest_url'], $work.'/release.json', 8 * 1024 * 1024);
            $this->client->download($release['signature_url'], $work.'/release.sig', 8192);
            $manifest = $this->verifier->manifest(File::get($work.'/release.json'), File::get($work.'/release.sig'), (string) config('updates.public_key'));
            if ($manifest['type'] !== 'core' || $manifest['version'] !== $release['version'] || version_compare($manifest['version'], config('zfy.version'), '<=')) {
                $this->fail('Release identity or version mismatch.');
            }
            $this->client->download($release['package_url'], $work.'/package.zip', config('updates.max_bytes'));
            $this->verifier->extract($work.'/package.zip', $manifest, $work.'/files');
            $this->checkLocalFiles($manifest);
            $this->state($id, ['id' => $id, 'status' => 'ready', 'version' => $manifest['version'], 'created_at' => now()->toIso8601String()]);
            File::copy(base_path('scripts/update-worker.php'), $work.'/worker.php');

            return $id;
        } catch (\Throwable $exception) {
            $this->state($id, ['id' => $id, 'status' => 'failed', 'error' => $exception->getMessage()]);
            throw $exception;
        }
    }

    public function checkLocalFiles(array $manifest): void
    {
        $baselinePath = base_path('.zfy-release.json');
        $baseline = is_file($baselinePath) ? json_decode(File::get($baselinePath), true, 64, JSON_THROW_ON_ERROR) : null;
        if (! $baseline) {
            $this->fail('This source checkout has no installed-release baseline. Install a signed release or create a baseline after committing and verifying the checkout.');
        }
        $conflicts = [];
        foreach ($baseline['files'] ?? [] as $path => $hash) {
            $this->verifier->path($path);
            if (! is_file(base_path($path)) || ! hash_equals($hash, hash_file('sha256', base_path($path)))) {
                $conflicts[] = $path;
            }
        }
        foreach ($manifest['files'] as $path => $hash) {
            if (file_exists(base_path($path)) && ! isset($baseline['files'][$path])) {
                $conflicts[] = $path;
            }
        }
        if ($conflicts !== []) {
            throw ValidationException::withMessages(['update' => ['存在本地文件修改或冲突，更新已停止。', ...array_values(array_unique($conflicts))]]);
        }
    }

    public function prepareOffline(string $archive, string $json, string $signature): string
    {
        $manifest = $this->verifier->manifest($json, $signature, (string) config('updates.public_key'));
        if ($manifest['type'] !== 'core' || version_compare($manifest['version'], config('zfy.version'), '<=')) {
            $this->fail('Invalid core update version.');
        }
        $id = (string) Str::uuid();
        $work = $this->directory($id);
        File::ensureDirectoryExists($work, 0700);
        try {
            File::put($work.'/release.json', $json);
            File::put($work.'/release.sig', $signature);
            File::copy($archive, $work.'/package.zip');
            $this->verifier->extract($work.'/package.zip', $manifest, $work.'/files');
            $this->checkLocalFiles($manifest);
            File::copy(base_path('scripts/update-worker.php'), $work.'/worker.php');
            $this->state($id, ['id' => $id, 'status' => 'ready', 'version' => $manifest['version'], 'created_at' => now()->toIso8601String()]);

            return $id;
        } catch (\Throwable $exception) {
            $this->state($id, ['id' => $id, 'status' => 'failed', 'error' => $exception->getMessage()]);
            throw $exception;
        }
    }

    public function recover(string $id, string $action): void
    {
        if (! in_array($action, ['resume', 'restore', 'finish'], true)) {
            $this->fail('Invalid recovery action.');
        }
        $work = $this->directory($id);
        if (! is_file($work.'/job.json')) {
            $this->fail('Recovery job is missing.');
        }
        $process = new Process([PHP_BINARY, '-d', 'disable_functions=', $work.'/worker.php', $work.'/job.json', $action]);
        $process->setTimeout(1800);
        $process->run();
        if (! $process->isSuccessful()) {
            $this->fail('Recovery failed; maintenance remains active. Inspect the private update log.');
        }
    }

    public function run(string $id): void
    {
        $work = $this->directory($id);
        $state = json_decode(File::get($work.'/state.json'), true);
        if (($state['status'] ?? '') !== 'ready') {
            $this->fail('Update is not ready.');
        }
        $manifest = $this->verifier->manifest(File::get($work.'/release.json'), File::get($work.'/release.sig'), (string) config('updates.public_key'));
        $this->checkLocalFiles($manifest);
        $connection = DB::connection();
        $configuration = $connection->getConfig();
        $job = ['root' => base_path(), 'work' => $work, 'php' => PHP_BINARY, 'database' => ['driver' => $connection->getDriverName(), 'host' => $configuration['host'] ?? '', 'port' => $configuration['port'] ?? 3306, 'name' => $configuration['database'], 'user' => $configuration['username'] ?? '', 'password' => $configuration['password'] ?? '', 'dump' => config('backup.mysqldump'), 'mysql' => config('backup.mysql')]];
        $job['public_key'] = (string) config('updates.public_key');
        File::put($work.'/job.json', json_encode($job, JSON_THROW_ON_ERROR));
        @chmod($work.'/job.json', 0600);
        $process = new Process([PHP_BINARY, '-d', 'disable_functions=', $work.'/worker.php', $work.'/job.json']);
        $process->setTimeout(1800);
        $process->run();
        if (! $process->isSuccessful()) {
            $this->fail('Update failed. Inspect the update status and recovery log.');
        }
    }

    public function status(): array
    {
        return collect(File::glob(storage_path('app/private/updates/*/state.json')))->map(fn ($path) => json_decode(File::get($path), true))->sortByDesc('created_at')->values()->take(20)->all();
    }

    private function directory(string $id): string
    {
        if (! Str::isUuid($id)) {
            $this->fail('Invalid update identifier.');
        }

        return storage_path('app/private/updates/'.$id);
    }

    private function state(string $id, array $state): void
    {
        File::put($this->directory($id).'/state.json', json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['update' => $message]);
    }
}
