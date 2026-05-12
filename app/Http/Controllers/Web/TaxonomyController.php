<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\View\View;

class TaxonomyController extends Controller
{
    public function category(Category $category): View
    {
        $contents = $category->contents()
            ->published()
            ->with(['author', 'tags'])
            ->latest('published_at')
            ->paginate(20);

        return view('channel', [
            'title' => '分类：' . $category->name,
            'category' => $category,
            'contents' => $contents,
        ]);
    }

    public function tag(Tag $tag): View
    {
        $contents = $tag->contents()
            ->published()
            ->with(['author', 'category'])
            ->latest('published_at')
            ->paginate(20);

        return view('channel', [
            'title' => '标签：' . $tag->name,
            'tag' => $tag,
            'contents' => $contents,
        ]);
    }
}
