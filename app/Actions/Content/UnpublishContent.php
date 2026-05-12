<?php

namespace App\Actions\Content;

use App\Models\Content;
use App\Models\User;

class UnpublishContent
{
    public function execute(Content $content, ?User $editor = null): Content
    {
        $content->status = Content::STATUS_DRAFT;
        if ($editor) {
            $content->editor_id = $editor->id;
        }
        $content->save();
        return $content->refresh();
    }
}
