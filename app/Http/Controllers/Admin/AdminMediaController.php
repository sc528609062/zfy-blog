<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Media;
use App\Services\SiteSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminMediaController extends Controller
{
    private const MEDIA_TYPES = ['all', 'image', 'video', 'audio', 'archive', 'file'];

    private const MEDIA_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg',
        'mp4', 'webm', 'mov', 'm4v', 'avi', 'mkv', 'm3u8',
        'mp3', 'wav', 'ogg', 'oga', 'm4a', 'aac', 'flac',
        'zip', 'rar', '7z', 'tar', 'gz',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'json',
    ];

    public function index(Request $request): JsonResponse
    {
        $this->authorizeMedia($request);

        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'type' => ['nullable', 'string', Rule::in(self::MEDIA_TYPES)],
        ]);

        $type = (string) ($data['type'] ?? 'all');
        $query = trim((string) ($data['q'] ?? ''));
        $perPage = (int) ($data['per_page'] ?? $this->mediaLibraryPerPage());

        $builder = Media::query()
            ->where('disk', '!=', 'local')
            ->whereNotIn('id', Attachment::where('role', 'download')->whereNotNull('media_id')->select('media_id'))
            ->where(fn ($query) => $query->whereNull('metadata->private')->orWhere('metadata->private', false))
            ->when($type !== 'all', fn ($mediaQuery) => $mediaQuery->where('type', $type))
            ->when($query !== '', function ($mediaQuery) use ($query) {
                $mediaQuery->where(function ($nested) use ($query) {
                    $nested->where('name', 'like', "%{$query}%")
                        ->orWhere('path', 'like', "%{$query}%")
                        ->orWhere('mime', 'like', "%{$query}%");
                });
            })
            ->latest();

        $paginator = $builder->paginate($perPage)->withQueryString();
        $items = $paginator->getCollection()->map(fn (Media $media) => $this->mediaPayload($media))->values();

        return response()->json([
            'items' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeMedia($request);

        $data = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:'.implode(',', self::MEDIA_EXTENSIONS),
                'max:'.$this->mediaUploadMaxKb(),
            ],
            'directory' => ['nullable', 'string', 'max:120'],
        ]);

        $file = $request->file('file');
        abort_unless($file instanceof UploadedFile, 422, '请选择有效媒体文件');

        $directory = $this->normalizeDirectory((string) ($data['directory'] ?? $this->mediaDefaultDirectory()));
        $relativeDirectory = trim($directory.'/'.now()->format('Y/m'), '/');
        $storageDirectory = trim($this->mediaStorageRoot().'/'.$relativeDirectory, '/');
        $extension = $this->mediaExtension($file);
        $type = $this->mediaType($file);
        $filename = $this->mediaFilename($file, $relativeDirectory, $extension);
        zfy_validate('zfy_media_uploading', $file, $request->user());
        $path = $file->storePubliclyAs($relativeDirectory, $filename, $this->mediaDisk());

        abort_unless(is_string($path) && $path !== '', 422, '媒体上传失败');

        try {
            $media = DB::transaction(fn () => Media::create([
                'folder_id' => null,
                'user_id' => $request->user()?->id,
                'disk' => $this->mediaDisk(),
                'type' => $type,
                'name' => pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME) ?: pathinfo($filename, PATHINFO_FILENAME),
                'path' => $path,
                'mime' => $file->getMimeType(),
                'size' => $file->getSize() ?: 0,
                'metadata' => [
                    'directory' => $directory,
                    'storage_directory' => $storageDirectory,
                    'original_name' => $file->getClientOriginalName(),
                    'stored_name' => $filename,
                    'url' => $this->mediaPublicUrl($path),
                ],
            ]));
        } catch (\Throwable $exception) {
            Storage::disk($this->mediaDisk())->delete($path);
            throw $exception;
        }
        zfy_after_commit('zfy_media_uploaded', $media, $request->user());

        return response()->json([
            'message' => '媒体已上传',
            'media' => $this->mediaPayload($media),
        ], 201);
    }

    public function destroy(Request $request, Media $media): JsonResponse
    {
        $this->authorizeMedia($request);

        abort_if(Attachment::where('media_id', $media->id)->exists(), 422, '请先移除内容中的附件关联。');
        zfy_validate('zfy_media_deleting', $media, $request->user());
        $disk = Storage::disk($media->disk ?: $this->mediaDisk());
        $paths = collect([
            trim((string) $media->path, '/'),
            $this->mediaRelativePath((string) $media->path),
        ])->filter()->unique()->values();

        foreach ($paths as $path) {
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }

        $media->delete();
        zfy_after_commit('zfy_media_deleted', $media, $request->user());

        return response()->json([
            'message' => '媒体已永久删除',
        ]);
    }

    private function authorizeMedia(Request $request): void
    {
        $user = $request->user();

        abort_unless($user, 403);
        abort_unless($user->can('manage contents') || $user->can('publish contents') || $user->can('manage system'), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function mediaPayload(Media $media): array
    {
        $url = data_get($media->metadata, 'private') ? null : $this->mediaPublicUrl($media->path, $media->disk);

        return [
            'id' => $media->id,
            'name' => $media->name,
            'path' => $media->path,
            'url' => $url,
            'thumb_url' => $media->type === 'image' ? $url : null,
            'disk' => $media->disk,
            'type' => $media->type,
            'mime' => $media->mime,
            'size' => (int) $media->size,
            'directory' => data_get($media->metadata, 'directory', ''),
            'storage_directory' => data_get($media->metadata, 'storage_directory', ''),
            'created_at' => optional($media->created_at)->toISOString(),
        ];
    }

    private function normalizeDirectory(string $directory): string
    {
        $directory = trim(str_replace('\\', '/', $directory));
        $directory = preg_replace('#\.\.+#', '', $directory) ?? '';
        $directory = preg_replace('#[<>:"|?*\x00-\x1F]#u', '', $directory) ?? '';
        $directory = preg_replace('#/+#', '/', $directory) ?? '';
        $directory = trim($directory, '/');

        return $directory !== '' ? $directory : $this->mediaDefaultDirectory();
    }

    private function mediaExtension(UploadedFile $file): string
    {
        $clientExtension = strtolower($file->getClientOriginalExtension() ?: '');
        $guessedExtension = strtolower($file->guessExtension() ?: $file->extension() ?: '');

        foreach ([$clientExtension, $guessedExtension] as $extension) {
            if (in_array($extension, self::MEDIA_EXTENSIONS, true)) {
                return $extension;
            }
        }

        return 'bin';
    }

    private function mediaFilename(UploadedFile $file, string $relativeDirectory, string $fallbackExtension): string
    {
        $filename = $this->normalizeFilename((string) $file->getClientOriginalName());

        if ($filename === '') {
            $filename = 'media.'.$fallbackExtension;
        }

        $filename = $this->ensureAllowedFilenameExtension($filename, $fallbackExtension);

        return $this->uniqueMediaFilename($relativeDirectory, $filename);
    }

    private function normalizeFilename(string $filename): string
    {
        $filename = basename(str_replace('\\', '/', trim($filename)));
        $filename = preg_replace('#[<>:"|?*\x00-\x1F]#u', '', $filename) ?? '';
        $filename = preg_replace('#/+#', '', $filename) ?? '';
        $filename = trim($filename, " \t\n\r\0\x0B.");

        return $filename;
    }

    private function ensureAllowedFilenameExtension(string $filename, string $fallbackExtension): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($extension, self::MEDIA_EXTENSIONS, true)) {
            return $filename;
        }

        $base = trim(pathinfo($filename, PATHINFO_FILENAME), " \t\n\r\0\x0B.");

        return ($base !== '' ? $base : 'media').'.'.$fallbackExtension;
    }

    private function uniqueMediaFilename(string $relativeDirectory, string $filename): string
    {
        $disk = Storage::disk($this->mediaDisk());

        if (! $disk->exists(trim($relativeDirectory.'/'.$filename, '/'))) {
            return $filename;
        }

        $base = pathinfo($filename, PATHINFO_FILENAME);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        for ($index = 1; $index < 1000; $index++) {
            $candidate = $base.' ('.$index.')'.($extension !== '' ? '.'.$extension : '');

            if (! $disk->exists(trim($relativeDirectory.'/'.$candidate, '/'))) {
                return $candidate;
            }
        }

        return $base.'-'.Str::ulid().($extension !== '' ? '.'.$extension : '');
    }

    private function mediaType(UploadedFile $file): string
    {
        $mime = strtolower((string) $file->getMimeType());
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: $file->guessExtension() ?: '');

        if (Str::startsWith($mime, 'image/') || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg'], true)) {
            return 'image';
        }

        if (Str::startsWith($mime, 'video/') || in_array($extension, ['mp4', 'webm', 'mov', 'm4v', 'avi', 'mkv', 'm3u8'], true)) {
            return 'video';
        }

        if (Str::startsWith($mime, 'audio/') || in_array($extension, ['mp3', 'wav', 'ogg', 'oga', 'm4a', 'aac', 'flac'], true)) {
            return 'audio';
        }

        if (in_array($extension, ['zip', 'rar', '7z', 'tar', 'gz'], true)
            || in_array($mime, ['application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed', 'application/gzip'], true)) {
            return 'archive';
        }

        return 'file';
    }

    /**
     * @return array<string, mixed>
     */
    private function mediaConfig(): array
    {
        return config('zfy.editor.media', []);
    }

    private function mediaStorageRoot(): string
    {
        return trim((string) ($this->mediaConfig()['storage_root'] ?? 'media'), '/') ?: 'media';
    }

    private function mediaDisk(): string
    {
        return (string) ($this->mediaConfig()['disk'] ?? 'media');
    }

    private function mediaPublicUrl(string $path, ?string $disk = null): string
    {
        if (($disk ?? $this->mediaDisk()) !== 'media') {
            return Storage::disk($disk ?? $this->mediaDisk())->url($path);
        }
        $relativePath = implode('/', array_map('rawurlencode', explode('/', $this->mediaRelativePath($path))));

        return url('/'.$this->mediaStorageRoot().'/'.$relativePath);
    }

    private function mediaRelativePath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path), '/');
        $root = trim($this->mediaStorageRoot(), '/');

        if ($root !== '' && Str::startsWith($path, $root.'/')) {
            return substr($path, strlen($root) + 1);
        }

        return $path;
    }

    private function mediaDefaultDirectory(): string
    {
        return (string) ($this->mediaConfig()['default_directory'] ?? 'editor/images');
    }

    private function mediaLibraryPerPage(): int
    {
        return (int) ($this->mediaConfig()['library_per_page'] ?? 24);
    }

    private function mediaUploadMaxKb(): int
    {
        return (int) app(SiteSettings::class)->get('media.max_upload_mb', ($this->mediaConfig()['upload_max_kb'] ?? 20480) / 1024) * 1024;
    }
}
