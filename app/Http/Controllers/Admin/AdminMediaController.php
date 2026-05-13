<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminMediaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeMedia($request);

        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'type' => ['nullable', 'string', Rule::in(['all', 'image'])],
        ]);

        $type = (string) ($data['type'] ?? 'image');
        $query = trim((string) ($data['q'] ?? ''));
        $perPage = (int) ($data['per_page'] ?? $this->mediaLibraryPerPage());

        $builder = Media::query()
            ->when($type !== 'all', fn ($mediaQuery) => $mediaQuery->where('type', 'image'))
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
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,avif,svg', 'max:'.$this->mediaUploadMaxKb()],
            'directory' => ['nullable', 'string', 'max:120'],
        ]);

        $file = $request->file('file');
        abort_unless($file instanceof UploadedFile, 422, '请选择有效图片');

        $directory = $this->normalizeDirectory((string) ($data['directory'] ?? $this->mediaDefaultDirectory()));
        $relativeDirectory = trim($directory.'/'.now()->format('Y/m'), '/');
        $storageDirectory = trim($this->mediaStorageRoot().'/'.$relativeDirectory, '/');
        $extension = $file->guessExtension() ?: $file->extension() ?: 'bin';
        $filename = (string) Str::ulid().'.'.$extension;
        $path = $file->storePubliclyAs($relativeDirectory, $filename, $this->mediaDisk());

        abort_unless(is_string($path) && $path !== '', 422, '图片上传失败');

        $media = Media::create([
            'folder_id' => null,
            'user_id' => $request->user()?->id,
            'disk' => $this->mediaDisk(),
            'type' => 'image',
            'name' => pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME) ?: pathinfo($filename, PATHINFO_FILENAME),
            'path' => $path,
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize() ?: 0,
            'metadata' => [
                'directory' => $directory,
                'storage_directory' => $storageDirectory,
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => $filename,
                'url' => $this->mediaPublicUrl($path),
            ],
        ]);

        return response()->json([
            'message' => '图片已上传',
            'media' => $this->mediaPayload($media),
        ], 201);
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
        $url = $this->mediaPublicUrl($media->path);

        return [
            'id' => $media->id,
            'name' => $media->name,
            'path' => $media->path,
            'url' => $url,
            'thumb_url' => $url,
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

    private function mediaPublicUrl(string $path): string
    {
        return url('/'.$this->mediaStorageRoot().'/'.$this->mediaRelativePath($path));
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
        return (int) ($this->mediaConfig()['upload_max_kb'] ?? 20480);
    }
}
