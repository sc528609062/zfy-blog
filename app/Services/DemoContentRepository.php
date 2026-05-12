<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Content;
use App\Models\VipLevel;

class DemoContentRepository
{
    public function pageData(string $page = 'home', ?string $type = null): array
    {
        $contents = Content::with(['author', 'category', 'tags'])
            ->when($type, fn ($query) => $query->where('type', $type))
            ->where('status', 'published')
            ->latest('published_at')
            ->take(12)
            ->get();

        return [
            'page' => $page,
            'contents' => $contents,
            'featured' => $contents->first(),
            'resources' => Content::where('type', 'files')->where('status', 'published')->take(8)->get(),
            'posts' => Content::where('type', 'post')->where('status', 'published')->take(8)->get(),
            'images' => Content::where('type', 'images')->where('status', 'published')->take(8)->get(),
            'categories' => Category::orderBy('sort_order')->take(10)->get(),
            'vipLevels' => VipLevel::orderBy('level')->get(),
            'rankings' => Content::where('status', 'published')->orderByDesc('view_count')->take(8)->get(),
            'stats' => [
                'contents' => Content::count(),
                'downloads' => Content::sum('download_count'),
                'authors' => 128,
                'users' => '28,563+',
            ],
        ];
    }

    public function findContent(string $slug): ?Content
    {
        return Content::with(['author', 'category', 'tags'])->where('slug', $slug)->first();
    }
}
