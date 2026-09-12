<?php

namespace Tests\Feature;

use App\Services\Updates\ReleaseClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReleaseClientTest extends TestCase
{
    public function test_release_platforms_select_stable_semver_and_accept_named_assets(): void
    {
        foreach (['github', 'gitee'] as $provider) {
            $assets = array_map(fn ($name) => ['name' => $name, $provider === 'github' ? 'browser_download_url' : 'url' => 'https://'.$provider.'.com/owner/repo/releases/'.$name], ['package.zip', 'release.json', 'release.sig']);
            Http::fake(['*' => Http::response([
                ['tag_name' => 'v1.9.0', 'assets' => $assets],
                ['tag_name' => 'v1.10.0', 'assets' => $provider === 'github' ? $assets : ['links' => $assets]],
                ['tag_name' => 'v2.0.0-rc.1', 'prerelease' => true, 'assets' => $assets],
                ['tag_name' => 'v3.0.0', 'draft' => true, 'assets' => $assets],
            ])]);
            $release = app(ReleaseClient::class)->latest(['provider' => $provider, 'repository' => 'owner/repo']);
            $this->assertSame('1.10.0', $release['version']);
            $this->assertStringEndsWith('/package.zip', $release['package_url']);
        }
    }

    public function test_untrusted_download_host_is_rejected_before_any_request(): void
    {
        Http::fake();
        try {
            app(ReleaseClient::class)->download('https://127.0.0.1/private', storage_path('framework/testing/unused.zip'), 100);
            $this->fail('Untrusted host was accepted.');
        } catch (ValidationException) {
            Http::assertNothingSent();
        }
    }
}
