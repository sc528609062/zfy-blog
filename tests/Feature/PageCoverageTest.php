<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ThemeManager;
use App\Support\Zfy\AdminRegistry;
use Database\Seeders\CmsDemoSeeder;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_registered_core_admin_pages_have_a_real_payload(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $this->actingAs(User::where('username', 'admin')->first());
        foreach (app(AdminRegistry::class)->pages() as $page) {
            $this->assertNotSame('placeholder', $page['kind'], $page['key']);
            $this->getJson('/admin'.($page['key'] === 'dashboard' ? '' : '/'.$page['key']))->assertOk()->assertJsonPath('payload.current_page.key', $page['key']);
        }
    }

    public function test_all_three_themes_render_public_pages_and_real_counts(): void
    {
        $this->seed([CoreInstallSeeder::class, CmsDemoSeeder::class]);
        foreach (array_keys(config('zfy.themes')) as $slug) {
            app(ThemeManager::class)->activate($slug);
            foreach (['/', '/posts', '/files', '/images', '/rank', '/vip', '/shop', '/authors', '/search?q=建站', '/content/demo-content-1', '/p/about-zfy-blog'] as $url) {
                $this->get($url)->assertOk()->assertDontSee('128,594')->assertDontSee('28,563+');
            }
        }
    }
}
