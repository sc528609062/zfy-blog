<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Content;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->input('q', ''));

        $contents = collect();
        if ($q !== '') {
            $contents = Content::query()
                ->published()
                ->where(function ($qb) use ($q) {
                    $qb->where('title', 'like', "%{$q}%")
                       ->orWhere('plain_text', 'like', "%{$q}%");
                })
                ->latest('published_at')
                ->paginate(20)
                ->withQueryString();
        }

        return view('search', [
            'title' => '搜索：' . ($q ?: '...'),
            'q' => $q,
            'contents' => $contents,
        ]);
    }
}
