<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class UpdateWriteBarrier
{
    public function handle(Request $request, Closure $next)
    {
        $directory = storage_path('app/private/updates');
        File::ensureDirectoryExists($directory);
        if (is_file($directory.'/writes-paused')) {
            abort(503, '站点正在更新，请稍后重试。');
        }
        $handle = fopen($directory.'/requests.lock', 'c');
        if (! $handle || ! flock($handle, LOCK_SH | LOCK_NB)) {
            abort(503);
        }
        try {
            $request->attributes->set('zfy.maintenance_lock', $handle);
            if (is_file($directory.'/writes-paused')) {
                abort(503);
            }

            return $next($request);
        } finally {
            $request->attributes->remove('zfy.maintenance_lock');
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
