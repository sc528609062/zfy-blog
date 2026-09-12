<?php

namespace Tests;

use App\Services\Install\InstallationState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.stores.file.path' => storage_path('framework/testing/unit-cache'), 'cache.stores.file.lock_path' => storage_path('framework/testing/unit-cache')]);
        app('cache')->forgetDriver('file');

        $this->mock(InstallationState::class, function ($mock) {
            $mock->shouldReceive('installed')->andReturn(true);
        });
        $this->withoutVite();
    }
}
