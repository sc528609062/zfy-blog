<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Support\Collection;

class NavigationService
{
    public function forViewer(?User $user): Collection
    {
        return Menu::with('items')->get()->map(function (Menu $menu) use ($user) {
            $grouped = $menu->items->groupBy(fn ($item) => $item->parent_id ?: 0);
            $build = function (int $parent, array $seen = []) use (&$build, $grouped, $user): Collection {
                return $grouped->get($parent, collect())->filter(function ($item) use ($user, $seen) {
                    $visibility = data_get($item->meta, 'visibility', 'public');

                    return ! isset($seen[$item->id]) && match ($visibility) {
                        'public' => true,
                        'guest' => ! $user,
                        'member' => $user && ! $user->is_banned,
                        'USER', 'EDITOR', 'ADMIN', 'SUPER_ADMIN' => $user && ! $user->is_banned && $user->hasRole($visibility),
                        default => false,
                    };
                })->map(fn ($item) => $item->setRelation('children', $build($item->id, $seen + [$item->id => true])))->values();
            };

            return $menu->setRelation('items', $build(0));
        })->keyBy('location');
    }
}
