<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Services\GalleryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminGalleryController extends Controller
{
    public function index(Request $request, Content $content, GalleryService $gallery)
    {
        abort_unless($request->user()?->can('manage contents'), 403);

        return response()->json(['items' => $gallery->items($content, $request->user(), true)]);
    }

    public function upload(Request $request, Content $content, GalleryService $gallery)
    {
        abort_unless($request->user()?->can('manage contents'), 403);
        $request->validate(['file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:20480']]);
        $gallery->upload($content, $request->user(), $request->file('file'));

        return response()->json(['items' => $gallery->items($content, $request->user(), true)], 201);
    }

    public function save(Request $request, Content $content)
    {
        abort_unless($request->user()?->can('manage contents'), 403);
        $data = $request->validate([
            'items' => ['present', 'array', 'max:100'], 'items.*.id' => ['required', 'integer', 'distinct'],
            'items.*.title' => ['nullable', 'string', 'max:180'], 'items.*.caption' => ['nullable', 'string', 'max:2000'],
            'items.*.copyright' => ['nullable', 'string', 'max:255'], 'items.*.visibility' => ['required', 'in:public,member,vip,purchased,comment,password'],
            'items.*.original_download' => ['required', 'boolean'], 'cover_id' => ['nullable', 'integer'],
        ]);
        DB::transaction(function () use ($content, $data) {
            $content = Content::whereKey($content->id)->lockForUpdate()->firstOrFail();
            $attachments = $content->attachments()->where('role', 'gallery')->lockForUpdate()->get()->keyBy('id');
            foreach ($data['items'] as $index => $item) {
                abort_unless($attachments->has($item['id']), 422, '图片不属于当前图集。');
                $attachments[$item['id']]->update(['meta' => [...$item, 'sort_order' => $index]]);
            }
            $content->attachments()->where('role', 'gallery')->whereNotIn('id', array_column($data['items'], 'id'))->delete();
            foreach ($attachments as $attachment) {
                if ($content->cover_url === route('gallery.image', $attachment, false) && (! in_array($attachment->id, array_column($data['items'], 'id'), true) || data_get($attachment->fresh()?->meta, 'visibility') !== 'public')) {
                    $content->update(['cover_url' => null]);
                }
            }
            if (! empty($data['cover_id'])) {
                $cover = $content->attachments()->where('role', 'gallery')->findOrFail($data['cover_id']);
                abort_unless(data_get($cover->meta, 'visibility') === 'public', 422, '受限图片不能用作公开封面。');
                $content->update(['cover_url' => route('gallery.image', $cover, false)]);
            }
        });

        return response()->json(['message' => '图集已保存']);
    }
}
