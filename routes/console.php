<?php

use App\Models\UpgradeLog;
use App\Services\SystemUpdateService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('zfy:update {version} {--log=}', function (SystemUpdateService $updates) {
    $log = UpgradeLog::query()->find($this->option('log'));

    if (! $log) {
        $this->error('Missing upgrade log.');

        return self::FAILURE;
    }

    try {
        $updates->run($log);
    } catch (Throwable $exception) {
        $this->error($exception->getMessage());

        return self::FAILURE;
    }

    $this->info('Update completed.');

    return self::SUCCESS;
})->purpose('Run a zfy-blog online update from a Gitee tag.');
