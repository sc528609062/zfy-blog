<?php

namespace App\Services\Updates;

use App\Models\Plugin;
use App\Models\Theme;
use App\Services\SiteSettings;
use Illuminate\Support\Facades\Cache;

class UpdateChecker
{
    public function check(): array
    {
        $results = [];
        $saved = app(SiteSettings::class)->get('updates.source', []);
        $core = array_replace(config('updates'), is_array($saved) ? array_intersect_key($saved, array_flip(['provider', 'repository'])) : []);
        try {
            $release = app(ReleaseClient::class)->latest($core);
            Cache::store('file')->put('zfy.available-release', $release, now()->addDay());
            $results['core'] = ['version' => $release['version'], 'available' => version_compare($release['version'], config('zfy.version'), '>'), 'checked_at' => now()->toIso8601String()];
        } catch (\Throwable $exception) {
            $results['core'] = ['status' => 'check-failed', 'error' => $exception::class];
            report($exception);
        }
        foreach (['plugin' => Plugin::get(), 'theme' => Theme::get()] as $type => $packages) {
            foreach ($packages as $package) {
                if (! is_file(base_path($type.'s/'.$package->slug.'/'.$type.'.json'))) {
                    continue;
                }
                $updater = app(ExtensionUpdater::class);
                $source = $updater->source($type, $package->slug);
                if (! filled($source['repository']) || ! filled($source['public_key'])) {
                    continue;
                }
                try {
                    $results[$type.':'.$package->slug] = $updater->check($type, $package->slug);
                } catch (\Throwable $exception) {
                    $results[$type.':'.$package->slug] = ['status' => 'check-failed', 'error' => $exception::class];
                    report($exception);
                }
            }
        }
        Cache::store('file')->put('zfy.update-checks', ['checked_at' => now()->toIso8601String(), 'results' => $results], now()->addDays(2));

        return $results;
    }
}
