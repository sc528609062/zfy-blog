<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Content;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GalleryService
{
    public function allowed(Attachment $attachment, Content $content, ?User $viewer): bool
    {
        if ($viewer && ! $viewer->is_banned && $viewer->can('manage contents')) {
            return true;
        }
        if (! app(OrderService::class)->userCanAccessContent($content, $viewer)) {
            return false;
        }
        $rule = data_get($attachment->meta, 'visibility', 'public');

        return $rule === 'public' || app(ContentVisibility::class)->allows($rule, $content, $viewer);
    }

    public function items(Content $content, ?User $viewer, bool $admin = false): array
    {
        if (! $content->exists) {
            return [];
        }

        return $content->attachments()->with('media')->where('role', 'gallery')->get()
            ->sortBy(fn ($item) => (int) data_get($item->meta, 'sort_order', 0))
            ->filter(fn ($item) => $admin || $this->allowed($item, $content, $viewer))
            ->map(fn ($item) => ['id' => $item->id, 'title' => data_get($item->meta, 'title', ''), 'caption' => data_get($item->meta, 'caption', ''), 'copyright' => data_get($item->meta, 'copyright', ''), 'visibility' => data_get($item->meta, 'visibility', 'public'), 'original_download' => (bool) data_get($item->meta, 'original_download', false), 'url' => route('gallery.image', $item), 'width' => data_get($item->media?->metadata, 'width'), 'height' => data_get($item->media?->metadata, 'height')])->values()->all();
    }

    public function upload(Content $content, User $user, UploadedFile $file): Attachment
    {
        $size = getimagesize($file->getRealPath());
        abort_unless($size && $size[0] * $size[1] <= 25000000, 422, '图片最多 2500 万像素。');
        abort_unless(extension_loaded('gd'), 422, '服务器需要启用 GD 图片扩展。');
        zfy_validate('zfy_media_uploading', $file, $user);
        $image = imagecreatefromstring(file_get_contents($file->getRealPath()));
        abort_unless($image, 422, '图片解码失败。');
        $ratio = min(1, 1600 / max($size[0], $size[1]));
        $preview = imagecreatetruecolor(max(1, (int) ($size[0] * $ratio)), max(1, (int) ($size[1] * $ratio)));
        imagefill($preview, 0, 0, imagecolorallocate($preview, 255, 255, 255));
        imagecopyresampled($preview, $image, 0, 0, 0, 0, imagesx($preview), imagesy($preview), $size[0], $size[1]);
        ob_start();
        imagejpeg($preview, null, 82);
        $bytes = ob_get_clean();
        imagedestroy($preview);
        imagedestroy($image);
        $disk = config('filesystems.downloads', 'local');
        abort_unless(in_array($disk, ['local', 's3', 'oss', 'cos'], true), 422);
        $path = $file->store('gallery/originals', ['disk' => $disk, 'visibility' => 'private']);
        abort_unless($path, 422, '上传失败。');
        $previewPath = 'gallery/previews/'.bin2hex(random_bytes(16)).'.jpg';
        try {
            abort_unless(Storage::disk($disk)->put($previewPath, $bytes, ['visibility' => 'private']), 422, '预览图保存失败。');

            return DB::transaction(function () use ($content, $user, $file, $disk, $path, $previewPath, $size) {
                Content::whereKey($content->id)->lockForUpdate()->firstOrFail();
                abort_if($content->attachments()->where('role', 'gallery')->count() >= 100, 422, '每个图集最多 100 张图片。');
                $media = Media::create(['user_id' => $user->id, 'disk' => $disk, 'type' => 'image', 'name' => $file->getClientOriginalName(), 'path' => $path, 'mime' => $file->getMimeType(), 'size' => $file->getSize(), 'metadata' => ['private' => true, 'preview_path' => $previewPath, 'width' => $size[0], 'height' => $size[1]]]);
                $attachment = $content->attachments()->create(['media_id' => $media->id, 'role' => 'gallery', 'meta' => ['title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 'sort_order' => $content->attachments()->where('role', 'gallery')->count(), 'visibility' => 'public', 'original_download' => false]]);
                zfy_after_commit('zfy_media_created', $media);

                return $attachment;
            });
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete([$path, $previewPath]);
            throw $exception;
        }
    }

    public function render(Content $content, ?User $viewer): string
    {
        $items = $this->items($content, $viewer);

        return $items ? view('themes.shared.partials.gallery', compact('content', 'items'))->render() : '';
    }
}
