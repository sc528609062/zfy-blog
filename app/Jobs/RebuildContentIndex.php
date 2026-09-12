<?php

namespace App\Jobs;

use App\Models\Content;
use App\Services\ThemeManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class RebuildContentIndex implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Cache::store('file')->lock('zfy-index-rebuild', 600)->block(1, function () {
            Content::withTrashed()->where(fn ($query) => $query->whereNotNull('deleted_at')->orWhere('status', '!=', 'published')->orWhere('published_at', '>', now()))->unsearchable();
            Content::published()->searchable();
            app(ThemeManager::class)->forgetActiveCache();
            Artisan::call('view:clear');
        });
    }
}
