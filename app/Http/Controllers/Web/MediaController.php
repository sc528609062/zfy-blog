<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function show(string $path): StreamedResponse
    {
        $relativePath = $this->normalizePath($path);
        abort_if($relativePath === '', 404);

        $disk = Storage::disk($this->mediaDisk());
        abort_unless($disk->exists($relativePath), 404);

        return $disk->response($relativePath, null, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    public function legacy(string $path): RedirectResponse
    {
        $relativePath = $this->normalizePath($path, true);
        abort_if($relativePath === '', 404);

        return redirect($this->publicUrl($relativePath), 301);
    }

    private function normalizePath(string $path, bool $stripLegacyRoot = false): string
    {
        $path = rawurldecode($path);
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#\.\.+#', '', $path) ?? '';
        $path = preg_replace('#/+#', '/', $path) ?? '';
        $path = trim($path, '/');

        $root = trim((string) config('zfy.editor.media.storage_root', 'media'), '/');
        if ($stripLegacyRoot && $root !== '' && str_starts_with($path, $root.'/')) {
            $path = substr($path, strlen($root) + 1);
        }

        return $path;
    }

    private function publicUrl(string $relativePath): string
    {
        $root = trim((string) config('zfy.editor.media.storage_root', 'media'), '/');

        return url('/'.$root.'/'.$relativePath);
    }

    private function mediaDisk(): string
    {
        return (string) config('zfy.editor.media.disk', 'media');
    }
}
