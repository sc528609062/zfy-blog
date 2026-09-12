<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Content;
use App\Services\GalleryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    public function image(Request $request, Attachment $attachment, GalleryService $gallery)
    {
        abort_unless($attachment->role === 'gallery', 404);
        $content = Content::findOrFail($attachment->content_id);
        abort_unless($gallery->allowed($attachment, $content, $request->user() ?? $request->user('sanctum')), 403);
        $media = $attachment->media;
        $path = data_get($media?->metadata, 'preview_path');
        abort_unless($path && Storage::disk($media->disk)->exists($path), 404);

        return Storage::disk($media->disk)->response($path, 'preview.jpg', ['Content-Type' => 'image/jpeg', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
