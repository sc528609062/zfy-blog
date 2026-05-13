<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config([
            'app.name' => 'zfy-blog',
            'zfy.installed' => true,
        ]);
        File::ensureDirectoryExists(storage_path('app/zfy'));
        File::put(storage_path('app/zfy/install.lock'), '{}');
    }
}
