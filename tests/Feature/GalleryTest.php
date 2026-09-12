<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Content;
use App\Models\User;
use App\Services\GalleryService;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_gallery_preview_is_private_and_original_download_obeys_current_permissions(): void
    {
        Storage::fake('local');
        config(['filesystems.downloads' => 'local']);
        $this->seed(CoreInstallSeeder::class);
        $admin = User::where('username', 'admin')->firstOrFail();
        $content = Content::create(['type' => 'images', 'title' => 'Gallery', 'slug' => 'gallery', 'status' => 'published']);
        $endpoint = '/admin/contents/'.$content->id.'/gallery';
        $this->actingAs($admin)->postJson($endpoint, ['file' => UploadedFile::fake()->image('photo.png', 100, 80)])->assertCreated();
        $attachment = Attachment::where('role', 'gallery')->firstOrFail();
        Storage::disk('local')->assertExists($attachment->media->path);
        Storage::disk('local')->assertExists($attachment->media->metadata['preview_path']);
        $item = app(GalleryService::class)->items($content, $admin, true)[0];
        $item['visibility'] = 'member';
        $this->putJson($endpoint, ['items' => [$item]])->assertOk();
        $this->app['auth']->forgetGuards();
        $this->get('/gallery/'.$attachment->id)->assertForbidden();
        $this->getJson('/api/v1/contents/gallery')->assertOk()->assertJsonCount(0, 'data.gallery');
        $buyer = User::factory()->create();
        $this->actingAs($buyer)->get('/gallery/'.$attachment->id)->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->postJson('/content/gallery/download', ['attachment_id' => $attachment->id])->assertForbidden();
        $this->actingAs($admin);
        $item['original_download'] = true;
        $this->putJson($endpoint, ['items' => [$item]])->assertOk();
        $download = $this->actingAs($buyer)->postJson('/content/gallery/download', ['attachment_id' => $attachment->id])->assertRedirect()->headers->get('Location');
        $this->get($download)->assertOk();
        $this->get($download)->assertForbidden();
        $this->actingAs($admin);
        $item['visibility'] = 'vip';
        $this->putJson($endpoint, ['items' => [$item]])->assertOk();
        $this->actingAs($buyer)->get('/gallery/'.$attachment->id)->assertForbidden();
        $this->postJson('/content/gallery/download', ['attachment_id' => $attachment->id])->assertForbidden();
    }

    public function test_gallery_cannot_reorder_other_content_or_export_restricted_cover(): void
    {
        Storage::fake('local');
        config(['filesystems.downloads' => 'local']);
        $this->seed(CoreInstallSeeder::class);
        $admin = User::where('username', 'admin')->firstOrFail();
        $first = Content::create(['type' => 'images', 'title' => 'One', 'slug' => 'one', 'status' => 'published']);
        $second = Content::create(['type' => 'images', 'title' => 'Two', 'slug' => 'two', 'status' => 'published']);
        $gallery = app(GalleryService::class);
        $attachment = $gallery->upload($first, $admin, UploadedFile::fake()->image('one.png'));
        $item = $gallery->items($first, $admin, true)[0];
        $this->actingAs($admin)->putJson('/admin/contents/'.$second->id.'/gallery', ['items' => [$item]])->assertUnprocessable();
        $item['visibility'] = 'vip';
        $this->putJson('/admin/contents/'.$first->id.'/gallery', ['items' => [$item], 'cover_id' => $item['id']])->assertUnprocessable();
        $this->assertSame('public', $attachment->fresh()->meta['visibility']);
        $this->actingAs(User::factory()->create())->getJson('/admin/contents/'.$first->id.'/gallery')->assertForbidden();
    }
}
