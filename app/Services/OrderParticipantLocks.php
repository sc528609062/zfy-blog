<?php

namespace App\Services;

use App\Models\Content;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderParticipantLocks
{
    public function acquire(Order $order): void
    {
        $ids = [$order->user_id];
        foreach ($order->items()->get() as $item) {
            if ($author = data_get($item->meta, 'author_settlement.author_id')) {
                $ids[] = (int) $author;
            } elseif ($item->item_type === 'content') {
                $ids[] = Content::withTrashed()->whereKey($item->item_id)->value('author_id');
            }
        }
        $ids = [...$ids, ...DB::table('author_earnings')->where('order_id', $order->id)->pluck('author_id')->all()];
        User::whereIn('id', array_filter(array_unique($ids)))->orderBy('id')->lockForUpdate()->get();
    }
}
