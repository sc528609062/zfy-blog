<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Plugin;
use App\Models\Theme;
use App\Models\User;
use App\Services\ContentDocumentService;
use App\Services\ContentMarkdownRenderer;
use App\Services\PackageManifestService;
use App\Services\PluginInstaller;
use App\Services\PluginLifecycleManager;
use App\Services\ThemeInstaller;
use App\Services\ThemeLifecycleManager;
use App\Services\ThemeManager;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PharData;
use Tests\TestCase;

class DocumentAndPackageTest extends TestCase
{
    use RefreshDatabase;

    public function test_indent_and_inline_code_survive_conversion_and_server_rendering(): void
    {
        $document = ['type' => 'doc', 'content' => [['type' => 'paragraph', 'attrs' => ['indent' => 2], 'content' => [['type' => 'text', 'text' => 'CODE', 'marks' => [['type' => 'code']]]]]]];
        $service = app(ContentDocumentService::class);
        $this->assertSame($document, $service->fromMarkdown("```zfy-document\n".json_encode($document)."\n```"));
        $html = $service->render($document);
        $this->assertStringContainsString('margin-left:4em', str_replace(' ', '', $html));
        $this->assertStringContainsString('<code>CODE</code>', $html);
        $document['content'][0]['attrs']['indent'] = 100;
        $this->expectException(ValidationException::class);
        $service->render($document);
    }

