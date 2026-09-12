<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Product;
use App\Models\User;
use App\Services\CommerceOperations;
use App\Services\OrderService;
use App\Services\ProductOrderService;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class MySqlConcurrencyTest extends TestCase
{
    protected function tearDown(): void
    {
        // Child processes commit outside PHPUnit transactions. Force the next RefreshDatabase test to rebuild.
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::getDriverName() !== 'mysql' || DB::getDatabaseName() !== 'zfy_isolated_test' || (string) config('database.connections.mysql.port') !== '13307') {
            $this->markTestSkipped('Requires dedicated MySQL test instance.');
        }
        $this->artisan('migrate:fresh', ['--force' => true]);
        $this->seed(CoreInstallSeeder::class);
    }

    private function race(string $action, int $id, array $users): array
    {
        $workers = array_map(fn ($user) => new Process([PHP_BINARY, '-d', 'disable_functions=', base_path('scripts/concurrency-worker.php'), $action, (string) $id, (string) $user->id], base_path()), $users);
        foreach ($workers as $worker) {
            $worker->setTimeout(30);
            $worker->start();
        }
        foreach ($workers as $worker) {
            $worker->wait();
            $this->assertContains($worker->getExitCode(), [0, 3], $worker->getOutput().$worker->getErrorOutput());
        }

        return array_map(fn ($worker) => $worker->getExitCode(), $workers);
    }

    public function test_concurrent_buyers_cannot_overreserve_last_stock(): void
    {
        $product = Product::create(['title' => 'Last item', 'slug' => 'last-item', 'status' => 'published', 'type' => 'digital', 'price' => '10.00', 'stock_strategy' => 'limited']);
        DB::table('stock_items')->insert(['product_id' => $product->id, 'quantity' => 1, 'type' => 'inventory', 'created_at' => now(), 'updated_at' => now()]);
        $codes = $this->race('reserve', $product->id, User::factory()->count(4)->create()->all());
        $this->assertCount(1, array_filter($codes, fn ($code) => $code === 0));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('stock_items', ['product_id' => $product->id, 'reserved' => 1, 'quantity' => 1]);
    }

    public function test_authors_can_pay_each_other_without_inverting_wallet_locks(): void
    {
        $authors = User::factory()->count(2)->create(['is_author' => true, 'author_status' => 'approved']);
        foreach ($authors as $author) {
            $author->wallet()->create(['balance' => '100.00']);
        }
        $workers = [];
        foreach ($authors as $index => $author) {
            $buyer = $authors[1 - $index];
            $content = Content::create(['author_id' => $author->id, 'title' => 'Mutual purchase', 'slug' => 'mutual-'.$index, 'status' => 'published', 'type' => 'post', 'pricing' => ['price' => '20.00']]);
            $order = app(OrderService::class)->createForContent($content, $buyer, 'balance');
            $workers[] = new Process([PHP_BINARY, '-d', 'disable_functions=', base_path('scripts/concurrency-worker.php'), 'pay', (string) $order->id, (string) $buyer->id], base_path());
        }
        foreach ($workers as $worker) {
            $worker->setTimeout(30)->start();
        }
        foreach ($workers as $worker) {
            $worker->wait();
            $this->assertSame(0, $worker->getExitCode(), $worker->getOutput().$worker->getErrorOutput());
        }
        $this->assertSame(2, DB::table('orders')->where('status', 'paid')->count());
        $this->assertDatabaseCount('author_earnings', 2);
    }

    public function test_concurrent_payment_refund_and_withdrawal_are_idempotent(): void
    {
        $author = User::factory()->create(['is_author' => true, 'author_status' => 'approved']);
        $buyer = User::factory()->create();
        $buyer->wallet()->create(['balance' => '100.00']);
        $content = Content::create(['author_id' => $author->id, 'title' => 'Paid', 'slug' => 'paid-race', 'status' => 'published', 'type' => 'post', 'pricing' => ['price' => 20]]);
        $order = app(OrderService::class)->createForContent($content, $buyer, 'balance');
        $this->race('pay', $order->id, [$buyer, $buyer, $buyer]);
        $this->assertSame('80.00', $buyer->wallet()->first()->balance);
        $this->assertDatabaseCount('author_earnings', 1);
        $refund = app(CommerceOperations::class)->requestRefund($buyer, $order->fresh(), 'Concurrent refund');
        $this->race('refund', $refund->id, [$buyer, $buyer]);
        $this->assertSame('100.00', $buyer->wallet()->first()->balance);
        $this->assertSame('0.00', $author->wallet()->first()->balance);
        $this->race('withdraw', 0, [$buyer, $buyer]);
        $this->assertDatabaseCount('author_withdrawals', 1);
        $this->assertSame('70.00', $buyer->wallet()->first()->balance);
        $this->assertSame('30.00', $buyer->wallet()->first()->frozen_balance);
    }

    public function test_concurrent_mixed_payment_and_partial_refund_debit_each_tender_once(): void
    {
        $buyer = User::factory()->create();
        $buyer->wallet()->create(['balance' => '100.00']);
        $buyer->pointsAccount()->create(['points' => 500]);
        $product = Product::create(['title' => 'Mixed race', 'slug' => 'mixed-race', 'status' => 'published', 'type' => 'digital', 'price' => '20.00', 'stock_strategy' => 'unlimited']);
        $order = app(ProductOrderService::class)->create($product, $buyer, ['quantity' => 1, 'gateway' => 'balance']);
        $this->race('pay-mixed', $order->id, [$buyer, $buyer, $buyer]);
        $this->assertSame('85.00', $buyer->wallet()->first()->balance);
        $this->assertEquals(450, $buyer->pointsAccount()->first()->points);
        $refund = app(CommerceOperations::class)->requestRefund($buyer, $order, 'Partial race', '10.00');
        $this->race('refund', $refund->id, [$buyer, $buyer]);
        $this->assertSame('92.50', $buyer->wallet()->first()->balance);
        $this->assertEquals(475, $buyer->pointsAccount()->first()->points);
        $this->assertDatabaseCount('points_transactions', 2);
        $this->assertSame('paid', $order->fresh()->status);
    }
}
