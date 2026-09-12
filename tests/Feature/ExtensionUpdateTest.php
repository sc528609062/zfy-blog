<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\PackageManifestService;
use App\Services\PluginInstaller;
use App\Services\Updates\ExtensionUpdater;
use App\Services\Updates\ReleaseClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use PharData;
use Tests\TestCase;

class ExtensionUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_extension_update_preserves_data_and_detects_local_changes(): void
    {
        $slug = 'signed-test-'.bin2hex(random_bytes(5));
        $work = storage_path('framework/testing/'.$slug);
        File::ensureDirectoryExists($work);
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'config' => base_path('tests/fixtures/openssl.cnf')]);
        $publicKey = openssl_pkey_get_details($key)['key'];
        $updates = [];
        try {
            foreach (['1.0.0', '1.1.0', '1.2.0'] as $version) {
                $manifest = ['schema_version' => 1, 'name' => 'Signed test', 'slug' => $slug, 'version' => $version, 'compatible' => '^1.0', 'permissions' => [], 'events' => [], 'requires' => ['plugins' => [], 'themes' => []]];
                $this->assertSame([], app(PackageManifestService::class)->validatePluginPayload($manifest));
                $json = json_encode($manifest, JSON_THROW_ON_ERROR);
                $zip = new PharData($work.'/'.$version.'.zip');
                $zip->addFromString($slug.'/plugin.json', $json);
                unset($zip);
                $release = json_encode(['schema_version' => 1, 'type' => 'plugin', 'slug' => $slug, 'version' => $version, 'commit' => str_repeat('a', 40), 'sha256' => hash_file('sha256', $work.'/'.$version.'.zip'), 'files' => [$slug.'/plugin.json' => hash('sha256', $json)]], JSON_THROW_ON_ERROR);
                openssl_sign($release, $signature, $key, OPENSSL_ALGO_SHA256);
                File::put($work.'/'.$version.'.json', $release);
                File::put($work.'/'.$version.'.sig', base64_encode($signature));
            }
            $plugin = app(PluginInstaller::class)->install(new UploadedFile($work.'/1.0.0.zip', 'package.zip', 'application/zip', null, true));
            $plugin->settings()->create(['key' => 'custom', 'value' => ['raw' => 'retained']]);
            $updater = app(ExtensionUpdater::class);
            $updater->saveSource('plugin', $slug, ['provider' => 'github', 'repository' => 'example/plugin', 'public_key' => $publicKey]);
            $client = $this->mock(ReleaseClient::class);
            $client->shouldReceive('latest')->andReturn(['version' => '1.1.0', 'notes' => 'Upgrade', 'manifest_url' => $work.'/1.1.0.json', 'signature_url' => $work.'/1.1.0.sig', 'package_url' => $work.'/1.1.0.zip'], ['version' => '1.2.0', 'notes' => '', 'manifest_url' => $work.'/1.2.0.json', 'signature_url' => $work.'/1.2.0.sig', 'package_url' => $work.'/1.2.0.zip']);
            $client->shouldReceive('download')->andReturnUsing(fn ($source, $destination, $limit) => File::copy($source, $destination));
            $updater = app(ExtensionUpdater::class);
            $release = $updater->check('plugin', $slug);
            $updates[] = $release['id'];
            $updater->enqueue('plugin', $slug, $release['id']);
            $this->assertSame('queued', $updater->state($release['id'])['status']);
            $this->assertSame('1.0.0', $plugin->fresh()->version);
            $statePath = storage_path('app/private/extensions/updates/'.$release['id'].'/state.json');
            File::put($statePath, json_encode([...$updater->state($release['id']), 'status' => 'downloading']));
            $updater->apply('plugin', $slug, $release['id']);
            $this->assertSame('1.1.0', $plugin->fresh()->version);
            $this->assertFalse($plugin->fresh()->enabled);
            $this->assertSame('retained', $plugin->settings()->where('key', 'custom')->first()->value['raw']);
            File::put(base_path('plugins/'.$slug.'/custom.txt'), 'local edits');
            $release = $updater->check('plugin', $slug);
            $updates[] = $release['id'];
            try {
                $updater->apply('plugin', $slug, $release['id']);
                $this->fail('Local edits were overwritten.');
            } catch (ValidationException) {
                $this->assertSame('local edits', File::get(base_path('plugins/'.$slug.'/custom.txt')));
                $this->assertSame('1.1.0', $plugin->fresh()->version);
            }
            $this->assertSame($publicKey, Setting::where('key', 'extensions.update.plugin.'.$slug)->first()->value['public_key']);
        } finally {
            foreach ($updates as $id) {
                File::deleteDirectory(storage_path('app/private/extensions/updates/'.$id));
            }
            foreach (File::glob(storage_path('app/private/extensions/versions/'.$slug.'-*')) as $directory) {
                File::deleteDirectory($directory);
            }
            File::deleteDirectory(base_path('plugins/'.$slug));
            File::deleteDirectory($work);
        }
    }
}
