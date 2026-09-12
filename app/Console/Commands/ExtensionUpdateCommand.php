<?php

namespace App\Console\Commands;

use App\Services\Updates\ExtensionUpdater;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ExtensionUpdateCommand extends Command
{
    protected $signature = 'zfy:extensions-update {id?}';

    protected $description = 'Run confirmed extension updates in independent safe-mode processes';

    public function handle(ExtensionUpdater $updater): int
    {
        if ($id = $this->argument('id')) {
            $state = $updater->state($id);
            $updater->apply($state['type'], $state['slug'], $id);
            $this->info('Extension installed. Activate it to run its migrations.');

            return self::SUCCESS;
        }
        if (is_file(storage_path('app/private/updates/writes-paused'))) {
            return self::SUCCESS;
        }
        foreach ($updater->statuses() as $state) {
            if ($state['status'] !== 'queued' && ! ($state['status'] === 'downloading' && Carbon::parse($state['updated_at'] ?? $state['created_at'])->lt(now()->subMinutes(30)))) {
                continue;
            }
            $process = new Process([PHP_BINARY, '-d', 'disable_functions=', base_path('artisan'), 'zfy:extensions-update', $state['id']], base_path(), ['ZFY_SAFE_MODE' => 'true']);
            $process->setTimeout(1800)->run();
            if (! $process->isSuccessful()) {
                logger()->error('Extension update worker failed', ['id' => $state['id'], 'exit' => $process->getExitCode()]);
                $this->error('Update failed: '.$state['id']);
            }
        }

        return self::SUCCESS;
    }
}
