<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\User;
use App\Services\NavigationService;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_hierarchy_visibility_and_roundtrip_are_preserved(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $admin = User::where('username', 'admin')->first();
        $payload = ['name' => 'Nested', 'location' => 'primary', 'items' => [
            ['title' => 'Public', 'url' => '/', 'depth' => 0],
            ['title' => 'Members', 'url' => '/user', 'depth' => 1, 'visibility' => 'member'],
            ['title' => 'Child', 'url' => '/user/orders', 'depth' => 2],
            ['title' => 'Guests', 'url' => '/login', 'depth' => 0, 'visibility' => 'guest'],
        ]];
        Menu::where('location', 'primary')->delete();
        $this->actingAs($admin)->postJson('/admin/resources/menus', $payload)->assertCreated();
        $menu = app(NavigationService::class)->forViewer(null)['primary'];
        $this->assertCount(2, $menu->items);
        $this->assertCount(0, $menu->items[0]->children);
        $menu = app(NavigationService::class)->forViewer($admin)['primary'];
        $this->assertCount(1, $menu->items);
        $this->assertSame('Child', $menu->items[0]->children[0]->children[0]->title);
        $rows = $this->getJson('/admin/resources/menus')->assertOk()->json('data.data');
        $this->assertSame([0, 1, 2, 0], array_column(collect($rows)->firstWhere('location', 'primary')['items'], 'depth'));
        $payload['items'][0]['depth'] = 2;
        $this->patchJson('/admin/resources/menus/'.$menu->id, $payload)->assertUnprocessable();
    }
}
