<?php

namespace Tests\Unit;

use App\Support\Zfy\FilterBus;
use App\Support\Zfy\HookBus;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class HookBusTest extends TestCase
{
    public function test_priority_arguments_removal_and_aliases(): void
    {
        $bus = new HookBus;
        $seen = [];
        $removed = $bus->on('content.published', function () use (&$seen) {
            $seen[] = 'removed';
        });
        $bus->on('zfy_content_published', function ($value) use (&$seen, $bus) {
            $seen[] = [$value, func_num_args(), $bus->current()];
        }, 5, 1);
        $bus->on('content.published', function () use ($bus, $removed) {
            $bus->remove('content.published', $removed);
        }, 1, 0);
        $bus->emit('content.published', 42, 'ignored');
        $this->assertSame([[42, 1, 'zfy_content_published']], $seen);
        $this->assertNull($bus->current());
        $this->assertSame(1, $bus->did('content.published'));
    }

    public function test_once_is_removed_before_recursive_dispatch(): void
    {
        $bus = new HookBus;
        $calls = 0;
        $bus->once('event', function () use ($bus, &$calls) {
            $calls++;
            $bus->emit('event');
        });
        $bus->emit('event');
        $this->assertSame(1, $calls);
        $this->assertSame(2, $bus->did('event'));
        $this->assertFalse($bus->has('event'));
    }

    public function test_filter_chain_and_strict_exception_restore_stack(): void
    {
        $bus = new FilterBus;
        $bus->filter('price', fn ($value, $factor) => $value * $factor, 10, 2);
        $bus->filter('price', fn ($value) => $value + 1, 20, 1);
        $this->assertSame(7, $bus->apply('price', 2, 3));
        $bus->filter('price', fn () => throw new RuntimeException('Rejected'), 30);
        try {
            $bus->applyStrict('price', 2, 3);
            $this->fail('Validation exception must propagate');
        } catch (RuntimeException $exception) {
            $this->assertSame('Rejected', $exception->getMessage());
        }
        $this->assertNull($bus->current());
    }
}
