<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\DeliverSiteNotification;
use App\Models\Content;
use App\Models\ContentRevision;
use App\Models\Tag;
use App\Services\ContentDocumentService;
use App\Services\ContentMarkdownRenderer;
use App\Services\ContentModeration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminContentController extends Controller
{
    public function __construct(private readonly ContentMarkdownRenderer $renderer) {}

    public function store(Request $request): JsonResponse
    {
        $this->authorizeWriting($request);

        $payload = $this->validatedPayload($request);
        $content = DB::transaction(fn () => $this->persistContent($request, $payload));

        return $this->contentResponse($content, '内容已保存');
    }

    public function update(Request $request, Content $content): JsonResponse
    {
        $this->authorizeWriting($request, $content);

        $payload = $this->validatedPayload($request);
        $content = DB::transaction(fn () => $this->persistContent($request, $payload, Content::whereKey($content->id)->lockForUpdate()->firstOrFail()));

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

        $this->saveQuickChange($request, $content, $attributes, $data['tags'] ?? null, true);

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

        $this->saveQuickChange($request, $content, $attributes);

        return $this->contentResponse($content->refresh(), $isPublished ? '内容已发布' : '内容已设为草稿');
    }

    public function destroy(Request $request, Content $content): JsonResponse
    {
        $this->authorizeWriting($request, $content);

        DB::transaction(function () use ($content, $request) {
            $content = Content::whereKey($content->id)->lockForUpdate()->firstOrFail();
            zfy_validate('zfy_content_deleting', $content, $request->user());
            $content->delete();
            zfy_after_commit('zfy_content_deleted', $content, $request->user());
        });

        return response()->json([
            'message' => '内容已删除',
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $this->authorizeWriting($request);

        $data = $request->validate([
            'markdown' => ['nullable', 'string'],
            'block_json' => ['nullable', 'array'],
        ]);

        $document = app(ContentDocumentService::class);
        $block = $document->normalize($data['block_json'] ?? [], $data['markdown'] ?? '');

        return response()->json([
            'html' => $block['mode'] === 'markdown' && ! str_starts_with($data['markdown'] ?? '', '```zfy-document') ? $this->renderer->render($data['markdown'] ?? '', $this->canUseRawHtml($request)) : $document->render($block['document']),
            'block_json' => $block,
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
            ->whereIn('kind', ['revision', 'pending', 'rejected', 'reviewed'])
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
        abort_unless($revision->content_id === $content->id && in_array($revision->kind, ['revision', 'pending', 'rejected', 'reviewed'], true), 404);

        return response()->json(['revision' => $this->revisionPayload($revision, true)]);
    }

    public function restoreRevision(Request $request, Content $content, ContentRevision $revision): JsonResponse
    {
        $this->authorizeWriting($request, $content);
        abort_unless($revision->content_id === $content->id && in_array($revision->kind, ['revision', 'pending'], true), 404);
        if ($revision->kind === 'pending') {
            abort_unless($request->user()->can('publish contents'), 403);
        }

        $snapshot = Arr::wrap($revision->snapshot);
        $payload = [
            ...$this->contentSnapshot($content),
            ...Arr::only($snapshot, [
                'title', 'type', 'category_id', 'tags', 'cover_url', 'excerpt', 'markdown_cache', 'block_json',
            ]),
            'status' => $content->status,
        ];
        $content = DB::transaction(function () use ($request, $payload, $content, $revision) {
            $content = Content::whereKey($content->id)->lockForUpdate()->firstOrFail();
            $revision = ContentRevision::whereKey($revision->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($revision->kind, ['revision', 'pending'], true), 409, '该版本已处理，请刷新。');
            if ($revision->kind === 'pending' && $revision->source_updated_at && ! $revision->source_updated_at->equalTo($content->updated_at)) {
                abort(409, '公开内容已更新，请重新核对并提交修订。');
            }
            $content = $this->persistContent($request, $payload, $content);
            if ($revision->kind === 'pending') {
                $revision->update(['kind' => 'reviewed']);
                if ($revision->user_id) {
                    DeliverSiteNotification::dispatch('review.approved:'.$revision->id, $revision->user_id, 'review', '修订已发布', $content->title);
                }
            }

            return $content;
        });

        return $this->contentResponse($content, '历史版本已恢复');
    }

    public function rejectRevision(Request $request, Content $content, ContentRevision $revision): JsonResponse
    {
        $this->authorizeWriting($request, $content);
        abort_unless($request->user()->can('publish contents'), 403);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        DB::transaction(function () use ($request, $content, $revision, $data) {
            Content::whereKey($content->id)->lockForUpdate()->firstOrFail();
            $revision = ContentRevision::whereKey($revision->id)->lockForUpdate()->firstOrFail();
            abort_unless($revision->content_id === $content->id && $revision->kind === 'pending', 409, '该待审版本已处理。');
            $revision->update(['kind' => 'rejected', 'snapshot' => [...($revision->snapshot ?? []), 'review' => ['reason' => $data['reason'], 'reviewer_id' => $request->user()->id, 'at' => now()->toIso8601String()]]]);
            if ($revision->user_id) {
                DeliverSiteNotification::dispatch('review.rejected:'.$revision->id, $revision->user_id, 'review', '修订已驳回', $data['reason']);
            }
            zfy_after_commit('zfy_content_review_rejected', $content, $revision, $request->user());
        });

        return response()->json(['message' => '修订已驳回，公开版本保持不变']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request): array
    {
        return $request->validate([
            'published_at' => ['nullable', 'date'],
            'title' => ['required', 'string', 'max:180'],
            'type' => ['required', 'string', Rule::in(config('zfy.content_types', ['post', 'images', 'files', 'page']))],
            'status' => ['required', 'string', Rule::in(['draft', 'published'])],
            'slug' => ['nullable', 'string', 'max:180'],
            'subtitle' => ['nullable', 'string', 'max:220'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'cover_url' => ['nullable', 'string', 'max:2048'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'tags' => ['nullable'],
            'markdown_cache' => ['nullable', 'string', 'max:200000'],
            'block_json' => ['nullable', 'array'],
        ]);
    }

    public function submit(Request $request, ?Content $content = null)
    {
        abort_unless($request->user()->canSubmitContent(), 403);
        abort_if($content && ($content->author_id !== $request->user()->id || $content->type === 'page'), 403);
        $payload = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'type' => ['required', Rule::in(array_values(array_diff(config('zfy.content_types', ['post', 'images', 'files']), ['page'])))],
            'status' => ['required', Rule::in(['draft', 'pending'])],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'markdown_cache' => ['required', 'string', 'max:200000'],
            'tags' => ['nullable', 'string', 'max:500'],
        ]);
        $content = DB::transaction(function () use ($request, $payload, $content) {
            if ($content) {
                $content = Content::whereKey($content->id)->lockForUpdate()->firstOrFail();
                abort_unless($content->author_id === $request->user()->id, 403);
                $payload['cover_url'] = $content->cover_url;
                $payload['subtitle'] = $content->subtitle;
                if ($content->status === 'published') {
                    app(ContentModeration::class)->check($payload['title'].' '.$payload['markdown_cache'], 'markdown_cache');
                    $kind = $payload['status'] === 'pending' ? 'pending' : 'author_draft';
                    $content->revisions()->updateOrCreate(['kind' => $kind, 'user_id' => $request->user()->id], ['snapshot' => $payload, 'source_updated_at' => $content->updated_at]);
                    zfy_after_commit('zfy_content_review_submitted', $content, $request->user());

                    return $content;
                }
            }

            return $this->persistContent($request, $payload, $content);
        });

        return redirect()->route('user.editor', ['content' => $content->id])->with('status', $payload['status'] === 'pending' ? '内容已提交，等待审核。' : '草稿已保存。');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function persistContent(Request $request, array $payload, ?Content $content = null, bool $recordRevision = true): Content
    {
        $user = $request->user();
        $markdown = (string) ($payload['markdown_cache'] ?? '');
        $isPublished = $payload['status'] === 'published';
        if ($isPublished) {
            abort_unless($user?->can('publish contents'), 403);
        }
        $publishAt = $isPublished ? (filled($payload['published_at'] ?? null) ? Carbon::parse($payload['published_at']) : ($content?->published_at ?: now())) : null;
        $isScheduled = $isPublished && $publishAt->isFuture();
        $wasPublished = $content?->status === 'published';
        $contentId = $content?->id;
        $allowRawHtml = $this->canUseRawHtml($request);
        $documents = app(ContentDocumentService::class);
        $block = $documents->normalize(Arr::wrap($payload['block_json'] ?? []), $markdown);

        if ($content && $recordRevision) {
            $this->recordRevision($content, $user?->id);
        }

        $attributes = [
            'author_id' => $content?->author_id ?: $user?->id,
            'category_id' => $payload['category_id'] ?? null,
            'type' => $payload['type'],
            'status' => $isScheduled ? 'scheduled' : $payload['status'],
            'title' => $payload['title'],
            'slug' => $this->uniqueSlug($payload['slug'] ?? $content?->slug ?? $payload['title'], $contentId),
            'subtitle' => $payload['subtitle'] ?? null,
            'excerpt' => $payload['excerpt'] ?? null,
            'cover_url' => $payload['cover_url'] ?? null,
            'markdown_cache' => $markdown,
            'rendered_html' => $block['mode'] === 'markdown' && ! str_starts_with($markdown, '```zfy-document') ? $this->renderer->render($markdown, $allowRawHtml) : $documents->render($block['document']),
            'block_json' => $block,
            'published_at' => $publishAt,
        ];

        $attributes = zfy_apply_strict('zfy_content_payload', $attributes, $payload, $request, $content);
        abort_unless(is_array($attributes), 422, '内容过滤器必须返回数组');

        zfy_validate('zfy_content_saving', $attributes, $content, $user);

        if ($content) {
            $content->update($attributes);
        } else {
            $content = Content::create($attributes);
        }

        $this->syncTags($content, $payload['tags'] ?? null);

        zfy_after_commit('zfy_content_saved', $content, $attributes, $user);
        zfy_after_commit($contentId ? 'zfy_content_updated' : 'zfy_content_created', $content, $attributes, $user);

        if ($content->status === 'published' && ! $wasPublished) {
            zfy_after_commit('zfy_content_published', $content, $attributes, $user);
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
            'markdown_cache' => ['nullable', 'string', 'max:200000'],
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

    private function saveQuickChange(Request $request, Content $content, array $attributes, mixed $tags = null, bool $syncTags = false): void
    {
        DB::transaction(function () use ($request, $content, $attributes, $tags, $syncTags) {
            $content = Content::whereKey($content->id)->lockForUpdate()->firstOrFail();
            $published = $content->status === 'published';
            if ($attributes['status'] === 'published') {
                abort_unless($request->user()->can('publish contents'), 403);
            }
            $attributes = zfy_apply_strict('zfy_content_payload', $attributes, $request->all(), $request, $content);
            abort_unless(is_array($attributes), 422);
            zfy_validate('zfy_content_saving', $attributes, $content, $request->user());
            $this->recordRevision($content, $request->user()->id);
            $content->update($attributes);
            if ($syncTags) {
                $this->syncTags($content, $tags);
            }
            zfy_after_commit('zfy_content_saved', $content, $attributes, $request->user());
            zfy_after_commit('zfy_content_updated', $content, $attributes, $request->user());
            if ($content->status === 'published' && ! $published) {
                zfy_after_commit('zfy_content_published', $content, $attributes, $request->user());
            }
        });
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
