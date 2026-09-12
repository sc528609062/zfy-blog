<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Content;
use App\Models\Link;
use App\Models\PageLayout;
use App\Models\User;
use App\Models\VipLevel;
use App\Models\Widget;

class DemoContentRepository
{
    public function pageData(string $page = 'home', ?string $type = null, array $filters = []): array
    {
        $query = Content::with(['author:id,name,username,avatar_url,bio,is_author', 'category', 'tags'])->published()
            ->when(in_array($page, ['home', 'rank'], true), fn ($query) => $query->where('type', '!=', 'page'))
            ->when($type, fn ($query) => $query->where('type', $type))
            ->when($filters['category_id'] ?? null, fn ($query, $id) => $query->where('category_id', $id))
            ->when($filters['tag_id'] ?? null, fn ($query, $id) => $query->whereHas('tags', fn ($tags) => $tags->whereKey($id)))
            ->when($filters['topic_id'] ?? null, fn ($query, $id) => $query->whereHas('topics', fn ($topics) => $topics->whereKey($id)))
            ->when($filters['author_id'] ?? null, fn ($query, $id) => $query->where('author_id', $id));
        if ($page === 'search') {
            $keyword = mb_substr(trim((string) request('q', '')), 0, 120);
            $query->matchingPublicText($keyword);
            if (in_array(request('type'), ['post', 'images', 'files', 'page'], true)) {
                $query->where('type', request('type'));
            }
        }
        $sort = request('sort', $page === 'rank' ? 'popular' : 'latest');
        if ($sort === 'popular') {
            $query->orderByDesc('view_count');
        } elseif ($sort === 'downloads') {
            $query->orderByDesc('download_count');
        }
        if (request()->boolean('free')) {
            $query->where(fn ($query) => $query->whereNull('pricing')->orWhereNull('pricing->price')->orWhere('pricing->price', 0));
        }
        $settings = app(SiteSettings::class);
        $pagination = $query->latest('published_at')->paginate(max(1, min(100, (int) $settings->get('reading.page_size', 12))))->withQueryString();
        $contents = $pagination->getCollection();

        return [
            'page' => $page,
            'contents' => $contents,
            'pagination' => $pagination,
            'featured' => $contents->first(),
            'resources' => $page === 'home' ? Content::published()->where('type', 'files')->latest('published_at')->take(8)->get() : $contents->where('type', 'files'),
            'posts' => $page === 'home' ? Content::published()->where('type', 'post')->latest('published_at')->take(8)->get() : $contents->where('type', 'post'),
            'images' => $page === 'home' ? Content::published()->where('type', 'images')->latest('published_at')->take(8)->get() : $contents->where('type', 'images'),
            'categories' => Category::withCount(['contents' => fn ($query) => $query->published()])->orderBy('sort_order')->take(10)->get(),
            'vipLevels' => VipLevel::orderBy('level')->get(),
            'rankings' => Content::published()->orderByDesc('view_count')->take(8)->get(),
            'authors' => User::where('is_author', true)->where('author_status', 'approved')->where('is_banned', false)->withCount(['contents' => fn ($query) => $query->published()])->paginate(20),
            'links' => Link::with('category')->where('status', 'active')->orderBy('sort_order')->get(),
            'navigation' => app(NavigationService::class)->forViewer(request()->user() ?? request()->user('sanctum')),
            'widgets' => Widget::where('enabled', true)->orderBy('sort_order')->get()->groupBy('region'),
            'siteName' => $settings->get('site.name', config('app.name', 'zfy-blog')),
            'pageLayout' => $page === 'home' ? PageLayout::where('scope', 'home')->where('status', 'published')->first() : null,
            'stats' => [
                'contents' => Content::published()->count(),
                'downloads' => Content::published()->sum('download_count'),
                'authors' => User::where('is_author', true)->count(),
                'users' => User::count(),
            ],
        ];
    }

    public function findContent(string $slug): ?Content
    {
        return Content::with(['author', 'category', 'tags'])->published()->where('slug', $slug)->first();
    }
}
