<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Content;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class DownloadService
{
    public function authorize(Request $request, Content $content): array
    {
        abort_unless(Content::published()->whereKey($content->id)->exists(), 404);
        abort_if($request->user()?->is_banned, 403);
        $allowed = app(OrderService::class)->userCanAccessContent($content, $request->user());
        $attachment = $request->filled('attachment_id')
            ? $content->attachments()->whereNotNull('media_id')->findOrFail($request->integer('attachment_id'))
            : $content->attachments()->whereNotNull('media_id')->where('role', '!=', 'gallery')->first();
        $media = $attachment ? Media::find($attachment->media_id) : null;
        if ($attachment?->role === 'gallery') {
            $allowed = $allowed && data_get($attachment->meta, 'original_download', false) && app(GalleryService::class)->allowed($attachment, $content, $request->user());
        }
        if (! $allowed) {
            $this->log($request, $content, null, 'denied');
        }
        abort_unless($allowed, 403, '当前账号没有下载权限。');
        abort_unless($media && Storage::disk($media->disk)->exists($media->path), 404, '下载文件不存在。');

        $token = bin2hex(random_bytes(32));
        DB::table('download_grants')->insert(['token_hash' => hash('sha256', $token), 'user_id' => $request->user()->id, 'attachment_id' => $attachment->id, 'expires_at' => now()->addMinutes(10)]);

        return [
            'content_id' => $content->id,
            'download_url' => URL::temporarySignedRoute($request->is('api/*') ? 'api.v1.downloads.file' : 'downloads.file', now()->addMinutes(10), ['attachment' => $attachment->id, 'user' => $request->user()->id, 'grant' => $token]),
        ];
    }

    public function file(Request $request, Attachment $attachment)
    {
        abort_if($request->user()?->is_banned, 403);
        abort_unless($request->hasValidSignature() && (int) $request->query('user') === $request->user()->id, 403);
        $content = Content::published()->findOrFail($attachment->content_id);
        abort_unless(app(OrderService::class)->userCanAccessContent($content, $request->user()), 403);
        if ($attachment->role === 'gallery') {
            abort_unless(data_get($attachment->meta, 'original_download', false) && app(GalleryService::class)->allowed($attachment, $content, $request->user()), 403);
        }
        $media = Media::findOrFail($attachment->media_id);
        $disk = Storage::disk($media->disk);
        abort_unless($disk->exists($media->path), 404);
        DB::transaction(function () use ($request, $content, $media, $attachment) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $grant = DB::table('download_grants')->where('token_hash', hash('sha256', (string) $request->query('grant')))->where('attachment_id', $attachment->id)->where('user_id', $request->user()->id)->whereNull('consumed_at')->where('expires_at', '>', now())->lockForUpdate()->first();
            abort_unless($grant, 403, '下载凭证已使用或过期。');
            $limit = (int) data_get($content->access_rules, 'downloads_per_day', 0);
            if ($limit > 0) {
                $used = DB::table('download_logs')->where('user_id', $request->user()->id)->where('content_id', $content->id)->where('status', 'allowed')->where('created_at', '>=', today())->count();
                abort_if($used >= $limit, 429, '已达到今天的下载次数上限。');
            }
            $total = (int) data_get($content->access_rules, 'downloads_total', 0);
            if ($total > 0) {
                abort_if(DB::table('download_logs')->where('user_id', $request->user()->id)->where('content_id', $content->id)->where('status', 'allowed')->count() >= $total, 429, '已达到下载次数上限。');
            }
            zfy_validate('zfy_download_authorizing', $content, $media, $request->user());
            $this->log($request, $content, $media->id, 'allowed');
            $content->increment('download_count');
            DB::table('download_grants')->where('id', $grant->id)->update(['consumed_at' => now()]);
            zfy_after_commit('zfy_download_created', $content, $media, $request->user());
        });

        return $disk->download($media->path, basename($media->path));
    }

    private function log(Request $request, Content $content, ?int $mediaId, string $status): void
    {
        DB::table('download_logs')->insert([
            'content_id' => $content->id, 'user_id' => $request->user()->id,
            'media_id' => $mediaId, 'status' => $status, 'ip_address' => $request->ip(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
