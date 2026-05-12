<?php

namespace App\Actions\Content;

use App\Models\Content;
use App\Models\User;

class TrashContent
{
    public function execute(Content $content, ?User $editor = null): Content
    {
        $content->status = Content::STATUS_TRASH;
        if ($editor) {
            $content->editor_id = $editor->id;
        }
        $content->save();
        $content->delete();
        return $content;
    }
}
