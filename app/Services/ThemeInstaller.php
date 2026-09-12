<?php

namespace App\Services;

use App\Models\Theme;
use App\Services\Updates\ReleaseVerifier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PharData;
use RecursiveIteratorIterator;

class ThemeInstaller
{
    public function install(UploadedFile $upload): Theme
    {
        return Cache::store('file')->lock('zfy-theme-lifecycle', 300)->block(5, fn () => $this->installLocked($upload));
    }

    private function installLocked(UploadedFile $upload): Theme
    {
        $work = storage_path('app/private/packages/'.Str::uuid());
        File::ensureDirectoryExists($work);
        File::copy($upload->getRealPath(), $work.'/theme.zip');
        $target = null;
        $previousDirectory = null;
        $moved = false;
        try {
            $archive = new PharData($work.'/theme.zip');
            $entries = [];
            $seen = [];
            $size = 0;
            $archivePrefix = 'phar://'.str_replace('\\', '/', $work).'/theme.zip/';
            foreach (new RecursiveIteratorIterator($archive) as $file) {
                $path = str_replace('\\', '/', $file->getPathname());
                $relative = str_starts_with($path, $archivePrefix) ? substr($path, strlen($archivePrefix)) : '';
                if ($relative === '' || $file->isLink() || preg_match('~(^/|(^|/)\.\.(/|$)|:|[\x00-\x1f])~', $relative)) {
                    $this->fail('Invalid archive path.');
                }
                app(ReleaseVerifier::class)->path($relative);
                if (isset($seen[strtolower($relative)])) {
                    $this->fail('Duplicate archive path.');
                }
                $seen[strtolower($relative)] = true;
                $size += $file->getSize();
                $entries[$relative] = $path;
                if (count($entries) > 5000 || $size > 100 * 1024 * 1024) {
                    $this->fail('Theme archive is too large.');
                }
            }
            $manifests = array_values(array_filter(array_keys($entries), fn ($path) => preg_match('~^(?:[a-z0-9-]+/)?theme\.json$~', $path)));
            if (count($manifests) !== 1) {
                $this->fail('Exactly one theme.json is required.');
            }
            $manifestPath = $manifests[0];
            $manifest = json_decode(file_get_contents($entries[$manifestPath]), true, 32, JSON_THROW_ON_ERROR);
            $validation = array_replace(['permissions' => [], 'events' => []], $manifest);
            unset($validation['schema_version']);
            $errors = app(PackageManifestService::class)->validatePluginPayload($validation);
            if (isset($manifest['schema_version'])) {
                $errors = [...$errors, ...app(PackageManifestService::class)->validateSchema($manifest, 'theme')];
            }
            if ($errors) {
                $this->fail(implode('; ', $errors));
            }
            $slug = $manifest['slug'];
            $prefix = dirname($manifestPath) === '.' ? '' : dirname($manifestPath).'/';
            if ($prefix !== '' && $prefix !== $slug.'/') {
                $this->fail('Theme directory must match its slug.');
            }
            $existing = Theme::where('slug', $slug)->first();
            $upgrading = $existing && is_dir(base_path('themes/'.$slug));
            if ($upgrading) {
                $activeSlug = Theme::where('is_active', true)->value('slug');
                if ($existing->is_active || ($activeSlug && in_array($slug, app(ThemePackageLoader::class)->chain($activeSlug), true))) {
                    $this->fail('Activate another theme before upgrading this theme.');
                }
                if (version_compare($manifest['version'], $existing->version, '<=')) {
                    $this->fail('Theme upgrade requires a newer version.');
                }
                app(PackageIntegrity::class)->assertUnmodified('theme', $slug);
            } elseif (File::exists(base_path('themes/'.$slug))) {
                $this->fail('Theme directory already exists.');
            }
            if (! empty($manifest['parent']) && ! Theme::where('slug', $manifest['parent'])->exists()) {
                $this->fail('Install parent theme first.');
            }
            $entry = app(ThemePackageLoader::class)->entry($manifest);
            if (! isset($entries[$prefix.($manifest['entry'] ?? 'views/layout.blade.php')]) && empty($manifest['parent'])) {
                $this->fail('Theme entry is missing.');
            }
            foreach ($entries as $relative => $source) {
                if (! str_starts_with($relative, $prefix)) {
                    $this->fail('File is outside the theme directory.');
                }
                $relative = substr($relative, strlen($prefix));
                $destination = $work.'/files/'.$relative;
                File::ensureDirectoryExists(dirname($destination));
                File::put($destination, file_get_contents($source));
            }
            $target = base_path('themes/'.$slug);
            if ($upgrading) {
                $previousDirectory = storage_path('app/private/extensions/versions/'.$slug.'-'.$existing->version.'-'.Str::uuid());
                File::ensureDirectoryExists(dirname($previousDirectory));
                if (! File::moveDirectory($target, $previousDirectory)) {
                    $this->fail('Cannot archive previous theme.');
                }
            }
            if (! File::moveDirectory($work.'/files', $target)) {
                $this->fail('Cannot write theme directory.');
            }
            $moved = true;
            $theme = DB::transaction(function () use ($slug, $manifest, $entry, $existing, $previousDirectory) {
                $previousVersion = $existing?->version;
                $theme = Theme::updateOrCreate(['slug' => $slug], [
                    'name' => $manifest['name'], 'slug' => $slug, 'version' => $manifest['version'],
                    'author' => $manifest['author'] ?? '', 'compatible' => $manifest['compatible'], 'entry_view' => $entry,
                    'preview' => '/extensions/assets/theme/'.$slug.'/'.($manifest['preview'] ?? 'preview.png'),
                    'menus' => $manifest['menus'] ?? [], 'regions' => $manifest['regions'] ?? [],
                    'settings_schema' => $manifest['settings_schema'] ?? [], 'is_active' => false,
                ]);
                app(PackageIntegrity::class)->record('theme', $slug);
                $theme->settings()->where('key', '_lifecycle.uninstalled')->delete();
                if ($previousDirectory) {
                    $theme->settings()->updateOrCreate(['scope' => 'global', 'key' => '_lifecycle.previous_version'], ['value' => ['raw' => $previousVersion]]);
                    $theme->settings()->updateOrCreate(['scope' => 'global', 'key' => '_lifecycle.previous_directory'], ['value' => ['raw' => $previousDirectory]]);
                }

                return $theme;
            });
            app(ThemePackageLoader::class)->register();
            app(ThemeManager::class)->forgetActiveCache();

            return $theme;
        } catch (\Throwable $exception) {
            if ($moved && $target && is_dir($target)) {
                File::deleteDirectory($target);
            }
            if ($previousDirectory && is_dir($previousDirectory)) {
                File::moveDirectory($previousDirectory, $target);
            }
            if ($target && is_dir($target)) {
                app(PackageIntegrity::class)->record('theme', basename($target));
            }
            if ($exception instanceof ValidationException) {
                throw $exception;
            }
            report($exception);
            $this->fail('Unable to install theme archive.');
        } finally {
            unset($archive);
            File::deleteDirectory($work);
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
