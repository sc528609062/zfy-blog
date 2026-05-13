<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\User;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminMediaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_library_lists_existing_images_and_supports_search(): void
    {
        $admin = $this->seedAndAdmin();

        Storage::fake('media');

        Media::create([
            'folder_id' => null,
            'user_id' => $admin->id,
            'disk' => 'public',
            'type' => 'image',
            'name' => '封面图',
            'path' => 'media/editor/covers/2026/05/cover.jpg',
            'mime' => 'image/jpeg',
            'size' => 2048,
            'metadata' => [
                'directory' => 'editor/covers',
                'storage_directory' => 'media/editor/covers/2026/05',
            ],
        ]);

        Media::create([
            'folder_id' => null,
            'user_id' => $admin->id,
            'disk' => 'public',
            'type' => 'image',
            'name' => '正文图',
            'path' => 'media/editor/images/2026/05/image.jpg',
            'mime' => 'image/jpeg',
            'size' => 1024,
            'metadata' => [
                'directory' => 'editor/images',
                'storage_directory' => 'media/editor/images/2026/05',
            ],
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.media.library', ['q' => '封面', 'type' => 'image', 'per_page' => 24]))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('items.0.name', '封面图')
            ->assertJsonPath('items.0.directory', 'editor/covers')
            ->assertJsonPath('items.0.url', url('/media/editor/covers/2026/05/cover.jpg'));
    }

    public function test_media_upload_stores_to_configured_directory_and_returns_payload(): void
    {
        $admin = $this->seedAndAdmin();

        Storage::fake('media');
        Carbon::setTestNow('2026-05-13 12:00:00');

        try {
            $response = $this->actingAs($admin)
                ->postJson(route('admin.media.upload'), [
                    'file' => UploadedFile::fake()->image('cover.jpg', 800, 600),
                    'directory' => 'editor/covers',
                ])
                ->assertCreated();

            $path = (string) $response->json('media.path');
            $url = (string) $response->json('media.url');

            $response
                ->assertJsonPath('media.directory', 'editor/covers')
                ->assertJsonPath('media.disk', 'media')
                ->assertJsonPath('media.storage_directory', 'media/editor/covers/2026/05');

            Storage::disk('media')->assertExists($path);
            $this->assertStringContainsString('editor/covers/2026/05/', $path);
            $this->assertStringStartsWith(url('/media/editor/covers/2026/05/'), $url);
            $this->assertStringNotContainsString('/storage/media/', $url);

            $assetResponse = $this->get(parse_url($url, PHP_URL_PATH) ?: $url)
                ->assertOk();
            $this->assertStringContainsString('max-age=31536000', (string) $assetResponse->headers->get('cache-control'));

            $this->get('/storage/media/'.$path)
                ->assertRedirect(url('/media/'.$path));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_editor_payload_exposes_media_configuration(): void
    {
        $admin = $this->seedAndAdmin();

        $this->actingAs($admin)
            ->get('/admin/editor')
            ->assertOk()
            ->assertSee('media_upload', false)
            ->assertSee('"disk":"media"', false)
            ->assertSee('"storageRoot":"media"', false)
            ->assertSee('"defaultDirectory":"editor\\/images"', false)
            ->assertSee('"directories":[{"value":"editor\\/images"', false)
            ->assertSee('"uploadMaxKb":20480', false);
    }

    private function seedAndAdmin(): User
    {
        $this->seed(CoreInstallSeeder::class);

        return User::where('email', 'admin@zfy-blog.test')->firstOrFail();
    }
}
