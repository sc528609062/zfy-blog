<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\User;
use App\Services\ContentDocumentService;
use App\Services\ContentMarkdownRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_hidden_source_uses_viewer_permissions_without_persisting_or_reusing_unlocked_html(): void
    {
        $viewer = User::factory()->create();
        $content = Content::create(['title' => 'Legacy', 'slug' => 'legacy', 'type' => 'post', 'status' => 'published', 'markdown_cache' => '{hide rule="member"}MEMBER_SECRET{/hide}', 'rendered_html' => 'old-public-cache']);
        $renderer = app(ContentMarkdownRenderer::class);
        $this->assertStringNotContainsString('MEMBER_SECRET', $renderer->renderContent($content));
        $this->actingAs($viewer);
        request()->setUserResolver(fn () => $viewer);
        $this->assertStringContainsString('MEMBER_SECRET', $renderer->renderContent($content, true));
        $this->assertSame('old-public-cache', $content->fresh()->rendered_html);
        $this->assertStringNotContainsString('MEMBER_SECRET', $renderer->render($content->markdown_cache));
        $documents = app(ContentDocumentService::class);
        $document = $documents->fromMarkdown($content->markdown_cache);
        $this->assertStringContainsString('MEMBER_SECRET', $documents->render($document, $content, $viewer));
        $this->assertStringNotContainsString('MEMBER_SECRET', $documents->render($document, $content));
        $this->assertStringNotContainsString('PAID_SECRET', $renderer->renderForViewer('{hide rule="purchased"}PAID_SECRET{/hide}', $content, $viewer));
        $this->assertStringNotContainsString('UNKNOWN_SECRET', $renderer->renderForViewer('{hide rule="unknown"}UNKNOWN_SECRET{/hide}', $content, $viewer));
        $nested = '{hide rule="member"}PUBLIC_MEMBER {hide rule="vip"}VIP_SECRET{/hide} AFTER_MEMBER{/hide}';
        $html = $renderer->renderForViewer($nested, $content, $viewer);
        $this->assertStringContainsString('PUBLIC_MEMBER', $html);
        $this->assertStringContainsString('AFTER_MEMBER', $html);
        $this->assertStringNotContainsString('VIP_SECRET', $html);
        $this->assertStringNotContainsString('UNCLOSED_SECRET', $renderer->render('{alert}{hide}UNCLOSED_SECRET{/alert}'));
    }
}
