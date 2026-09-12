<?php

namespace Tests\Unit;

use App\Support\Zfy\SettingsRegistry;
use PHPUnit\Framework\TestCase;

class SettingsRegistryTest extends TestCase
{
    public function test_fields_are_grouped_and_ordered(): void
    {
        $registry = new SettingsRegistry;
        $registry->setting(['key' => 'site.name', 'group' => 'general', 'position' => 20]);
        $registry->setting(['key' => 'site.url', 'group' => 'general', 'position' => 10]);

        $this->assertSame(['site.url', 'site.name'], array_column($registry->schema()[0]['fields'], 'key'));
    }
}
