<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InstallAndAdminArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_uninstalled_site_redirects_to_install_page(): void
    {
        config(['zfy.installed' => false]);
        File::delete(storage_path('app/zfy/install.lock'));

        $this->get('/')->assertRedirect('/install');
        $this->get('/install')->assertOk()->assertSee('install-payload');
    }

    public function test_installed_site_locks_install_page(): void
    {
        $this->get('/install')->assertRedirect(route('admin.dashboard', [], false));
    }

    public function test_install_status_reports_current_lock_state(): void
    {
        $this->getJson('/install/status')
            ->assertOk()
            ->assertJson([
                'installed' => true,
                'login' => '/login',
                'admin' => '/admin',
            ]);

        config(['zfy.installed' => false]);
        File::delete(storage_path('app/zfy/install.lock'));

        $this->getJson('/install/status')
            ->assertOk()
            ->assertJson([
                'installed' => false,
            ]);
    }

    public function test_admin_menu_is_registry_driven_and_skips_removed_wordpress_entries(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@zfy-blog.test')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/themes')
            ->assertOk()
            ->assertSee('admin_menu')
            ->assertSee('"key":"links"', false)
            ->assertSee('"key":"commerce"', false)
            ->assertDontSee('"key":"patterns"', false)
            ->assertDontSee('"key":"customize"', false)
            ->assertDontSee('"key":"theme-editor"', false)
            ->assertDontSee('"key":"plugin-editor"', false);
    }

    public function test_new_commerce_and_links_tables_exist(): void
    {
        foreach (['products', 'product_variants', 'links', 'link_categories', 'link_submissions', 'link_checks'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table {$table}");
        }
    }
}
