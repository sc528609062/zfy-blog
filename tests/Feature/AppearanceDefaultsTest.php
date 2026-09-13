<?php

namespace Tests\Feature;

use App\Models\PageLayout;
use App\Models\Theme;
use App\Models\User;
use App\Services\PageLayoutSchema;
use App\Services\ThemeConfiguration;
use App\Services\ThemeManager;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppearanceDefaultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_theme_defaults_ignore_saved_overrides_and_reset_only_selected_theme(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $this->actingAs(User::where('username', 'admin')->firstOrFail());
        $theme = Theme::firstOrFail();
        $other = Theme::whereKeyNot($theme->id)->firstOrFail();
        $url = '/admin/themes/'.$theme->id.'/configuration';
        $this->putJson($url, ['logo_text' => 'Edited brand', 'hero_enabled' => false])->assertOk();
        $other->settings()->create(['scope' => 'global', 'key' => 'logo_text', 'value' => ['raw' => 'Other brand']]);
        $theme->settings()->create(['scope' => 'content-detail', 'key' => 'markdown_theme', 'value' => ['raw' => 'github']]);
        $payload = collect($this->getJson('/admin/themes/configuration')->assertOk()->json('data'))->firstWhere('id', $theme->id);
        $this->assertSame('Edited brand', $payload['values']['logo_text']);
        $this->assertNotSame('Edited brand', $payload['defaults']['logo_text']);
        $this->assertTrue($payload['defaults']['hero_enabled']);
        $this->putJson($url, $payload['defaults'])->assertOk();
        $settings = app(ThemeManager::class)->settingsFor($theme);
        $this->assertSame($payload['defaults']['logo_text'], $settings['global']['logo_text']);
        $this->assertTrue($settings['home']['hero_enabled']);
        $this->assertSame('github', $settings['content-detail']['markdown_theme']);
        $this->assertSame('Other brand', app(ThemeManager::class)->settingsFor($other)['global']['logo_text']);
    }

    public function test_package_field_defaults_preserve_false_zero_and_empty_values(): void
    {
        $theme = new Theme(['slug' => 'third-party', 'settings_schema' => ['global' => [
            ['key' => 'show_banner', 'type' => 'boolean', 'default' => false],
            ['key' => 'offset', 'type' => 'number', 'default' => 0],
            ['key' => 'caption', 'type' => 'text', 'default' => ''],
        ]]]);
        $this->assertSame(['show_banner' => false, 'offset' => 0, 'caption' => ''], app(ThemeConfiguration::class)->defaults($theme));
    }

    public function test_layout_reset_can_disable_custom_rendering_and_survives_reload(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $this->actingAs(User::where('username', 'admin')->firstOrFail());
        $layout = PageLayout::firstOrFail();
        $url = route('admin.page-builder.save', $layout, false);
        $meta = ['title' => 'My layout', 'status' => 'published'];
        $this->postJson($url, [...$meta, 'schema' => json_encode(['blocks' => [['type' => 'html', 'html' => 'Custom']]])])->assertOk();
        $this->assertTrue($layout->fresh()->schema['enabled']);
        $payload = collect($this->getJson('/admin/page-builder')->assertOk()->json('payload.layouts'))->firstWhere('id', $layout->id);
        $this->postJson($url, [...$meta, 'schema' => json_encode($payload['default_schema'])])->assertOk();
        $this->assertSame(app(PageLayoutSchema::class)->defaults('home'), $layout->fresh()->schema);
        $this->assertSame('My layout', $layout->fresh()->title);
        $this->postJson($url, [...$meta, 'schema' => json_encode(['enabled' => 'invalid', 'blocks' => []])])->assertUnprocessable();
        $this->assertFalse($layout->fresh()->schema['enabled']);
    }

    public function test_user_without_theme_permission_cannot_read_defaults_or_save_resets(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('EDITOR');
        $this->actingAs($user);
        $this->getJson('/admin/themes/configuration')->assertForbidden();
        $this->getJson('/admin/page-builder')->assertForbidden();
        $this->putJson('/admin/themes/'.Theme::firstOrFail()->id.'/configuration', ['logo_text' => 'Reset'])->assertForbidden();
        $this->postJson(route('admin.page-builder.save', PageLayout::firstOrFail(), false), [])->assertForbidden();
    }
}
