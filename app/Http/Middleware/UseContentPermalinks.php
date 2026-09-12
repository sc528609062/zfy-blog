<?php

namespace App\Http\Middleware;

use App\Services\SiteSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class UseContentPermalinks
{
    public function handle(Request $request, Closure $next)
    {
        URL::defaults(['contentBase' => app(SiteSettings::class)->get('permalink.content_base', 'content')]);

        return $next($request);
    }
}
