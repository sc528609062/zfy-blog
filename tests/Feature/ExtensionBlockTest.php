<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ContentDocumentService;
use App\Services\ContentHtmlSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ExtensionBlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_collection_blocks_preserve_items_and_filter_nested_permissions(): void
    {
        $service = app(ContentDocumentService::class);
        foreach (['tabs', 'timeline', 'collapse', 'card-list'] as $type) {
            $values = ['items' => [
                ['title' => '<script>Title</script>', 'body' => '**Public item** {hide type="member"}PRIVATE ITEM{/hide}', 'color' => '#126abc', 'open' => true],
                ['title' => 'Second item', 'body' => 'Second body', 'open' => false],
            ]];
            $document = ['type' => 'doc', 'content' => [['type' => 'zfyExtension', 'attrs' => ['key' => 'core-'.$type, 'values' => json_encode($values)]]]];
            $this->assertSame($document, $service->fromMarkdown("```zfy-document\n".json_encode($document)."\n```"));
            $public = $service->render($document);
            $this->assertStringContainsString('zfy-shortcode-'.$type, $public);
            $this->assertStringContainsString('<strong>Public item</strong>', $public);
            $this->assertStringContainsString('Second body', $public);
            $this->assertStringNotContainsString('PRIVATE ITEM', $public);
            $this->assertStringNotContainsString('<script>', $public);
            $this->assertStringContainsString('PRIVATE ITEM', $service->render($document, null, new User(['name' => 'Member'])));
        }
        $legacy = ['type' => 'doc', 'content' => [['type' => 'zfyExtension', 'attrs' => ['key' => 'core-tabs', 'values' => json_encode(['body' => '{zfy-tab title="Legacy"}Original body{/zfy-tab}'])]]]];
        $this->assertStringContainsString('Original body', $service->render($legacy));
        $values['items'][0]['color'] = 'red;position:fixed';
        $document['content'][0]['attrs']['values'] = json_encode($values);
        $this->expectException(ValidationException::class);
        $service->render($document);
    }

    public function test_sanitizer_keeps_site_whitelist_and_rejects_unknown_iframe_hosts(): void
    {
        $html = app(ContentHtmlSanitizer::class)->clean('<p style="position:fixed;margin-left:2em"><u>UNDERLINE</u></p><iframe src="https://player.bilibili.com/player.html"></iframe><iframe src="https://untrusted.example.test/embed"></iframe><script>run()</script>');
        $this->assertStringContainsString('<u>UNDERLINE</u>', $html);
        $this->assertStringContainsString('margin-left:2em', str_replace(' ', '', $html));
        $this->assertStringContainsString('https://player.bilibili.com/player.html', $html);
        $this->assertStringNotContainsString('untrusted.example.test', $html);
        $this->assertStringNotContainsString('position:', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    public function test_core_blocks_keep_nested_permissions_and_use_explicit_viewer(): void
    {
        $document = ['type' => 'doc', 'content' => [['type' => 'zfyExtension', 'attrs' => ['key' => 'core-alert', 'values' => json_encode(['title' => 'Notice', 'body' => '{hide type="member"}HIDDEN VALUE{/hide}', 'color' => 'blue'])]]]];
        $service = app(ContentDocumentService::class);
        $this->assertStringContainsString('Notice', $service->render($document));
        $this->assertStringNotContainsString('HIDDEN VALUE', $service->render($document));
        zfy_register_block('context-test', ['rules' => [], 'render' => fn ($data, $context) => $context['user'] ? '<p>PRIVILEGED</p>' : '<p>ANONYMOUS</p>']);
        $document['content'][0]['attrs'] = ['key' => 'context-test', 'values' => '{}'];
        $viewer = new User(['name' => 'Viewer']);
        $this->assertStringContainsString('PRIVILEGED', $service->render($document, null, $viewer));
        $this->assertStringContainsString('ANONYMOUS', $service->render($document));
    }

    public function test_registered_block_renders_and_missing_extension_keeps_document_data(): void
    {
        zfy_register_block('test-callout', ['rules' => ['text' => ['required', 'string', 'max:100']], 'render' => fn ($data) => '<p>'.e($data['text']).'</p><script>alert(1)</script>']);
        $document = ['type' => 'doc', 'content' => [['type' => 'zfyExtension', 'attrs' => ['key' => 'test-callout', 'values' => '{"text":"Plugin content"}']]]];
        $service = app(ContentDocumentService::class);
        $html = $service->render($document);
        $this->assertStringContainsString('Plugin content', $html);
        $this->assertStringNotContainsString('<script', $html);
        $document['content'][0]['attrs']['key'] = 'disabled-extension';
        $this->assertStringNotContainsString('Plugin content', $service->render($document));
        $source = "```zfy-document\n".json_encode($document)."\n```";
        $this->assertSame($document, $service->fromMarkdown($source));
    }
}
