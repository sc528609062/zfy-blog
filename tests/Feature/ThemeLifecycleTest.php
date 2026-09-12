<?php

namespace Tests\Feature;

use App\Models\Theme;
use App\Services\ThemeInstaller;
use App\Services\ThemeLifecycleManager;
use App\Services\ThemeManager;
use App\Services\ThemePackageLoader;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use PharData;
use Tests\TestCase;

class ThemeLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_theme_lifecycle_runs_with_child_and_cannot_upgrade_while_in_use(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $prefix = 'family-'.bin2hex(random_bytes(5));
        $work = storage_path('framework/testing/'.$prefix);
        File::ensureDirectoryExists($work);
        $package = function (string $slug, string $version, ?string $parent = null) use ($work) {
            $class = 'FamilyProvider'.str_replace('-', '', $slug);
            $path = $work.'/'.$slug.'-'.$version.'.zip';
            $zip = new PharData($path);
            $manifest = ['schema_version' => 1, 'name' => $slug, 'slug' => $slug, 'version' => $version, 'compatible' => '^1', 'entry' => 'views/layout.blade.php', 'provider' => $class, 'bootstrap' => 'provider.php'];
            if ($parent) {
                $manifest += ['parent' => $parent, 'requires' => ['themes' => [$parent => '^1']]];
            }
            $zip->addFromString($slug.'/theme.json', json_encode($manifest));
            $zip->addFromString($slug.'/views/layout.blade.php', '<h1>Family</h1>');
            $zip->addFromString($slug.'/provider.php', '<?php class '.$class.' extends \\Illuminate\\Support\\ServiceProvider implements \\App\\Support\\Zfy\\ThemeLifecycle { public function activate(): void { config(["family.events" => [...config("family.events", []), "'.$slug.':on"]]); } public function deactivate(): void { config(["family.events" => [...config("family.events", []), "'.$slug.':off"]]); } public function upgrade(string $fromVersion): void {} public function uninstall(bool $deleteData): void {} }');
            unset($zip);

            return new UploadedFile($path, 'theme.zip', 'application/zip', null, true);
        };
        try {
            $parent = app(ThemeInstaller::class)->install($package($prefix, '1.0.0'));
            $child = app(ThemeInstaller::class)->install($package($prefix.'-child', '1.0.0', $prefix));
            app(ThemeManager::class)->activate($child->slug);
            $this->assertSame([$prefix.':on', $prefix.'-child:on'], config('family.events'));
            try {
                app(ThemeInstaller::class)->install($package($prefix, '1.1.0'));
                $this->fail('Active parent was overwritten.');
            } catch (ValidationException) {
                $this->assertSame('1.0.0', $parent->fresh()->version);
            }
            app(ThemeManager::class)->activate(config('zfy.default_theme'));
            $this->assertSame([$prefix.':on', $prefix.'-child:on', $prefix.'-child:off', $prefix.':off'], config('family.events'));
        } finally {
            File::deleteDirectory(base_path('themes/'.$prefix.'-child'));
            File::deleteDirectory(base_path('themes/'.$prefix));
            File::deleteDirectory($work);
        }
    }

    public function test_theme_owns_provider_and_keeps_data_through_upgrade_and_uninstall(): void
    {
        config(['extensions.safe_mode' => false]);
        $this->seed(CoreInstallSeeder::class);
        $slug = 'provider-theme-'.bin2hex(random_bytes(6));
        $class = 'ThemeProvider'.bin2hex(random_bytes(6));
        $work = storage_path('framework/testing/'.$slug);
        File::ensureDirectoryExists($work);
        $source = '<?php class '.$class.' extends \\Illuminate\\Support\\ServiceProvider implements \\App\\Support\\Zfy\\ThemeLifecycle {
            public function boot(): void { zfy_filter("test_theme_value", fn ($value) => $value." booted"); }
            public function activate(): void { config(["theme_test.active" => true]); }
            public function deactivate(): void { config(["theme_test.active" => false]); }
            public function upgrade(string $fromVersion): void { config(["theme_test.previous" => $fromVersion]); }
            public function uninstall(bool $deleteData): void { config(["theme_test.purged" => $deleteData]); }
        }';
        $package = function ($version) use ($slug, $class, $work, $source) {
            $path = $work.'/'.$version.'.zip';
            $zip = new PharData($path);
            $zip->addFromString($slug.'/theme.json', json_encode(['schema_version' => 1, 'name' => 'Provider theme', 'slug' => $slug, 'version' => $version, 'compatible' => '^1', 'entry' => 'views/layout.blade.php', 'provider' => $class, 'bootstrap' => 'provider.php']));
            $zip->addFromString($slug.'/views/layout.blade.php', '<h1>Theme test</h1>');
            $zip->addFromString($slug.'/provider.php', $source);
            unset($zip);

            return new UploadedFile($path, 'theme.zip', 'application/zip', null, true);
        };
        try {
            $theme = app(ThemeInstaller::class)->install($package('1.0.0'));
            $theme->settings()->create(['scope' => 'global', 'key' => 'custom', 'value' => ['raw' => 'retained']]);
            app(ThemeManager::class)->activate($slug);
            $this->assertTrue(config('theme_test.active'));
            app(ThemePackageLoader::class)->bootActive();
            $this->assertSame('value booted', zfy_apply('test_theme_value', 'value'));
            app(ThemeManager::class)->activate(config('zfy.default_theme'));
            $this->assertFalse(config('theme_test.active'));
            $theme = app(ThemeInstaller::class)->install($package('1.1.0'));
            app(ThemeManager::class)->activate($slug);
            $this->assertSame('1.0.0', config('theme_test.previous'));
            $this->assertSame('retained', $theme->settings()->where('key', 'custom')->first()->value['raw']);
            app(ThemeManager::class)->activate(config('zfy.default_theme'));
            app(ThemeLifecycleManager::class)->uninstall($theme);
            $this->assertFalse(config('theme_test.purged'));
            $this->assertNotNull(Theme::find($theme->id));
            app(ThemeLifecycleManager::class)->purge($theme);
            $this->assertTrue(config('theme_test.purged'));
            $this->assertNull(Theme::find($theme->id));
        } finally {
            File::deleteDirectory(base_path('themes/'.$slug));
            File::deleteDirectory($work);
            foreach (['versions', 'uninstalled'] as $directory) {
                foreach (File::glob(storage_path('app/private/extensions/'.$directory.'/*'.$slug.'*')) as $path) {
                    File::deleteDirectory($path);
                }
            }
        }
    }
}
