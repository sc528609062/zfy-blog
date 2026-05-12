<?php

namespace App\Actions\Content;

use App\Models\Content;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PublishContent
{
    public function execute(Content $content, ?User $editor = null): Content
    {
        DB::transaction(function () use ($content, $editor) {
            $content->status = Content::STATUS_PUBLISHED;
            $content->published_at ??= now();
            if ($editor) {
                $content->editor_id = $editor->id;
            }
            $content->save();

            if ($content->category) {
                $content->category->increment('content_count');
            }

            foreach ($content->tags as $tag) {
                $tag->increment('content_count');
            }
        });

        return $content->refresh();
    }
}
