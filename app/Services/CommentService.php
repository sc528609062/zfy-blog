<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CommentService
{
    public function store(Request $request, Content $content): Comment
    {
        abort_unless(Content::published()->whereKey($content->id)->exists(), 404);
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'integer', Rule::exists('comments', 'id')->where('content_id', $content->id)->where('status', 'approved')],
        ]);

        return DB::transaction(function () use ($request, $content, $data) {
            $review = app(ContentModeration::class)->check($data['body']);
            zfy_validate('zfy_comment_saving', $data, $content, $request->user());
            $comment = $content->comments()->create([
                ...$data,
                'user_id' => $request->user()->id,
                'status' => $review || app(SiteSettings::class)->get('discussion.require_approval', true) ? 'pending' : 'approved',
                'ip_address' => $request->ip(),
            ]);
            if ($comment->status === 'approved') {
                $content->increment('comment_count');
            }
            zfy_after_commit('zfy_comment_created', $comment, $content);

            return $comment;
        });
    }
}
