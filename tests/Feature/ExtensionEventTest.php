<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExtensionEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_consumer_receipt_and_database_effect_commit_together(): void
    {
        $apply = fn () => DB::table('settings')->insert(['key' => 'event-result', 'value' => '{}', 'autoload' => false]);
        try {
            zfy_consume_event('example.accounting', 'order.paid:1', function () use ($apply) {
                $apply();
                throw new \RuntimeException('retry');
            });
        } catch (\RuntimeException) {
        }
        $this->assertDatabaseCount('extension_event_receipts', 0);
        $this->assertDatabaseMissing('settings', ['key' => 'event-result']);
        $this->assertTrue(zfy_consume_event('example.accounting', 'order.paid:1', $apply));
        $this->assertFalse(zfy_consume_event('example.accounting', 'order.paid:1', $apply));
        $this->assertDatabaseCount('extension_event_receipts', 1);
        $this->assertDatabaseHas('settings', ['key' => 'event-result']);
    }
}
