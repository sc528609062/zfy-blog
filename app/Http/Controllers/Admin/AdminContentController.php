<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\ContentRevision;
use App\Models\Tag;
use App\Services\ContentMarkdownRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminContentController extends Controller
{
    public function __construct(private readonly ContentMarkdownRenderer $renderer) {}

    public function store(Request $request): JsonResponse
    {
        $this->authorizeWriting($request);

        $payload = $this->validatedPayload($request);
        $content = $this->persistContent($request, $payload);

        return $this->contentResponse($content, '内容已保存');
    }

    public function update(Request $request, Content $content): JsonResponse
    {
        $this->authorizeWriting($request, $content);

        $payload = $this->validatedPayload($request);
        $content = $this->persistContent($request, $payload, $content);

        return $this->contentResponse($content, '内容已更新');
    }

    public function settings(Request $request, Content $content): JsonResponse
    {
        $this->authorizeWriting($request, $content);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'type' => ['required', 'string', Rule::in(config('zfy.content_types', ['post', 'images', 'files', 'page']))],
            'status' => ['required', 'string', Rule::in(['draft', 'published'])],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'tags' => ['nullable'],
            'topic' => ['nullable', 'string', 'max:120'],
            'cover_url' => ['nullable', 'string', 'max:2048'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
        ]);

        $wasPublished = $content->status === 'published';
        $isPublished = $data['status'] === 'published';
        $blockJson = Arr::wrap($content->block_json);
        $topic = trim((string) ($data['topic'] ?? ''));

        if ($topic !== '') {
            $blockJson['topic'] = $topic;
        } else {
            unset($blockJson['topic']);
        }

        $attributes = [
            'title' => $data['title'],
            'type' => $data['type'],
            'status' => $data['status'],
            'category_id' => $data['category_id'] ?? null,
            'cover_url' => $data['cover_url'] ?? null,
            'excerpt' => $data['excerpt'] ?? null,
            'block_json' => $blockJson,
            'published_at' => $isPublished ? ($content->published_at ?: now()) : null,
        ];

        zfy_emit('zfy_content_saving', $attributes, $content, $request->user());

        $content->update($attributes);
        $this->syncTags($content, $data['tags'] ?? null);

        zfy_emit('zfy_content_saved', $content, $attributes, $request->user());

        if ($isPublished && ! $wasPublished) {
            zfy_emit('zfy_content_published', $content, $attributes, $request->user());
        }

        return $this->contentResponse($content->refresh(), '快捷设置已保存');
    }

    public function status(Request $request, Content $content): JsonResponse
    {
        $this->authorizeWriting($request, $content);

        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(['draft', 'published'])],
        ]);

        $wasPublished = $content->status === 'published';
        $isPublished = $data['status'] === 'published';
        $attributes = [
            'status' => $data['status'],
            'published_at' => $isPublished ? ($content->published_at ?: now()) : null,
        ];

        zfy_emit('zfy_content_saving', $attributes, $content, $request->user());

        $content->update($attributes);

        zfy_emit('zfy_content_saved', $content, $attributes, $request->user());

        if ($isPublished && ! $wasPublished) {
            zfy_emit('zfy_content_published', $content, $attributes, $request->user());
        }

        return $this->contentResponse($content->refresh(), $isPublished ? '内容已发布' : '内容已设为草稿');
    }

    public function destroy(Request $request, Content $content): JsonResponse
    {
        $this->authorizeWriting($request, $content);

        $content->delete();

        return response()->json([
            'message' => '内容已删除',
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $this->authorizeWriting($request);

        $data = $request->validate([
            'markdown' => ['nullable', 'string'],
        ]);

        return response()->json([
            'html' => $this->renderer->render($data['markdown'] ?? '', $this->canUseRawHtml($request)),
        ]);
    }

    public function autosaveState(Request $request): JsonResponse
    {
        $this->authorizeWriting($request);
        $data = $request->validate([
            'content_id' => ['nullable', 'integer', 'exists:contents,id'],
            'draft_key' => ['nullable', 'uuid'],
        ]);
        $content = isset($data['content_id']) ? Content::findOrFail($data['content_id']) : null;

        if ($content) {
            $this->authorizeWriting($request, $content);
        }

        $revision = ContentRevision::query()
            ->where('user_id', $request->user()?->id)
            ->where('kind', 'autosave')
            ->when($content, fn ($query) => $query->where('content_id', $content->id))
            ->when(! $content && ! empty($data['draft_key']), fn ($query) => $query->whereNull('content_id')->where('draft_key', $data['draft_key']))
            ->when(! $content && empty($data['draft_key']), fn ($query) => $query->whereNull('content_id'))
            ->latest('updated_at')
            ->first();

        return response()->json([
            'autosave' => $revision ? $this->revisionPayload($revision, true) : null,
        ]);
    }

    public function autosave(Request $request): JsonResponse
    {
        $this->authorizeWriting($request);
        $data = $this->validatedAutosavePayload($request);
        $content = isset($data['content_id']) ? Content::findOrFail($data['content_id']) : null;

        if ($content) {
            $this->authorizeWriting($request, $content);
        }

        $query = ContentRevision::query()
            ->where('user_id', $request->user()?->id)
            ->where('kind', 'autosave');

        if ($content) {
            $query->where('content_id', $content->id);
        } else {
            $query->whereNull('content_id')->where('draft_key', $data['draft_key']);
        }

        $revision = $query->first() ?? new ContentRevision;
        $snapshot = Arr::only($data, [
            'title', 'type', 'status', 'category_id', 'tags', 'cover_url', 'excerpt', 'markdown_cache', 'block_json',
        ]);
        $baseUpdatedAt = isset($data['base_updated_at']) ? Carbon::parse($data['base_updated_at']) : null;
        $conflict = $content && $baseUpdatedAt && ! $content->updated_at?->equalTo($baseUpdatedAt);

        $revision->fill([
            'content_id' => $content?->id,
            'user_id' => $request->user()?->id,
            'kind' => 'autosave',
            'draft_key' => $data['draft_key'],
            'snapshot' => $snapshot,
            'source_updated_at' => $content?->updated_at,
        ])->save();

        return response()->json([
            'message' => '已自动保存',
            'conflict' => (bool) $conflict,
            'autosave' => $this->revisionPayload($revision->refresh(), false),
        ]);
    }

    public function discardAutosave(Request $request, ContentRevision $revision): JsonResponse
    {
        $this->authorizeRevision($request, $revision);
        abort_unless($revision->kind === 'autosave', 404);
        $revision->delete();

        return response()->json(['message' => '自动保存已丢弃']);
    }

    public function revisions(Request $request, Content $content): JsonResponse
    {
        $this->authorizeWriting($request, $content);
        $revisions = $content->revisions()
            ->with('user:id,name,username')
            ->where('kind', 'revision')
            ->latest()
            ->take(50)
            ->get()
            ->map(fn (ContentRevision $revision) => $this->revisionPayload($revision, false))
            ->values();

        return response()->json(['revisions' => $revisions]);
    }

    public function revision(Request $request, Content $content, ContentRevision $revision): JsonResponse
    {
        $this->authorizeWriting($request, $content);
        abort_unless($revision->content_id === $content->id && $revision->kind === 'revision', 404);

        return response()->json(['revision' => $this->revisionPayload($revision, true)]);
    }

    public function restoreRevision(Request $request, Content $content, ContentRevision $revision): JsonResponse
    {
        $this->authorizeWriting($request, $content);
        abort_unless($revision->content_id === $content->id && $revision->kind === 'revision', 404);
        $this->recordRevision($content, $request->user()?->id);

        $snapshot = Arr::wrap($revision->snapshot);
        $payload = [
            ...$this->contentSnapshot($content),
            ...Arr::only($snapshot, [
                'title', 'type', 'category_id', 'tags', 'cover_url', 'excerpt', 'markdown_cache', 'block_json',
            ]),
            'status' => $content->status,
        ];
        $content = $this->persistContent($request, $payload, $content, false);

        return $this->contentResponse($content, '历史版本已恢复');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'type' => ['required', 'string', Rule::in(config('zfy.content_types', ['post', 'images', 'files', 'page']))],
            'status' => ['required', 'string', Rule::in(['draft', 'published'])],
            'slug' => ['nullable', 'string', 'max:180'],
            'subtitle' => ['nullable', 'string', 'max:220'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'cover_url' => ['nullable', 'string', 'max:2048'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'tags' => ['nullable'],
            'markdown_cache' => ['nullable', 'string'],
            'block_json' => ['nullable', 'array'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function persistContent(Request $request, array $payload, ?Content $content = null, bool $recordRevision = true): Content
    {
        $user = $request->user();
        $markdown = (string) ($payload['markdown_cache'] ?? '');
        $isPublished = $payload['status'] === 'published';
        $wasPublished = $content?->status === 'published';
        $contentId = $content?->id;
        $allowRawHtml = $this->canUseRawHtml($request);

        if ($content && $recordRevision) {
            $this->recordRevision($content, $user?->id);
        }

        $attributes = [
            'author_id' => $content?->author_id ?: $user?->id,
            'category_id' => $payload['category_id'] ?? null,
            'type' => $payload['type'],
            'status' => $payload['status'],
            'title' => $payload['title'],
            'slug' => $this->uniqueSlug($payload['slug'] ?? $payload['title'], $contentId),
            'subtitle' => $payload['subtitle'] ?? null,
            'excerpt' => $payload['excerpt'] ?? null,
            'cover_url' => $payload['cover_url'] ?? null,
            'markdown_cache' => $markdown,
            'rendered_html' => $this->renderer->render($markdown, $allowRawHtml),
            'block_json' => array_replace_recursive([
                'mode' => 'markdown',
                'editor' => 'zfy-markdown',
                'version' => 1,
                'shortcodes' => $this->renderer->extractShortcodes($markdown),
                'raw_html' => $this->renderer->containsMarkedHtmlBlock($markdown),
            ], Arr::wrap($payload['block_json'] ?? [])),
            'published_at' => $isPublished ? ($content?->published_at ?: now()) : null,
        ];

        $attributes = zfy_apply('zfy_content_payload', $attributes, $payload, $request, $content);
        abort_unless(is_array($attributes), 422, '内容过滤器必须返回数组');

        zfy_emit('zfy_content_saving', $attributes, $content, $user);

        if ($content) {
            $content->update($attributes);
        } else {
            $content = Content::create($attributes);
        }

        $this->syncTags($content, $payload['tags'] ?? null);

        zfy_emit('zfy_content_saved', $content, $attributes, $user);

        if ($isPublished && ! $wasPublished) {
            zfy_emit('zfy_content_published', $content, $attributes, $user);
        }

        ContentRevision::query()
            ->where('kind', 'autosave')
            ->where('user_id', $user?->id)
            ->where(function ($query) use ($content, $request) {
                $query->where('content_id', $content->id);
                if ($request->filled('draft_key')) {
                    $query->orWhere('draft_key', $request->string('draft_key')->toString());
                }
            })
            ->delete();

        return $content->refresh();
    }

    private function validatedAutosavePayload(Request $request): array
    {
        return $request->validate([
            'content_id' => ['nullable', 'integer', 'exists:contents,id'],
            'draft_key' => ['required', 'uuid'],
            'base_updated_at' => ['nullable', 'date'],
            'title' => ['nullable', 'string', 'max:180'],
            'type' => ['required', 'string', Rule::in(config('zfy.content_types', ['post', 'images', 'files', 'page']))],
            'status' => ['required', 'string', Rule::in(['draft', 'published'])],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'tags' => ['nullable'],
            'cover_url' => ['nullable', 'string', 'max:2048'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'markdown_cache' => ['nullable', 'string'],
            'block_json' => ['nullable', 'array'],
        ]);
    }

    private function recordRevision(Content $content, ?int $userId): void
    {
        $snapshot = $this->contentSnapshot($content);
        $latest = $content->revisions()->where('kind', 'revision')->latest()->first();

        if ($latest && $latest->snapshot === $snapshot) {
            return;
        }

        $content->revisions()->create([
            'user_id' => $userId,
            'kind' => 'revision',
            'snapshot' => $snapshot,
            'source_updated_at' => $content->updated_at,
        ]);

        $staleIds = $content->revisions()
            ->where('kind', 'revision')
            ->latest()
            ->skip(50)
            ->take(500)
            ->pluck('id');

        if ($staleIds->isNotEmpty()) {
            ContentRevision::whereKey($staleIds)->delete();
        }
    }

    private function contentSnapshot(Content $content): array
    {
        return [
            'title' => $content->title,
            'type' => $content->type,
            'status' => $content->status,
            'category_id' => $content->category_id,
            'tags' => $content->tags()->pluck('name')->implode(', '),
            'cover_url' => $content->cover_url,
            'excerpt' => $content->excerpt,
            'markdown_cache' => $content->markdown_cache,
            'block_json' => Arr::wrap($content->block_json),
        ];
    }

    private function revisionPayload(ContentRevision $revision, bool $includeSnapshot): array
    {
        $snapshot = Arr::wrap($revision->snapshot);
        $payload = [
            'id' => $revision->id,
            'content_id' => $revision->content_id,
            'kind' => $revision->kind,
            'draft_key' => $revision->draft_key,
            'title' => (string) ($snapshot['title'] ?? '未命名版本'),
            'summary' => Str::limit(trim((string) ($snapshot['markdown_cache'] ?? '')), 100),
            'user' => $revision->relationLoaded('user') ? $revision->user?->only(['id', 'name', 'username']) : null,
            'source_updated_at' => optional($revision->source_updated_at)->toISOString(),
            'created_at' => optional($revision->created_at)->toISOString(),
            'updated_at' => optional($revision->updated_at)->toISOString(),
        ];

        if ($includeSnapshot) {
            $payload['snapshot'] = $snapshot;
        }

        return $payload;
    }

    private function authorizeRevision(Request $request, ContentRevision $revision): void
    {
        $this->authorizeWriting($request, $revision->content);
        abort_unless($revision->user_id === $request->user()?->id || $request->user()?->can('manage contents'), 403);
    }

    private function authorizeWriting(Request $request, ?Content $content = null): void
    {
        $user = $request->user();

        abort_unless($user, 403);
        abort_unless($user->can('publish contents') || $user->can('manage contents'), 403);

        if ($content && $content->author_id !== $user->id) {
            abort_unless($user->can('manage contents'), 403);
        }
    }

    private function canUseRawHtml(Request $request): bool
    {
        $user = $request->user();

        return (bool) ($user?->can('manage system') || $user?->hasAnyRole(['SUPER_ADMIN', 'ADMIN']));
    }

    private function uniqueSlug(?string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug((string) $source) ?: 'post';
        $slug = $base;
        $index = 2;

        while (
            Content::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$index}";
            $index++;
        }

        return $slug;
    }

    private function syncTags(Content $content, mixed $tags): void
    {
        $names = collect(is_array($tags) ? $tags : explode(',', (string) $tags))
            ->map(fn (mixed $tag) => trim((string) $tag))
            ->filter()
            ->unique()
            ->take(20);

        $tagIds = $names->map(function (string $name) {
            $slug = Str::slug($name) ?: Str::random(8);

            return Tag::firstOrCreate(['slug' => $slug], ['name' => $name])->id;
        });

        $content->tags()->sync($tagIds->all());
    }

    private function contentResponse(Content $content, string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'content' => [
                'id' => $content->id,
                'title' => $content->title,
                'slug' => $content->slug,
                'status' => $content->status,
                'type' => $content->type,
                'published_at' => optional($content->published_at)->toISOString(),
                'updated_at' => optional($content->updated_at)->toISOString(),
                'show_url' => route('contents.show', $content->slug, false),
            ],
        ]);
    }
}
