<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

final class AdminPagination
{
    public static function paginate(EloquentBuilder|Builder $query, Request $request, int $default = 20): LengthAwarePaginator
    {
        $data = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1', 'max:1000000'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $size = (int) ($data['per_page'] ?? $default);
        $rows = $query->paginate($size, ['*'], 'page', (int) ($data['page'] ?? 1));

        // Concurrent deletions can remove the requested last page.
        if ($rows->currentPage() > $rows->lastPage()) {
            $rows = $query->paginate($size, ['*'], 'page', $rows->lastPage());
        }

        return $rows;
    }
}
