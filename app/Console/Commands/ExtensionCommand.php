<?php

namespace App\Console\Commands;

use App\Models\Plugin;
use App\Services\ExtensionMaintenance;
use App\Services\PackageManifestService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PharData;

class ExtensionCommand extends Command
{
    protected $signature = 'zfy:extension {action : make-plugin, make-theme, validate, pack, disable, safe-mode, recover} {slug?} {--type=plugin} {--output=} {--directory=} {--off}';

    protected $description = 'Create, validate, package and recover native extensions';

    public function handle(PackageManifestService $packages): int
    {
        $action = $this->argument('action');
        $slug = $this->argument('slug');
        if ($action === 'recover') {
            app(ExtensionMaintenance::class)->recover();
            $this->info('Extension database and files recovered; affected plugin is disabled.');

            return self::SUCCESS;
        }
        if ($action === 'safe-mode') {
            $path = storage_path('app/private/extensions/safe-mode');
            File::ensureDirectoryExists(dirname($path));
            $this->option('off') ? File::delete($path) : File::put($path, now()->toIso8601String());
            $this->info('Safe mode '.($this->option('off') ? 'disabled' : 'enabled'));

            return self::SUCCESS;
        }
        if (! is_string($slug) || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) || strlen($slug) > 80) {
            $this->error('A valid package slug is required.');

            return self::FAILURE;
        }
        if ($action === 'disable') {
            Plugin::where('slug', $slug)->update(['enabled' => false]);
            $this->info('Plugin disabled without loading its code.');

            return self::SUCCESS;
        }
        $type = $action === 'make-theme' ? 'theme' : $this->option('type');
        if (! in_array($type, ['theme', 'plugin'], true)) {
            return self::FAILURE;
        }
        $root = $this->option('directory') ?: base_path($type.'s/'.$slug);
        if (str_starts_with($action, 'make-')) {
            if (File::exists($root)) {
                $this->error('Package already exists.');

                return self::FAILURE;
            }
            File::ensureDirectoryExists($root);
            $manifest = ['schema_version' => 1, 'name' => Str::headline($slug), 'slug' => $slug, 'version' => '1.0.0', 'compatible' => '^'.config('zfy.version'), 'author' => '', 'permissions' => [], 'events' => [], 'requires' => ['php' => '^8.2']];
            if ($type === 'plugin') {
                $namespace = 'Plugins\\'.Str::studly($slug);
                $manifest['provider'] = $namespace.'\\Provider';
                $manifest['entry'] = 'provider.php';
                $source = "<?php\n\nnamespace ".$namespace.";\n\nuse Illuminate\\Support\\ServiceProvider;\n\nclass Provider extends ServiceProvider\n{\n    public function boot(): void\n    {\n        zfy_on('zfy_content_published', function (\\App\\Models\\Content \$content): void {\n            logger()->info('Content published', ['id' => \$content->id]);\n        }, 10, 1);\n    }\n}\n";
                File::put($root.'/provider.php', $source);
            } else {
                File::ensureDirectoryExists($root.'/views');
                File::ensureDirectoryExists($root.'/assets');
                $manifest['entry'] = 'views/layout.blade.php';
                $manifest['menus'] = ['primary', 'footer'];
                $manifest['regions'] = ['sidebar', 'footer'];
                File::put($root.'/views/layout.blade.php', "<!doctype html>\n<html lang=\"zh-CN\"><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\"><title>{{ config('app.name') }}</title></head><body><header><a href=\"/\">{{ config('app.name') }}</a></header><main>@include('themes.shared.partials.native-content')</main></body></html>\n");
            }
            File::put($root.'/'.$type.'.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
            $this->info($root);

            return self::SUCCESS;
        }
        $manifest = is_file($root.'/'.$type.'.json') ? json_decode(File::get($root.'/'.$type.'.json'), true, 64, JSON_THROW_ON_ERROR) : [];
        if (($manifest['slug'] ?? '') !== $slug) {
            $this->error('Manifest slug does not match.');

            return self::FAILURE;
        }
        $validation = array_replace(['permissions' => [], 'events' => []], $manifest);
        if ($type === 'theme') {
            unset($validation['schema_version']);
        }
        $errors = $packages->validatePluginPayload($validation);
        if ($type === 'theme' && isset($manifest['schema_version'])) {
            $errors = [...$errors, ...$packages->validateSchema($manifest, 'theme')];
        }
        if ($errors) {
            foreach ($errors as $error) {
                $this->error($error);
            }

return self::FAILURE;
        }
        if ($action === 'validate') {
            $this->info('Manifest is valid.');

            return self::SUCCESS;
        }
        if ($action !== 'pack') {
            return self::FAILURE;
        }
        $output = $this->option('output') ?: storage_path('app/private/packages/'.$slug.'-'.$manifest['version'].'.zip');
        if (File::exists($output)) {
            $this->error('Output already exists.');

            return self::FAILURE;
        }
        File::ensureDirectoryExists(dirname($output));
        $archive = new PharData($output);
        foreach (File::allFiles($root) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            if ($file->isLink() || preg_match('~(^|/)(\.env[^/]*|\.git|node_modules)(/|$)~', $relative)) {
                continue;
            }
            $archive->addFile($file->getPathname(), $slug.'/'.$relative);
        }
        $this->info($output);

        return self::SUCCESS;
    }
}
