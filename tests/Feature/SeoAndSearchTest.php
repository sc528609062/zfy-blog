<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Setting;
use App\Services\SeoMetadata;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoAndSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_search_and_seo_only_expose_public_metadata(): void
    {
        $public = Content::create(['title' => 'Public tutorial', 'slug' => 'public-tutorial', 'type' => 'post', 'status' => 'published', 'excerpt' => 'Searchable summary', 'markdown_cache' => 'BODY_SECRET', 'seo' => ['title' => 'SEO title', 'description' => 'SEO description']]);
        $draft = Content::create(['title' => 'Secret draft', 'slug' => 'secret-draft', 'type' => 'post', 'status' => 'draft']);
        $scheduled = Content::create(['title' => 'Scheduled', 'slug' => 'scheduled-search', 'type' => 'post', 'status' => 'published', 'published_at' => now()->addDay()]);
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertStringContainsString('public-tutorial', $xml);
        $this->assertStringNotContainsString('secret-draft', $xml);
        $this->assertStringNotContainsString('scheduled-search', $xml);
        $this->assertTrue($public->shouldBeSearchable());
        $this->assertFalse($draft->shouldBeSearchable());
        $this->assertFalse($scheduled->shouldBeSearchable());
        $this->assertFalse(Content::published()->matchingPublicText('BODY_SECRET')->exists());
        $this->assertTrue(Content::published()->matchingPublicText('summary')->exists());
        $seo = app(SeoMetadata::class)->forPage($public, 'post-detail', 'Site');
        $this->assertSame('SEO title - Site', $seo['title']);
        $this->assertSame('SEO description', $seo['description']);
        $this->assertSame('noindex,nofollow', app(SeoMetadata::class)->forPage(null, 'user-orders', 'Site')['robots']);
        Setting::create(['key' => 'reading.search_visible', 'value' => ['raw' => false]]);
        $this->assertStringNotContainsString('public-tutorial', $this->get('/sitemap.xml')->assertOk()->getContent());
    }
}
