<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\User;
use Database\Seeders\CmsDemoSeeder;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_import_is_repeatable_and_preserves_existing_accounts_and_edits(): void
    {
        Storage::fake('local');
        $this->seed(CoreInstallSeeder::class);
        $admin = User::where('username', 'admin')->first();
        $password = $admin->password;
        $admin->wallet()->update(['balance' => 7]);
        $this->seed(CmsDemoSeeder::class);
        $content = Content::where('slug', 'demo-content-1')->firstOrFail();
        $content->update(['title' => 'Edited by owner']);
        $this->seed(CmsDemoSeeder::class);
        $this->assertDatabaseCount('contents', 17);
        $this->assertDatabaseCount('card_codes', 5);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('attachments', 4);
        $this->assertSame('Edited by owner', $content->fresh()->title);
        $this->assertSame($password, $admin->fresh()->password);
        $this->assertEquals(7, $admin->wallet()->first()->balance);
        $this->get('/')->assertOk();
        $this->get('/topic/demo-getting-started')->assertOk();
        $this->get('/vip')->assertOk()->assertSee('标准会员');
    }
}
