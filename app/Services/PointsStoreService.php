<?php

namespace App\Services;

use App\Models\PointsStoreItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PointsStoreService
{
    public function exchange(User $user, PointsStoreItem $item, string $requestId): object
    {
        return DB::transaction(function () use ($user, $item, $requestId) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $existing = DB::table('points_exchange_orders')->where('user_id', $user->id)->where('meta->request_id', $requestId)->first();
            if ($existing) {
                if ($existing->points_store_item_id !== $item->id) {
                    throw ValidationException::withMessages(['request_id' => '兑换请求与商品不匹配。']);
                }

                return $existing;
            }
            $item = PointsStoreItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            if ($item->status !== 'active' || $item->stock === 0 || $item->points_price < 0) {
                throw ValidationException::withMessages(['item' => '商品已下架或库存不足。']);
            }
            $account = $user->pointsAccount()->lockForUpdate()->firstOrCreate([], ['points' => 0]);
            if ($account->points < $item->points_price) {
                throw ValidationException::withMessages(['points' => '积分不足。']);
            }
            $account->decrement('points', $item->points_price);
            if ($item->stock > 0) {
                $item->decrement('stock');
            }
            $id = DB::table('points_exchange_orders')->insertGetId([
                'user_id' => $user->id, 'points_store_item_id' => $item->id, 'status' => 'pending',
                'points_spent' => $item->points_price, 'meta' => json_encode(['request_id' => $requestId, 'title' => $item->title]),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('points_transactions')->insert([
                'points_account_id' => $account->id, 'type' => 'exchange', 'points' => -$item->points_price,
                'balance_after' => $account->fresh()->points, 'remark' => '兑换 '.$item->title,
                'meta' => json_encode(['exchange_order_id' => $id]), 'created_at' => now(), 'updated_at' => now(),
            ]);

            return DB::table('points_exchange_orders')->find($id);
        });
    }
}
