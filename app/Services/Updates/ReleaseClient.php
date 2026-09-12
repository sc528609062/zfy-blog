<?php

namespace App\Services\Updates;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class ReleaseClient
{
    public function latest(array $source): array
    {
        $provider = $source['provider'] ?? '';
        $repository = $source['repository'] ?? '';
        if (! in_array($provider, ['github', 'gitee'], true) || ! preg_match('~^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$~', $repository)) {
            $this->fail('Invalid update repository.');
        }
        $url = $provider === 'github' ? 'https://api.github.com/repos/'.$repository.'/releases' : 'https://gitee.com/api/v5/repos/'.$repository.'/releases';
        $request = Http::acceptJson()->timeout(15)->withHeaders(['User-Agent' => 'zfy-blog-updater'])->withOptions(['allow_redirects' => false]);
        if (! empty($source['token'])) {
            $request = $request->withToken($source['token']);
        }
        $releases = $request->get($url, ['per_page' => 30])->throw()->json();
        $candidates = collect($releases)->filter(fn ($release) => is_array($release) && empty($release['draft']) && empty($release['prerelease']) && preg_match('/^v?\d+\.\d+\.\d+$/', $release['tag_name'] ?? ''))->sort(fn ($a, $b) => version_compare(ltrim($b['tag_name'], 'v'), ltrim($a['tag_name'], 'v')));
        $release = $candidates->first();
        if (! $release) {
            $this->fail('No stable release is available.');
        }
        $assets = collect(data_get($release, 'assets.links', $release['assets'] ?? []))->filter(fn ($asset) => is_array($asset) && is_string($asset['name'] ?? null))->keyBy('name');
        $manifest = $assets->get('release.json');
        $signature = $assets->get('release.sig');
        $package = $assets->get('package.zip');
        if (! $manifest || ! $signature || ! $package) {
            $this->fail('Release must contain release.json, release.sig and package.zip.');
        }

        $assetUrl = function ($asset) {
            $url = $asset['browser_download_url'] ?? $asset['url'] ?? null;
            if (! is_string($url) || ! str_starts_with($url, 'https://')) {
                $this->fail('Release asset URL is missing or insecure.');
            }

            return $url;
        };

        return ['version' => ltrim($release['tag_name'], 'v'), 'tag' => $release['tag_name'], 'notes' => $release['body'] ?? '', 'published_at' => $release['published_at'] ?? null, 'manifest_url' => $assetUrl($manifest), 'signature_url' => $assetUrl($signature), 'package_url' => $assetUrl($package)];
    }

    public function download(string $url, string $path, int $limit): void
    {
        for ($redirects = 0; $redirects <= 5; $redirects++) {
            $parts = parse_url($url);
            $host = strtolower($parts['host'] ?? '');
            $allowed = ['github.com', 'api.github.com', 'objects.githubusercontent.com', 'release-assets.githubusercontent.com', 'gitee.com'];
            if (($parts['scheme'] ?? '') !== 'https' || ! in_array($host, $allowed, true) || isset($parts['user']) || isset($parts['pass']) || (isset($parts['port']) && $parts['port'] !== 443)) {
                $this->fail('Untrusted release download host.');
            }
            $response = Http::timeout(config('updates.timeout'))->withOptions([
                'allow_redirects' => false, 'sink' => $path,
                'progress' => function ($total, $downloaded) use ($limit) {
                    if ($total > $limit || $downloaded > $limit) {
                        throw new \RuntimeException('Release download exceeds size limit.');
                    }
                },
            ])->get($url);
            if ($response->redirect()) {
                $url = $response->header('Location');

                continue;
            }
            $response->throw();
            if (! is_file($path) || filesize($path) > $limit) {
                $this->fail('Invalid downloaded asset.');
            }

            return;
        }
        $this->fail('Too many release redirects.');
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['update' => $message]);
    }
}
