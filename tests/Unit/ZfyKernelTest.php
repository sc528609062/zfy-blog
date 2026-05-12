<?php

namespace Tests\Unit;

use App\Support\Zfy\AdminRegistry;
use App\Support\Zfy\FilterBus;
use App\Support\Zfy\HookBus;
use PHPUnit\Framework\TestCase;

class ZfyKernelTest extends TestCase
{
    public function test_hooks_and_filters_run_by_priority(): void
    {
        $hooks = new HookBus();
        $events = [];

        $hooks->on('demo.ready', function () use (&$events) {
            $events[] = 'late';
        }, 20);
        $hooks->on('demo.ready', function () use (&$events) {
            $events[] = 'early';
        }, 5);
        $hooks->emit('demo.ready');

        $this->assertSame(['early', 'late'], $events);

        $filters = new FilterBus();
        $filters->filter('demo.label', fn (string $value) => $value.'A', 20);
        $filters->filter('demo.label', fn (string $value) => $value.'B', 5);

        $this->assertSame('ZBA', $filters->apply('demo.label', 'Z'));
    }

    public function test_admin_registry_builds_grouped_menu(): void
    {
        $registry = new AdminRegistry();
        $registry->group(['key' => 'content', 'label' => '内容', 'icon' => 'Document', 'position' => 20]);
        $registry->page(['key' => 'contents', 'label' => '所有文章', 'description' => '管理文章', 'group' => 'content']);

        $menu = $registry->menuFor();

        $this->assertSame('内容', $menu[0]['label']);
        $this->assertSame('contents', $menu[0]['items'][0]['key']);
    }
}
