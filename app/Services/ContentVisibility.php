<?php

namespace App\Services;

use App\Models\Content;
use App\Models\Order;
use App\Models\User;

class ContentVisibility
{
    public function allows(string $rule, ?Content $content, ?User $viewer): bool
    {
        if ($rule === 'password' && $content && ! $viewer?->is_banned) {
            return app(ContentPasswordAccess::class)->allows($content, $viewer) || ($viewer && ($viewer->can('manage contents') || $content->author_id === $viewer->id));
        }
        if (! $viewer || $viewer->is_banned) {
            return false;
        }
        if ($viewer->can('manage contents') || ($content && $content->author_id === $viewer->id)) {
            return true;
        }

        return match ($rule) {
            'member' => true,
            'vip' => (bool) $viewer->vip()->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))->exists(),
            'comment' => $content && $content->comments()->where('user_id', $viewer->id)->where('status', 'approved')->exists(),
            'purchased' => $content && Order::where('user_id', $viewer->id)->where('status', 'paid')->whereHas('items', fn ($query) => $query->where(fn ($query) => $query->where('item_type', 'content')->where('item_id', $content->id))->orWhere(fn ($query) => $query->where('item_type', 'product')->where('meta->content_id', $content->id)))->exists(),
            default => false,
        };
    }
}
