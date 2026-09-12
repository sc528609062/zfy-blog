<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_super_admin_can_edit_role_permissions_and_seed_does_not_reset_them(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $role = Role::findByName('EDITOR');
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');
        $this->actingAs($admin)->patchJson('/admin/resources/roles/'.$role->id, ['permissions' => ['manage system']])->assertForbidden();
        $this->actingAs(User::where('username', 'admin')->first())->patchJson('/admin/resources/roles/'.$role->id, ['permissions' => ['manage contents']])->assertOk();
        $this->seed(CoreInstallSeeder::class);
        $this->assertSame(['manage contents'], $role->fresh()->permissions->pluck('name')->all());
        $this->patchJson('/admin/resources/roles/'.Role::findByName('SUPER_ADMIN')->id, ['permissions' => []])->assertUnprocessable();
    }
}
