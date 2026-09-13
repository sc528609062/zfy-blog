<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Theme;
use App\Models\User;
use App\Services\ThemeConfiguration;
use App\Services\ThemeManager;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeAppearanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreInstallSeeder::class);
        $this->actingAs(User::where('username', 'admin')->firstOrFail());
    }

    public function test_all_theme_defaults_can_be_saved_and_groups_are_complete(): void
    {
        $response = $this->getJson('/admin/themes/configuration')->assertOk();
        $groups = array_column($response->json('groups'), 'key');
        foreach ($response->json('data') as $theme) {
            $this->assertGreaterThan(45, count($theme['fields']));
            foreach ($theme['fields'] as $field) {
                $this->assertContains($field['group'], $groups);
            }
            $this->putJson('/admin/themes/'.$theme['id'].'/configuration', $theme['defaults'])->assertOk();
        }
        $this->getJson('/admin/theme-settings')->assertOk()->assertJsonPath('payload.current_page.kind', 'theme-settings');
    }

    public function test_invalid_urls_and_layout_options_do_not_change_saved_values(): void
    {
        $theme = Theme::firstOrFail();
        $url = '/admin/themes/'.$theme->id.'/configuration';
        $before = app(ThemeManager::class)->settingsFor($theme);
        foreach (['javascript:alert(1)', '//example.com/image.png', '/\\example.com', 'data:image/svg+xml,test', '/bad path', '/image"onerror=alert(1)'] as $value) {
            $this->putJson($url, ['hero_url' => $value])->assertUnprocessable();
            $this->postJson('/admin/themes/'.$theme->id.'/preview', ['logo_url' => $value])->assertUnprocessable();
        }
        foreach ([['page_width' => 99999], ['sidebar_width' => 20], ['card_radius' => 100], ['cover_ratio' => 'auto'], ['feed_layout' => 'unknown'], ['article_font_size' => 80], ['home_count' => -1]] as $values) {
            $this->putJson($url, $values)->assertUnprocessable();
        }
        $this->assertSame($before, app(ThemeManager::class)->settingsFor($theme));
        $this->putJson($url, ['logo_url' => '/theme-assets/a-avatar.png', 'hero_url' => 'https://example.com/path?q=1', 'card_radius' => 0])->assertOk();
        $this->putJson($url, ['logo_url' => '', 'hero_title' => ''])->assertOk();
        $settings = app(ThemeManager::class)->settingsFor($theme);
        $this->assertSame('', $settings['global']['logo_url']);
        $this->assertSame('', $settings['global']['hero_title']);
    }

    public function test_display_options_reach_each_frontend(): void
    {
        $content = Content::create(['type' => 'post', 'title' => 'Appearance article', 'slug' => 'appearance-article', 'excerpt' => 'Appearance excerpt', 'status' => 'published', 'published_at' => now(), 'author_id' => auth()->id(), 'body_markdown' => 'Public body']);
        foreach (Theme::all() as $theme) {
            app(ThemeManager::class)->activate($theme->slug);
            $this->putJson('/admin/themes/'.$theme->id.'/configuration', [
                'hero_enabled' => false, 'sidebar_enabled' => false, 'channels_enabled' => false,
                'header_search' => false, 'header_publish' => false, 'header_vip' => false,
                'announcement_enabled' => true, 'announcement_text' => 'Appearance announcement',
                'announcement_url' => '/posts', 'feed_layout' => 'grid', 'card_excerpt' => false,
                'archive_layout' => 'list', 'archive_sidebar' => false, 'detail_sidebar' => false,
                'detail_cover' => false, 'detail_excerpt' => false, 'detail_tags' => false, 'detail_date' => false,
                'footer_navigation' => false, 'icp_number' => 'Appearance ICP', 'back_to_top' => false,
                'page_width' => 1400, 'sidebar_position' => 'left', 'article_font_size' => 18,
            ])->assertOk();
            $this->get('/')->assertOk()->assertSee('Appearance announcement')->assertSee('Appearance ICP')
                ->assertSee('--site-width: 1400px', false)->assertSee('data-sidebar="left"', false)
                ->assertSee('data-back-to-top="false"', false)->assertSee('a-card-grid')
                ->assertDontSee('class="a-hero"', false)->assertDontSee('class="a-search"', false)
                ->assertDontSee('class="a-side-stack"', false)->assertDontSee('class="a-channel-grid"', false)
                ->assertDontSee('aria-label="页脚导航"', false);
            $this->get('/posts')->assertOk()->assertSee('a-feed-list')->assertDontSee('class="a-side-stack"', false);
            $this->get('/content/'.$content->slug)->assertOk()->assertSee('--site-article-font: 18px', false)
                ->assertDontSee('class="a-gallery-strip"', false)->assertDontSee('class="a-side-stack"', false)
                ->assertDontSee('<p>Appearance excerpt</p>', false)->assertDontSee('<time>', false);
        }
    }

    public function test_legacy_scoped_switches_keep_their_values_until_explicitly_saved(): void
    {
        $theme = Theme::firstOrFail();
        $theme->settings()->where('scope', 'global')->whereIn('key', ['hero_enabled', 'show_related'])->delete();
        foreach (['home' => 'hero_enabled', 'content-detail' => 'show_related'] as $scope => $key) {
            $theme->settings()->updateOrCreate(compact('scope', 'key'), ['value' => ['raw' => false]]);
        }
        $settings = app(ThemeManager::class)->settingsFor($theme);
        $this->assertFalse($settings['global']['hero_enabled']);
        $this->assertFalse($settings['home']['hero_enabled']);
        $this->assertFalse($settings['content-detail']['show_related']);
        $this->putJson('/admin/themes/'.$theme->id.'/configuration', ['hero_enabled' => true, 'show_related' => true])->assertOk();
        $settings = app(ThemeManager::class)->settingsFor($theme);
        $this->assertTrue($settings['home']['hero_enabled']);
        $this->assertTrue($settings['content-detail']['show_related']);
    }

    public function test_unknown_extension_groups_are_accessible_in_extension_section(): void
    {
        $theme = new Theme(['slug' => 'custom-theme', 'settings_schema' => ['global' => [
            ['key' => 'custom_image', 'type' => 'image', 'group' => 'custom', 'default' => '/image.png'],
        ]]]);
        $this->assertSame('extension', app(ThemeConfiguration::class)->fields($theme)[0]['group']);
    }
}
