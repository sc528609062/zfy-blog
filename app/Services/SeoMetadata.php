<?php

namespace App\Services;

use App\Models\Content;

class SeoMetadata
{
    public function forPage(?Content $content, string $page, string $siteName): array
    {
        $settings = app(SiteSettings::class);
        $title = $content ? (data_get($content->seo, 'title') ?: $content->title).' - '.$siteName : $siteName;
        $description = mb_substr(strip_tags((string) ($content ? (data_get($content->seo, 'description') ?: $content->excerpt) : $settings->get('site.description', ''))), 0, 300);
        $private = str_starts_with($page, 'user') || in_array($page, ['search', 'cart'], true) || request()->filled('theme_preview');
        $canonical = $content ? route('contents.show', $content->slug) : request()->url();

        return ['title' => $title, 'description' => $description, 'canonical' => $canonical, 'robots' => $private || ! $settings->get('reading.search_visible', true) ? 'noindex,nofollow' : 'index,follow', 'image' => $content?->cover_url, 'type' => $content ? 'article' : 'website'];
    }
}
