<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class ExtensionAssetController extends Controller
{
    public function show(string $type, string $slug, string $path)
    {
        abort_unless(in_array($type, ['theme', 'plugin'], true) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug), 404);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $types = ['css' => 'text/css', 'js' => 'application/javascript', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp', 'gif' => 'image/gif', 'avif' => 'image/avif', 'woff' => 'font/woff', 'woff2' => 'font/woff2'];
        abort_unless(isset($types[$extension]), 404);
        $root = realpath(base_path($type.'s/'.$slug));
        $file = realpath(base_path($type.'s/'.$slug.'/'.$path));
        abort_unless($root && $file && is_file($file) && str_starts_with(str_replace('\\', '/', $file), str_replace('\\', '/', $root).'/'), 404);
        abort_unless(str_starts_with($path, 'assets/') || $path === 'preview.png', 404);

        return response()->file($file, ['Content-Type' => $types[$extension], 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'public, max-age=3600']);
    }
}
