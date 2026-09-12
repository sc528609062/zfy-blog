<?php

namespace Tests\Feature;

use App\Models\PageLayout;
use App\Models\User;
use App\Services\PageBuilderRenderer;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageBuilderExtensionTest extends TestCase
{
    use RefreshDatabase;

    public function test_nested_layout_permissions_and_validation_apply_to_children(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $renderer = app(PageBuilderRenderer::class);
        $schema = ['blocks' => [['type' => 'container', 'columns' => 2, 'mobile_columns' => 1, 'children' => [
            ['type' => 'html', 'html' => 'PUBLIC'],
            ['type' => 'container', 'access' => 'member', 'children' => [['type' => 'html', 'html' => 'MEMBER ONLY']]],
        ]]]];
        $this->assertStringContainsString('PUBLIC', $renderer->render($schema));
        $this->assertStringNotContainsString('MEMBER ONLY', $renderer->render($schema));
        $this->assertStringContainsString('MEMBER ONLY', $renderer->render($schema, ['user' => User::factory()->create()]));
        $this->actingAs(User::where('username', 'admin')->first());
        $this->assertStringNotContainsString('MEMBER ONLY', $renderer->render($schema, ['user' => null]));
        $layout = PageLayout::firstOrFail();
        $url = route('admin.page-builder.save', $layout, false);
        $payload = ['title' => 'Nested', 'status' => 'published'];
        $this->postJson($url, [...$payload, 'schema' => json_encode($schema)])->assertOk();
        $schema['blocks'][0]['children'][0]['type'] = 'unknown';
        $this->postJson($url, [...$payload, 'schema' => json_encode($schema)])->assertUnprocessable();
        $block = ['type' => 'html', 'html' => 'TOO DEEP'];
        for ($i = 0; $i < 4; $i++) {
            $block = ['type' => 'container', 'children' => [$block]];
        }
        $this->postJson($url, [...$payload, 'schema' => json_encode(['blocks' => [$block]])])->assertUnprocessable();
        $this->assertSame('container', $layout->fresh()->schema['blocks'][0]['type']);
    }

    public function test_registered_fields_are_validated_before_layout_save_and_responsive_values_are_bounded(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $this->actingAs(User::where('username', 'admin')->first());
        zfy_register_block('test-callout', ['label' => 'Callout', 'rules' => ['body' => ['required', 'string', 'max:50']], 'render' => fn ($values) => '<p>'.e($values['body']).'</p>']);
        $layout = PageLayout::firstOrFail();
        $url = route('admin.page-builder.save', $layout, false);
        $data = ['title' => 'Layout', 'status' => 'published'];
        $this->postJson($url, [...$data, 'schema' => json_encode(['blocks' => [['type' => 'test-callout']]])])->assertUnprocessable();
        $this->postJson($url, [...$data, 'schema' => json_encode(['blocks' => [['type' => 'content-feed', 'columns' => 100]]])])->assertUnprocessable();
        $schema = ['blocks' => [['type' => 'test-callout', 'body' => 'Registered block', 'visibility' => 'mobile']]];
        $this->postJson($url, [...$data, 'schema' => json_encode($schema)])->assertOk();
        $html = app(PageBuilderRenderer::class)->render($layout->fresh()->schema);
        $this->assertStringContainsString('builder-only-mobile', $html);
        $this->assertStringContainsString('Registered block', $html);
    }
}
