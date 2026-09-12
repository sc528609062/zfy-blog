<?php

namespace App\Services\Updates;

use App\Models\Plugin;
use App\Models\Setting;
use App\Models\Theme;
use App\Services\ExtensionMaintenance;
use App\Services\PackageManifestService;
use App\Services\PluginInstaller;
use App\Services\ThemeInstaller;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ExtensionUpdater
{
    public function __construct(private ReleaseClient $client, private ReleaseVerifier $verifier) {}

    public function package(string $type, string $slug): Plugin|Theme
    {
        abort_unless(in_array($type, ['plugin', 'theme'], true) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug), 404);

        return ($type === 'plugin' ? Plugin::query() : Theme::query())->where('slug', $slug)->firstOrFail();
    }

    public function source(string $type, string $slug): array
    {
        $this->package($type, $slug);
        $stored = Setting::where('key', 'extensions.update.'.$type.'.'.$slug)->value('value');
        $manifest = $type === 'plugin' ? app(PackageManifestService::class)->plugins() : app(PackageManifestService::class)->themes();

        return array_replace(['provider' => 'github', 'repository' => '', 'public_key' => ''], Arr::only($manifest[$slug]['update'] ?? [], ['provider', 'repository']), $stored ?? []);
    }

    public function saveSource(string $type, string $slug, array $source): void
    {
        $this->package($type, $slug);
        if (! openssl_pkey_get_public($source['public_key'])) {
            $this->fail('发布公钥格式无效。');
        }
        Setting::updateOrCreate(['key' => 'extensions.update.'.$type.'.'.$slug], ['value' => $source, 'autoload' => false]);
    }

    public function check(string $type, string $slug): array
    {
        $package = $this->package($type, $slug);
        $source = $this->source($type, $slug);
        if (empty($source['public_key'])) {
            $this->fail('请先由系统管理员固定发布者公钥。');
        }
        $cacheKey = 'zfy.extension-release.'.$type.'.'.$slug.'.'.hash('sha256', json_encode($source).$package->version);
        if ($cached = Cache::store('file')->get($cacheKey)) {
            return $cached;
        }
        $release = $this->client->latest($source);
        if (version_compare($release['version'], $package->version, '<=')) {
            return ['available' => false, 'version' => $package->version];
        }
        $id = (string) Str::uuid();
        $work = $this->directory($id);
        File::ensureDirectoryExists($work, 0700);
        try {
            $this->client->download($release['manifest_url'], $work.'/release.json', 8 * 1024 * 1024);
            $this->client->download($release['signature_url'], $work.'/release.sig', 8192);
            $manifest = $this->verifier->manifest(File::get($work.'/release.json'), File::get($work.'/release.sig'), $source['public_key']);
            $this->identity($manifest, $type, $slug, $package->version);
            if ($manifest['version'] !== $release['version']) {
                $this->fail('Release 标签与签名版本不一致。');
            }
            File::put($work.'/state.json', json_encode(['type' => $type, 'slug' => $slug, 'version' => $manifest['version'], 'package_url' => $release['package_url'], 'status' => 'ready', 'created_at' => now()->toIso8601String()], JSON_THROW_ON_ERROR));
            $result = ['available' => true, 'id' => $id, 'version' => $manifest['version'], 'notes' => $release['notes']];
            Cache::store('file')->put($cacheKey, $result, now()->addHours(12));

            return $result;
        } catch (\Throwable $exception) {
            File::deleteDirectory($work);
            throw $exception;
        }
    }

    public function apply(string $type, string $slug, string $id): void
    {
        Cache::store('file')->lock('zfy-extension-update-'.$id, 1800)->block(1, fn () => $this->applyLocked($type, $slug, $id));
    }

    public function enqueue(string $type, string $slug, string $id): void
    {
        $package = $this->package($type, $slug);
        if ($type === 'plugin' ? $package->enabled : $package->is_active) {
            $this->fail('请先停用扩展。');
        }
        Cache::store('file')->lock('zfy-extension-update-'.$id, 1800)->block(1, function () use ($type, $slug, $id) {
            $state = $this->state($id);
            if ($state['type'] !== $type || $state['slug'] !== $slug || ! in_array($state['status'], ['ready', 'queued'], true)) {
                $this->fail('更新任务不可执行。');
            }
            $this->writeState($id, [...$state, 'status' => 'queued']);
        });
    }

    public function state(string $id): array
    {
        $path = $this->directory($id).'/state.json';
        if (! is_file($path)) {
            $this->fail('更新任务不存在。');
        }

        return json_decode(File::get($path), true, 64, JSON_THROW_ON_ERROR);
    }

    public function statuses(): array
    {
        return collect(File::glob(storage_path('app/private/extensions/updates/*/state.json')))
            ->map(fn ($path) => ['id' => basename(dirname($path)), ...json_decode(File::get($path), true, 64, JSON_THROW_ON_ERROR)])
            ->sortByDesc('created_at')->values()->all();
    }

    private function writeState(string $id, array $state): void
    {
        File::replace($this->directory($id).'/state.json', json_encode([...$state, 'updated_at' => now()->toIso8601String()], JSON_THROW_ON_ERROR));
    }

    private function applyLocked(string $type, string $slug, string $id): void
    {
        $package = $this->package($type, $slug);
        if ($type === 'plugin' ? $package->enabled : $package->is_active) {
            $this->fail('请先停用此扩展，主题需先切换到其他主题。');
        }
        $work = $this->directory($id);
        if (! is_file($work.'/state.json')) {
            $this->fail('更新任务不存在。');
        }
        $state = json_decode(File::get($work.'/state.json'), true, 64, JSON_THROW_ON_ERROR);
        if ($state['type'] !== $type || $state['slug'] !== $slug || ! in_array($state['status'], ['ready', 'queued', 'downloading'], true)) {
            $this->fail('更新任务不匹配或已经执行。');
        }
        $manifest = $this->verifier->manifest(File::get($work.'/release.json'), File::get($work.'/release.sig'), $this->source($type, $slug)['public_key']);
        $this->identity($manifest, $type, $slug, $package->version);
        try {
            $state['status'] = 'downloading';
            $this->writeState($id, $state);
            if (is_dir($work.'/verified')) {
                File::deleteDirectory($work.'/verified');
            }
            $this->client->download($state['package_url'], $work.'/package.zip', 20 * 1024 * 1024);
            $this->verifier->extract($work.'/package.zip', $manifest, $work.'/verified');
            $packageManifest = json_decode(File::get($work.'/verified/'.$slug.'/'.$type.'.json'), true, 64, JSON_THROW_ON_ERROR);
            if (($packageManifest['slug'] ?? '') !== $slug || ($packageManifest['version'] ?? '') !== $manifest['version']) {
                $this->fail('包清单与发布签名不一致。');
            }
            $files = ['type' => $type, 'slug' => $slug, 'backup' => storage_path('app/private/extensions/versions/pre-update-'.$slug.'-'.Str::uuid())];
            File::ensureDirectoryExists(dirname($files['backup']));
            if (! File::copyDirectory(base_path($type.'s/'.$slug), $files['backup'])) {
                $this->fail('Cannot back up extension files.');
            }
            app(ExtensionMaintenance::class)->run(function () use ($type, $work, $id, &$state) {
                $state['status'] = 'installing';
                $this->writeState($id, $state);
                $upload = new UploadedFile($work.'/package.zip', 'package.zip', 'application/zip', null, true);
                app($type === 'plugin' ? PluginInstaller::class : ThemeInstaller::class)->install($upload);
                $state['status'] = 'installed';
                $this->writeState($id, $state);
            }, fn () => app(ExtensionMaintenance::class)->restorePackageFiles($files), ['package_files' => $files, 'update_id' => $id]);
            $state['status'] = 'installed';
        } catch (\Throwable $exception) {
            $state['status'] = 'failed';
            $state['error'] = $exception::class;
            throw $exception;
        } finally {
            $this->writeState($id, $state);
        }
    }

    private function identity(array $manifest, string $type, string $slug, string $current): void
    {
        if ($manifest['type'] !== $type || ($manifest['slug'] ?? '') !== $slug || version_compare($manifest['version'], $current, '<=')) {
            $this->fail('扩展发布身份或版本不匹配。');
        }
        foreach (array_keys($manifest['files']) as $path) {
            if (! str_starts_with($path, $slug.'/')) {
                $this->fail('发布包文件超出扩展目录。');
            }
        }
        if (! isset($manifest['files'][$slug.'/'.$type.'.json'])) {
            $this->fail('发布包缺少扩展清单。');
        }
    }

    private function directory(string $id): string
    {
        if (! Str::isUuid($id)) {
            $this->fail('更新任务标识无效。');
        }

        return storage_path('app/private/extensions/updates/'.$id);
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['update' => $message]);
    }
}
