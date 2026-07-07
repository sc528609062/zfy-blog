<?php

namespace Tests\Feature;

use App\Models\UpgradeLog;
use App\Models\User;
use App\Services\SystemUpdateService;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AdminUpdaterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_system_manager_cannot_access_updater_status(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $editor = User::factory()->create(['username' => 'editor']);
        $editor->assignRole('EDITOR');

        $this->actingAs($editor)
            ->getJson(route('admin.updater.status'))
            ->assertForbidden();
    }

    public function test_status_reports_no_tags_message_shape(): void
    {
        $admin = $this->seedAndAdmin();

        $mock = Mockery::mock(SystemUpdateService::class);
        $mock->shouldReceive('status')->once()->andReturn([
            'current_version' => '1.0.0',
            'repository' => SystemUpdateService::REPOSITORY,
            'remote' => 'origin',
            'tags' => [],
            'latest_version' => null,
            'preflight' => ['ok' => false, 'checks' => []],
            'running_log' => null,
            'recent_logs' => [],
        ]);
        $this->app->instance(SystemUpdateService::class, $mock);

        $this->actingAs($admin)
            ->getJson(route('admin.updater.status'))
            ->assertOk()
            ->assertJsonPath('tags', [])
            ->assertJsonPath('latest_version', null);
    }

    public function test_invalid_target_version_returns_validation_error(): void
    {
        $admin = $this->seedAndAdmin();

        $this->actingAs($admin)
            ->postJson(route('admin.updater.run'), ['version' => '1.0.1'])
            ->assertUnprocessable();
    }

    public function test_running_update_blocks_duplicate_start(): void
    {
        $admin = $this->seedAndAdmin();

        $mock = Mockery::mock(SystemUpdateService::class);
        $mock->shouldReceive('start')->once()->with('v1.0.1')->andThrow(new \RuntimeException('已有更新任务正在运行，请等待完成后再重试。'));
        $this->app->instance(SystemUpdateService::class, $mock);

        $this->actingAs($admin)
            ->postJson(route('admin.updater.run'), ['version' => 'v1.0.1'])
            ->assertUnprocessable()
            ->assertJsonPath('message', '已有更新任务正在运行，请等待完成后再重试。');
    }

    public function test_update_log_can_be_returned_by_status(): void
    {
        $admin = $this->seedAndAdmin();
        $log = UpgradeLog::query()->create([
            'from_version' => '1.0.0',
            'to_version' => 'v1.0.1',
            'status' => 'running',
            'log' => '正在更新',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.updater.log', $log))
            ->assertOk()
            ->assertJsonPath('log.status', 'running')
            ->assertJsonPath('log.to_version', 'v1.0.1')
            ->assertJsonPath('log.log', '正在更新');
    }

    private function seedAndAdmin(): User
    {
        $this->seed(CoreInstallSeeder::class);

        return User::where('email', 'admin@zfy-blog.test')->firstOrFail();
    }
}