    public function test_document_round_trip_and_server_rendering(): void
    {
        $service = app(ContentDocumentService::class);
        $document = ['type' => 'doc', 'content' => [['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => '<script>literal</script>']]], ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Bold', 'marks' => [['type' => 'bold']]]]]]];
        $preserved = "```zfy-document\n".json_encode($document)."\n```";
        $this->assertSame($document, $service->fromMarkdown($preserved));
        $html = $service->render($document);
        $this->assertStringContainsString('<strong>Bold</strong>', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $source = '{zfy-alert color="blue"}Example{/zfy-alert}';
        $this->assertSame($source, $service->fromMarkdown($source)['content'][0]['attrs']['source']);
    }

    public function test_unknown_document_nodes_are_rejected_instead_of_discarded(): void
    {
        $this->expectException(ValidationException::class);
        app(ContentDocumentService::class)->render(['type' => 'doc', 'content' => [['type' => 'unknown-extension']]]);
    }

    public function test_registered_shortcode_and_preserved_markdown_render_in_public_content(): void
    {
        zfy_register_shortcode('example-callout', fn ($attributes) => '<p>Extension '.e($attributes['title'] ?? '').'</p>');
        $renderer = app(ContentMarkdownRenderer::class);
        $this->assertStringContainsString('Extension Hello', $renderer->render('{example-callout title="Hello" /}'));
        $document = ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Retained block']]]]];
        $markdown = "```zfy-document\n".json_encode($document)."\n```";
        $content = new Content(['markdown_cache' => $markdown, 'block_json' => app(ContentDocumentService::class)->normalize(['mode' => 'markdown'], $markdown)]);
        $this->assertStringContainsString('<p>Retained block</p>', $renderer->renderContent($content));
        $this->assertStringNotContainsString('zfy-document', $renderer->renderContent($content));
    }

    public function test_malformed_manifest_returns_errors_instead_of_crashing(): void
    {
        $this->assertNotEmpty(app(PackageManifestService::class)->validatePluginPayload(['slug' => [], 'version' => [], 'requires' => 'invalid']));
    }

    public function test_permission_blocks_are_evaluated_for_each_viewer_and_not_written_to_cache(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $paragraph = ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'PRIVATE BLOCK']]];
        $document = ['type' => 'doc', 'content' => [['type' => 'zfyRestricted', 'attrs' => ['rule' => 'member'], 'content' => [$paragraph]]]];
        $content = Content::create(['title' => 'Mixed', 'slug' => 'mixed', 'type' => 'post', 'status' => 'published', 'block_json' => ['version' => 3, 'mode' => 'blocks', 'document' => $document], 'rendered_html' => 'Public cached HTML']);
        $service = app(ContentDocumentService::class);
        $this->assertStringNotContainsString('PRIVATE BLOCK', $service->render($document, $content));
        $viewer = User::factory()->create();
        $this->assertStringContainsString('PRIVATE BLOCK', $service->render($document, $content, $viewer));
        $this->assertStringNotContainsString('PRIVATE BLOCK', $service->render($document, $content));
        $this->assertSame('Public cached HTML', $content->fresh()->rendered_html);
        $this->assertSame('heading', $service->fromMarkdown('# Normal heading')['content'][0]['type']);
    }

    public function test_theme_package_installs_without_core_configuration(): void
    {
        config(['extensions.safe_mode' => false]);
        $this->seed(CoreInstallSeeder::class);
        $slug = 'test-theme-'.Str::lower(Str::random(8));
        $path = sys_get_temp_dir().'/'.$slug.'.zip';
        try {
            $zip = new PharData($path);
            $zip->addFromString($slug.'/theme.json', json_encode(['name' => 'Native Theme', 'slug' => $slug, 'version' => '1.0.0', 'compatible' => '^1.0', 'entry' => 'views/layout.blade.php']));
            $zip->addFromString($slug.'/views/layout.blade.php', '<!doctype html><title>Native Theme</title><h1>{{ $siteName }}</h1>');
            unset($zip);
            $theme = app(ThemeInstaller::class)->install(new UploadedFile($path, 'theme.zip', 'application/zip', null, true));
            app(ThemeManager::class)->activate($theme->slug);
            $this->get('/')->assertOk()->assertSee('Native Theme');
            $this->assertStringStartsWith('theme-'.$slug.'::', $theme->entry_view);
        } finally {
            File::deleteDirectory(base_path('themes/'.$slug));
            File::delete($path);
        }
    }

    public function test_plugin_dependency_prevents_activation(): void
    {
        $slug = 'test-plugin-'.Str::lower(Str::random(8));
        $root = base_path('plugins/'.$slug);
        try {
            File::ensureDirectoryExists($root);
            File::put($root.'/plugin.json', json_encode(['name' => 'Dependent Plugin', 'slug' => $slug, 'version' => '1.0.0', 'compatible' => '^1.0', 'permissions' => [], 'events' => [], 'requires' => ['plugins' => ['missing-plugin' => '^1.0']]]));
            $plugin = Plugin::create(['name' => 'Dependent', 'slug' => $slug, 'version' => '1.0.0', 'permissions' => [], 'events' => [], 'enabled' => false]);
            try {
                app(PluginLifecycleManager::class)->toggle($plugin, true);
                $this->fail('Missing dependency should reject activation');
            } catch (ValidationException) {
                $this->assertFalse($plugin->fresh()->enabled);
            }
        } finally {
            File::deleteDirectory($root);
        }
    }

    public function test_schema_rejects_bad_nested_definitions(): void
    {
        $manifest = ['schema_version' => 1, 'name' => 'Example', 'slug' => 'example', 'version' => '1.0.0', 'compatible' => '^1', 'permissions' => [], 'events' => [], 'admin_pages' => [['key' => 'example', 'label' => 'Example', 'kind' => 'dashboard']]];
        $this->assertNotEmpty(app(PackageManifestService::class)->validatePluginPayload($manifest));
    }

    public function test_theme_uninstall_retains_settings_until_separate_cleanup(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $slug = 'theme-life-'.Str::lower(Str::random(8));
        $root = base_path('themes/'.$slug);
        File::ensureDirectoryExists($root.'/views');
        File::put($root.'/theme.json', json_encode(['name' => 'Life', 'slug' => $slug, 'version' => '1.0.0']));
        $theme = Theme::create(['name' => 'Life', 'slug' => $slug, 'version' => '1.0.0', 'compatible' => '^1', 'entry_view' => 'theme-'.$slug.'::layout']);
        $theme->settings()->create(['scope' => 'global', 'key' => 'title', 'value' => ['raw' => 'Retained']]);
        try {
            app(ThemeLifecycleManager::class)->uninstall($theme);
            $this->assertDirectoryDoesNotExist($root);
            $this->assertSame('Retained', data_get($theme->settings()->where('key', 'title')->first()->value, 'raw'));
            app(ThemeLifecycleManager::class)->purge($theme);
            $this->assertDatabaseMissing('themes', ['id' => $theme->id]);
            $this->assertDatabaseMissing('theme_settings', ['theme_id' => $theme->id]);
        } finally {
            File::deleteDirectory($root);
        }
    }

    public function test_independent_example_plugin_can_install_upgrade_uninstall_and_reinstall(): void
    {
        $slug = 'example-test-'.Str::lower(Str::random(8));
        $directory = storage_path('framework/testing/'.$slug);
        File::copyDirectory(base_path('examples/plugins/editorial-tools'), $directory);
        $manifest = json_decode(File::get($directory.'/plugin.json'), true);
        $manifest['slug'] = $slug;
        $root = base_path('plugins/'.$slug);
        $package = function (string $version) use ($directory, $manifest, $slug): UploadedFile {
            $manifest['version'] = $version;
            File::put($directory.'/plugin.json', json_encode($manifest));
            $path = $directory.'-'.$version.'.zip';
            $archive = new PharData($path);
            foreach (File::allFiles($directory) as $file) {
                $archive->addFile($file->getPathname(), $slug.'/'.$file->getRelativePathname());
            }
            unset($archive);

            return new UploadedFile($path, 'plugin.zip', 'application/zip', null, true);
        };
        try {
            $installer = app(PluginInstaller::class);
            $manager = app(PluginLifecycleManager::class);
            $plugin = $installer->install($package('1.0.0'));
            $plugin->settings()->create(['key' => 'notice', 'value' => ['raw' => 'Retained setting']]);
            $manager->toggle($plugin, true);
            $this->assertTrue($plugin->fresh()->enabled);
            $manager->toggle($plugin, false);
            $plugin = $installer->install($package('1.1.0'));
            $this->assertSame('1.1.0', $plugin->version);
            $manager->toggle($plugin, true);
            $this->assertFalse($plugin->settings()->where('key', '_lifecycle.previous_version')->exists());
            $manager->toggle($plugin, false);
            $manager->uninstall($plugin);
            $this->assertDirectoryDoesNotExist($root);
            $reinstalled = $installer->install(new UploadedFile($directory.'-1.1.0.zip', 'plugin.zip', 'application/zip', null, true));
            $this->assertSame($plugin->id, $reinstalled->id);
            $this->assertSame('Retained setting', data_get($reinstalled->settings()->where('key', 'notice')->first()->value, 'raw'));
            $manager->uninstall($reinstalled);
            $manager->purge($reinstalled);
            $this->assertDatabaseMissing('plugins', ['id' => $reinstalled->id]);
            $this->assertDatabaseMissing('plugin_settings', ['plugin_id' => $reinstalled->id]);
        } finally {
            File::deleteDirectory($root);
            File::deleteDirectory($directory);
            File::delete([$directory.'-1.0.0.zip', $directory.'-1.1.0.zip']);
        }
    }
}
