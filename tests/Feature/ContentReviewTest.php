<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\User;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ContentReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_content_stays_public_until_submitted_revision_is_approved(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $author = User::factory()->create(['is_author' => true, 'author_status' => 'approved']);
        $content = Content::create(['author_id' => $author->id, 'title' => 'Public', 'slug' => 'public', 'type' => 'post', 'status' => 'published', 'published_at' => now()->subDay(), 'markdown_cache' => 'Published original']);
        $this->actingAs($author)->patch('/user/contents/'.$content->id, ['title' => 'Pending title', 'type' => 'post', 'status' => 'pending', 'markdown_cache' => 'Pending body'])->assertRedirect();
        $this->assertSame('Published original', $content->fresh()->markdown_cache);
        $this->assertSame('published', $content->fresh()->status);
        $revision = $content->revisions()->where('kind', 'pending')->firstOrFail();
        $admin = User::where('username', 'admin')->firstOrFail();
        $this->actingAs($admin)->postJson('/admin/contents/'.$content->id.'/revisions/'.$revision->id.'/restore')->assertOk();
        $this->assertSame('Pending body', $content->fresh()->markdown_cache);
        $this->assertSame('reviewed', $revision->fresh()->kind);
    }

    public function test_scheduled_content_is_hidden_until_due_and_validation_can_block_quick_save(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $this->actingAs(User::where('username', 'admin')->firstOrFail());
        $id = $this->postJson('/admin/contents', ['title' => 'Scheduled', 'type' => 'post', 'status' => 'published', 'markdown_cache' => 'Later', 'published_at' => now()->addHour()->toIso8601String()])->assertOk()->json('content.id');
        $this->assertSame('scheduled', Content::find($id)->status);
        $this->assertFalse(Content::published()->whereKey($id)->exists());
        $this->travel(2)->hours();
        $this->artisan('zfy:contents-publish')->assertSuccessful();
        $this->assertTrue(Content::published()->whereKey($id)->exists());
        zfy_on('zfy_content_saving', function () {
            throw ValidationException::withMessages(['title' => 'Rejected by extension']);
        });
        $this->patchJson('/admin/contents/'.$id.'/status', ['status' => 'draft'])->assertUnprocessable();
        $this->assertSame('published', Content::find($id)->status);
    }

    public function test_rejected_revision_does_not_change_public_body_or_allow_later_approval(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $author = User::factory()->create(['is_author' => true, 'author_status' => 'approved']);
        $content = Content::create(['author_id' => $author->id, 'title' => 'Public', 'slug' => 'public-review', 'type' => 'post', 'status' => 'published', 'markdown_cache' => 'Original']);
        $this->actingAs($author)->patch('/user/contents/'.$content->id, ['title' => 'Review', 'type' => 'post', 'status' => 'pending', 'markdown_cache' => 'Review body'])->assertRedirect();
        $revision = $content->revisions()->where('kind', 'pending')->firstOrFail();
        $url = '/admin/contents/'.$content->id.'/revisions/'.$revision->id;
        $this->actingAs(User::where('username', 'admin')->first())->postJson($url.'/reject', ['reason' => 'Needs changes'])->assertOk();
        $this->assertSame('Original', $content->fresh()->markdown_cache);
        $this->assertSame('rejected', $revision->fresh()->kind);
        $this->postJson($url.'/restore')->assertNotFound();
    }
}
