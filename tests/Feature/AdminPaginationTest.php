<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreInstallSeeder::class);
        $this->actingAs(User::where('username', 'admin')->firstOrFail());
    }

    public function test_content_filters_pagination_and_deleted_last_page(): void
    {
        $ids = [];
        for ($i = 1; $i <= 25; $i++) {
            $ids[] = Content::create(['title' => 'Pagination '.$i, 'slug' => 'pagination-'.$i, 'type' => 'images', 'status' => 'scheduled'])->id;
        }
        Content::create(['title' => 'Pagination excluded', 'slug' => 'pagination-page', 'type' => 'page', 'status' => 'scheduled']);
        Content::create(['title' => 'Pagination excluded', 'slug' => 'pagination-draft', 'type' => 'images', 'status' => 'draft']);
        $url = '/admin/contents?q=Pagination&type=images&status=scheduled&per_page=10';
        $first = $this->getJson($url)->assertOk()->assertJsonPath('payload.content_pagination.total', 25)->assertJsonCount(10, 'payload.data_rows')->json('payload.data_rows');
        $second = $this->getJson($url.'&page=2')->assertOk()->assertJsonPath('payload.content_pagination.current_page', 2)->json('payload.data_rows');
        $this->assertSame(array_slice(array_reverse($ids), 0, 10), array_column($first, 'id'));
        $this->assertSame(array_slice(array_reverse($ids), 10, 10), array_column($second, 'id'));
        $this->getJson($url.'&page=999')->assertOk()->assertJsonPath('payload.content_pagination.current_page', 3)->assertJsonCount(5, 'payload.data_rows');
        Content::whereIn('id', array_slice($ids, 0, 5))->delete();
        $this->getJson($url.'&page=3')->assertOk()->assertJsonPath('payload.content_pagination.current_page', 2)->assertJsonPath('payload.content_pagination.total', 20)->assertJsonCount(10, 'payload.data_rows');
        $this->getJson('/admin/contents?q=no-match&page=9')->assertOk()->assertJsonPath('payload.content_pagination.current_page', 1)->assertJsonCount(0, 'payload.data_rows');
        $this->getJson('/admin/pages?status=scheduled')->assertOk()->assertJsonCount(1, 'payload.data_rows');
    }

    public function test_resource_page_size_search_and_bounds_are_real(): void
    {
        User::factory()->count(23)->create(['name' => 'Pagination user']);
        $this->getJson('/admin/resources/users?q=Pagination&per_page=10&page=3')->assertOk()->assertJsonPath('data.total', 23)->assertJsonPath('data.per_page', 10)->assertJsonCount(3, 'data.data');
        $this->getJson('/admin/resources/users?q=Pagination&per_page=50')->assertOk()->assertJsonCount(23, 'data.data');
        $this->getJson('/admin/resources/users?q=Pagination&per_page=10&page=99')->assertOk()->assertJsonPath('data.current_page', 3);
    }

    public function test_commerce_filters_page_size_and_all_sections(): void
    {
        $user = User::factory()->create(['name' => 'Pagination buyer']);
        for ($i = 1; $i <= 23; $i++) {
            Order::create(['order_no' => 'PAGINATION-'.$i, 'user_id' => $user->id, 'type' => 'content', 'status' => 'pending', 'total_amount' => '1.00', 'pay_channel' => 'balance']);
        }
        $this->getJson('/admin/commerce/orders?q=Pagination%20buyer&status=pending&per_page=10&page=3')->assertOk()->assertJsonPath('data.total', 23)->assertJsonCount(3, 'data.data');
        $this->getJson('/admin/commerce/orders?status=paid')->assertOk()->assertJsonCount(0, 'data.data');
        foreach (['refunds', 'commissions', 'withdrawals', 'points-exchanges'] as $section) {
            $this->getJson('/admin/commerce/'.$section.'?q='.$user->id.'&per_page=50&page=9')->assertOk()->assertJsonPath('data.current_page', 1)->assertJsonPath('data.per_page', 50);
        }
        $this->getJson('/admin/link-tools/checks?per_page=10&page=9')->assertOk()->assertJsonPath('data.current_page', 1)->assertJsonPath('data.per_page', 10);
    }

    public function test_invalid_pagination_is_rejected_and_permissions_are_preserved(): void
    {
        foreach (['/admin/contents', '/admin/resources/users', '/admin/commerce/orders', '/admin/link-tools/checks', '/admin/maintenance/status'] as $url) {
            foreach (['per_page=101', 'per_page=0', 'page=-1', 'page=abc', 'page=1000001'] as $parameters) {
                $this->getJson($url.'?'.$parameters)->assertUnprocessable();
            }
        }
        $this->getJson('/admin/contents?type=invalid')->assertUnprocessable();
        $this->getJson('/admin/contents?status=invalid')->assertUnprocessable();
        $this->actingAs(User::factory()->create());
        foreach (['/admin/contents', '/admin/resources/users', '/admin/commerce/orders', '/admin/link-tools/checks'] as $url) {
            $this->getJson($url.'?per_page=100')->assertForbidden();
        }
    }

    public function test_maintenance_history_is_not_limited_to_the_latest_twenty_records(): void
    {
        for ($i = 1; $i <= 23; $i++) {
            DB::table('upgrade_logs')->insert(['from_version' => '1.0.0', 'to_version' => '1.0.'.$i, 'status' => 'completed', 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->getJson('/admin/maintenance/status?per_page=10&page=3')->assertOk()
            ->assertJsonPath('data.logs_meta.total', 23)->assertJsonPath('data.logs_meta.page', 3)
            ->assertJsonCount(3, 'data.logs')->assertJsonPath('data.logs.0.to_version', '1.0.3');
        $this->getJson('/admin/maintenance/status?per_page=50')->assertOk()->assertJsonCount(23, 'data.logs');
    }
}
