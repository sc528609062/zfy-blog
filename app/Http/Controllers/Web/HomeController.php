<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Content;
use App\Models\FriendLink;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $latest = Content::query()
            ->published()
            ->feed()
            ->with(['author', 'category', 'tags'])
            ->latest('published_at')
            ->take(12)
            ->get();

        $hot = Content::query()
            ->published()
            ->feed()
            ->orderByDesc('view_count')
            ->take(6)
            ->get();

        $featuredCategories = Category::query()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->take(6)
            ->get();

        $vipLevels = \App\Models\VipLevel::query()
            ->where('enabled', true)
            ->orderBy('sort_order')
            ->take(3)
            ->get();

        $friendLinks = FriendLink::query()
            ->where('enabled', true)
            ->orderBy('sort_order')
            ->take(20)
            ->get();

        return view('home', compact('latest', 'hot', 'featuredCategories', 'vipLevels', 'friendLinks'));
    }
}
