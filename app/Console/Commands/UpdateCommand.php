<?php

namespace App\Console\Commands;

use App\Services\Updates\UpdateManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class UpdateCommand extends Command
{
    protected $signature = 'zfy:update {id?} {--baseline} {--recover=} {--offline=}';

    protected $description = 'Apply a staged release or record the verified source baseline';

    public function handle(UpdateManager $manager): int
    {
        if ($this->option('recover')) {
            $manager->recover((string) $this->argument('id'), $this->option('recover'));
            $this->info('Recovery action completed.');

            return self::SUCCESS;
        }
        if ($directory = $this->option('offline')) {
            $id = $manager->prepareOffline($directory.'/package.zip', File::get($directory.'/release.json'), File::get($directory.'/release.sig'));
            $this->info('Offline release staged: '.$id);

            return self::SUCCESS;
        }
        if ($this->option('baseline')) {
            $git = new Process(['git', 'status', '--porcelain'], base_path());
            $git->mustRun();
            if (trim($git->getOutput()) !== '') {
                $this->error('Commit and verify local changes before creating a baseline.');

                return self::FAILURE;
            }
            $files = [];
            foreach (['app', 'bootstrap', 'config', 'database', 'resources', 'routes', 'vendor', 'public', 'scripts', 'schemas', 'docs', 'examples'] as $directory) {
                if (! is_dir(base_path($directory))) {
                    continue;
                }
                foreach (File::allFiles(base_path($directory)) as $file) {
                    $path = $directory.'/'.str_replace('\\', '/', $file->getRelativePathname());
                    if ($file->isLink() || str_starts_with($path, 'bootstrap/cache/') || str_starts_with($path, 'public/storage/') || $path === 'public/hot' || preg_match('~^database/.*\.sqlite~', $path)) {
                        continue;
                    }
                    $files[$path] = hash_file('sha256', $file->getPathname());
                }
            }
            foreach (['artisan', 'composer.json', 'composer.lock', 'package.json', 'package-lock.json', 'public/index.php', 'public/.htaccess'] as $path) {
                if (is_file(base_path($path))) {
                    $files[$path] = hash_file('sha256', base_path($path));
                }
            }
            File::put(base_path('.zfy-release.json'), json_encode(['version' => config('zfy.version'), 'files' => $files], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->info('Verified checkout baseline recorded.');

            return self::SUCCESS;
        }
        $id = $this->argument('id');
        if (! $id) {
            $state = collect($manager->status())->firstWhere('status', 'ready');
            $id = $state['id'] ?? null;
        }
        if (! $id) {
            return self::SUCCESS;
        }
        $manager->run($id);
        $this->info('Update completed.');

        return self::SUCCESS;
    }
}
