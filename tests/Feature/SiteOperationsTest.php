<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Link;
use App\Models\Theme;
use App\Models\User;
use App\Models\VipLevel;
use App\Services\Install\InstallationService;
use App\Services\LinkChecker;
use App\Services\OrderService;
use App\Services\ThemeManager;
use App\Support\Zfy\AdminRegistry;
use Database\Seeders\CoreInstallSeeder;
use Dotenv\Dotenv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SiteOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreInstallSeeder::class);
    }

    public function test_all_registered_admin_pages_respond_and_permalinks_redirect(): void
    {
        $this->actingAs(User::where('username', 'admin')->first());
        foreach (app(AdminRegistry::class)->pages() as $page) {
            $this->getJson('/admin/'.$page['key'])->assertOk();
        }
        Content::create(['title' => 'Permalink article', 'slug' => 'permalink-test', 'status' => 'published', 'type' => 'post']);
        $this->putJson('/admin/settings/permalink', ['values' => ['permalink.content_base' => 'articles']])->assertOk();
        $this->get('/content/permalink-test')->assertRedirect('/articles/permalink-test')->assertStatus(301);
        $this->get('/articles/permalink-test')->assertOk();
        $this->get('/wrong/permalink-test')->assertNotFound();
        $this->get('/')->assertOk()->assertSee('/articles/permalink-test');
        $this->getJson('/admin/maintenance/status')->assertOk()->assertJsonPath('data.pending', []);
    }

    public function test_all_themes_render_real_vip_plans_and_theme_configuration(): void
    {
        $this->actingAs(User::where('username', 'admin')->first());
        foreach (Theme::all() as $theme) {
            $this->putJson('/admin/themes/'.$theme->id.'/configuration', ['logo_text' => 'Custom Brand', 'primary_color' => '#21886e', 'footer_text' => 'Custom Footer'])->assertOk();
            app(ThemeManager::class)->activate($theme->slug);
            foreach (['/', '/posts', '/images', '/files', '/vip', '/shop', '/points-store', '/authors', '/links', '/user/editor', '/user/orders'] as $url) {
                $this->get($url)->assertOk()->assertSee('Custom Brand')->assertSee('Custom Footer');
            }
        }
    }

    public function test_link_checker_rejects_internal_addresses_without_http_requests(): void
    {
        Http::fake();
        $link = Link::create(['name' => 'Internal', 'url' => 'http://127.0.0.1/admin', 'status' => 'active']);
        $result = app(LinkChecker::class)->check($link);
        $this->assertSame('failed', $result->status);
        Http::assertNothingSent();
    }

    public function test_vip_upgrade_converts_remaining_value_and_downgrade_does_not_charge(): void
    {
        $this->travelTo(now()->startOfDay());
        $user = User::factory()->create();
        $user->wallet()->create(['balance' => 1000]);
        $lower = VipLevel::where('slug', 'vip')->first();
        $higher = VipLevel::where('slug', 'svip')->first();
        $user->vip()->create(['vip_level_id' => $lower->id, 'started_at' => now(), 'expires_at' => now()->addDays(100)]);
        $orders = app(OrderService::class);
        $orders->payWithBalance($orders->createForVip($higher, $user, 'monthly', 'balance'), $user);
        $this->assertLessThan(90, now()->diffInDays($user->vip()->first()->expires_at));
        $balance = $user->wallet()->first()->balance;
        try {
            $orders->payWithBalance($orders->createForVip($lower, $user, 'monthly', 'balance'), $user);
            $this->fail('Downgrade should not replace an active higher membership');
        } catch (ValidationException $exception) {
            $this->assertEquals($balance, $user->wallet()->first()->balance);
            $this->assertSame($higher->id, $user->vip()->first()->vip_level_id);
        }
    }

    public function test_installation_environment_encoding_round_trips_special_values(): void
    {
        $installer = app(InstallationService::class);
        $method = new \ReflectionMethod($installer, 'envValue');
        foreach (['test$1${PATH}', 'quote"and#hash', 'C:\\example\\path', 'spaces here'] as $value) {
            $encoded = $method->invoke($installer, $value);
            $this->assertSame($value, Dotenv::parse('EXAMPLE='.$encoded)['EXAMPLE']);
        }
        $this->get('/install')->assertRedirect('/admin');
        $this->postJson('/install', [])->assertStatus(409);
    }
}
