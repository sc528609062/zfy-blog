<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Media;
use App\Models\PointsStoreItem;
use App\Models\User;
use App\Services\PointsStoreService;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PointsAndDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_exchange_is_idempotent_and_respects_stock(): void
    {
        $user = User::factory()->create();
        $user->pointsAccount()->create(['points' => 100]);
        $item = PointsStoreItem::create(['title' => 'Reward', 'slug' => 'reward', 'points_price' => 60, 'stock' => 1]);
        $key = (string) Str::uuid();
        app(PointsStoreService::class)->exchange($user, $item, $key);
        app(PointsStoreService::class)->exchange($user, $item, $key);
        $this->assertSame(40, $user->pointsAccount()->first()->points);
        $this->assertSame(0, $item->fresh()->stock);
        $this->assertDatabaseCount('points_exchange_orders', 1);
        $this->actingAs($user)->postJson('/points-store/'.$item->id.'/exchange', ['request_id' => (string) Str::uuid()])->assertUnprocessable();
    }

    public function test_private_upload_download_requires_owner_and_valid_signature(): void
    {
        $this->seed(CoreInstallSeeder::class);
        Storage::fake('local');
        $content = Content::create(['type' => 'files', 'title' => 'Download', 'slug' => 'download', 'status' => 'published']);
        $admin = User::where('username', 'admin')->first();
        $this->actingAs($admin)->postJson('/admin/contents/'.$content->id.'/attachments', ['file' => UploadedFile::fake()->create('manual.pdf', 10, 'application/pdf')])->assertCreated();
        $user = User::factory()->create();
        $url = $this->actingAs($user)->post('/content/download/download')->assertRedirect()->headers->get('Location');
        $this->get($url)->assertOk();
        $this->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        $this->actingAs($user)->get(str_replace('signature=', 'signature=bad', $url))->assertForbidden();
        $this->assertSame(1, $content->fresh()->download_count);
    }

    public function test_bearer_download_uses_download_ability_and_single_use_grant(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('private/api.pdf', 'document');
        $content = Content::create(['type' => 'files', 'title' => 'API file', 'slug' => 'api-file', 'status' => 'published']);
        $media = Media::create(['name' => 'api.pdf', 'disk' => 'local', 'path' => 'private/api.pdf', 'type' => 'file', 'mime' => 'application/pdf', 'size' => 8]);
        $content->attachments()->create(['media_id' => $media->id, 'name' => 'api.pdf', 'type' => 'file']);
        $user = User::factory()->create();
        $token = $user->createToken('downloader', ['download'])->plainTextToken;
        $url = $this->withToken($token)->postJson('/api/v1/contents/api-file/downloads')->assertOk()->json('data.download_url');
        $this->assertStringContainsString('/api/v1/downloads/', $url);
        $this->withToken($token)->get($url)->assertOk();
        $this->withToken($token)->get($url)->assertForbidden();
        $readToken = $user->createToken('reader', ['read'])->plainTextToken;
        $this->withToken($readToken)->get($url)->assertForbidden();
        $this->assertSame(1, $content->fresh()->download_count);
    }
}
