<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Content;
use App\Models\User;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreInstallSeeder::class);
    }

    public function test_editor_cannot_open_system_pages_or_change_themes(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('EDITOR');
        $this->actingAs($editor)->getJson('/admin/users')->assertForbidden();
        $this->postJson('/admin/themes/activate', ['slug' => config('zfy.default_theme')])->assertForbidden();
    }

    public function test_settings_are_validated_and_persisted(): void
    {
        $this->actingAs(User::where('username', 'admin')->first());
        $this->putJson('/admin/settings/general', ['values' => ['site.name' => 'My CMS', 'site.url' => 'https://example.com']])->assertOk();
        $this->getJson('/admin/settings-general')->assertJsonPath('payload.settings_schema.0.fields.0.value', 'My CMS');
        $this->putJson('/admin/settings/reading', ['values' => ['reading.page_size' => -1]])->assertUnprocessable();
        $this->putJson('/admin/settings/general', ['values' => ['site.active_theme' => 'invalid']])->assertUnprocessable();
    }

    public function test_category_crud_rejects_hierarchy_cycles(): void
    {
        $this->actingAs(User::where('username', 'admin')->first());
        $parent = $this->postJson('/admin/resources/categories', ['name' => 'Parent', 'slug' => 'parent', 'type' => 'mixed'])->assertCreated()->json('data.id');
        $child = $this->postJson('/admin/resources/categories', ['name' => 'Child', 'slug' => 'child', 'type' => 'mixed', 'parent_id' => $parent])->assertCreated()->json('data.id');
        $this->patchJson('/admin/resources/categories/'.$parent, ['name' => 'Parent', 'slug' => 'parent', 'type' => 'mixed', 'parent_id' => $child])->assertUnprocessable();
        $this->deleteJson('/admin/resources/categories/'.$parent)->assertOk();
        $this->assertNull(Category::findOrFail($child)->parent_id);
    }

    public function test_public_content_is_filtered_and_missing_slugs_return_404(): void
    {
        $draft = Content::create(['type' => 'page', 'title' => 'Secret draft', 'slug' => 'secret', 'status' => 'draft', 'markdown_cache' => 'Private body']);
        $this->get('/p/'.$draft->slug)->assertNotFound();
        $this->get('/content/missing')->assertNotFound();
        $this->getJson('/api/v1/contents/secret')->assertNotFound();
    }
}
