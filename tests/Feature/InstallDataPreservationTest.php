<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Models\VipLevel;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallDataPreservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reseeding_preserves_credentials_settings_and_balances(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $admin = User::where('email', 'admin@zfy-blog.test')->firstOrFail();
        $admin->update(['name' => 'Changed', 'password' => 'changed-password']);
        $password = $admin->password;
        $admin->wallet->update(['balance' => '12.34']);
        Setting::where('key', 'site.name')->update(['value' => ['raw' => 'Changed site']]);
        VipLevel::where('slug', 'vip')->update(['price_monthly' => '42.00']);
        $this->seed(CoreInstallSeeder::class);
        $this->assertSame($password, $admin->fresh()->password);
        $this->assertSame('Changed', $admin->fresh()->name);
        $this->assertSame('12.34', $admin->wallet->fresh()->balance);
        $this->assertSame(['raw' => 'Changed site'], Setting::where('key', 'site.name')->first()->value);
        $this->assertSame('42.00', VipLevel::where('slug', 'vip')->first()->price_monthly);
    }
}
