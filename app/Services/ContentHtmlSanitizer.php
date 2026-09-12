<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;
use Mews\Purifier\Facades\Purifier;

class ContentHtmlSanitizer
{
    public function clean(string $html): string
    {
        $hosts = Schema::hasTable('settings') ? app(SiteSettings::class)->get('media.iframe_hosts', 'player.bilibili.com') : 'player.bilibili.com';
        $hosts = array_filter(preg_split('/[\s,]+/', (string) $hosts), fn ($host) => preg_match('/^[a-z0-9.-]+$/', $host));
        $expression = $hosts ? '%^https://(?:'.implode('|', array_map(fn ($host) => preg_quote($host, '%'), $hosts)).')(?:/|$)%' : '%(?!)%';

        return Purifier::clean($html, array_replace(config('purifier.settings.default', []), ['URI.SafeIframeRegexp' => $expression]));
    }
}
