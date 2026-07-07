<?php

namespace Tests\Unit;

use App\Services\SystemUpdateService;
use Tests\TestCase;

class SystemUpdateServiceTest extends TestCase
{
    public function test_tags_are_sorted_by_semantic_version_descending(): void
    {
        $service = app(SystemUpdateService::class);

        $this->assertSame(
            ['v1.0.10', 'v1.0.2', 'v1.0.1'],
            $service->sortedTags(['v1.0.1', 'invalid', 'v1.0.10', 'v1.0.2'])
        );
    }

    public function test_composer_command_falls_back_to_project_composer_phar(): void
    {
        $service = app(SystemUpdateService::class);

        $command = $service->composerCommand();

        $this->assertNotEmpty($command);
        $this->assertContains('composer.phar', $command);
    }
}
