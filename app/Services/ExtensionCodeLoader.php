<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;

class ExtensionCodeLoader
{
    public function provider(string $type, string $slug, ?string $archive = null): ?ServiceProvider
    {
        if (! in_array($type, ['plugin', 'theme'], true) || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $this->fail('Invalid extension identity.');
        }
        $root = realpath($archive ?? base_path($type.'s/'.$slug));
        $parent = realpath($archive ? storage_path('app/private/extensions/uninstalled') : base_path($type.'s'));
        if (! $root || ! $parent || dirname($root) !== $parent) {
            $this->fail('Invalid extension directory.');
        }
        $manifest = json_decode(File::get($root.'/'.$type.'.json'), true, 64, JSON_THROW_ON_ERROR);
        if (($manifest['slug'] ?? '') !== $slug) {
            $this->fail('Extension identity mismatch.');
        }
        $class = $manifest['provider'] ?? null;
        if (! $class) {
            return null;
        }
        $entry = $manifest[$type === 'theme' ? 'bootstrap' : 'entry'] ?? 'provider.php';
        $file = $this->inside($root, $entry);
        if (! is_file($file)) {
            $this->fail('Extension PHP entry is missing.');
        }
        foreach ($manifest['autoload']['psr-4'] ?? [] as $namespace => $relative) {
            $directory = $this->inside($root, $relative);
            if (! is_dir($directory) || ! is_string($namespace) || ! str_ends_with($namespace, '\\')) {
                $this->fail('Invalid extension autoload map.');
            }
            spl_autoload_register(static function ($name) use ($namespace, $directory) {
                if (str_starts_with($name, $namespace)) {
                    $path = $directory.'/'.str_replace('\\', '/', substr($name, strlen($namespace))).'.php';
                    if (is_file($path)) {
                        require_once $path;
                    }
                }
            });
        }
        if (! class_exists($class, false)) {
            require_once $file;
        }
        if (! is_subclass_of($class, ServiceProvider::class)) {
            $this->fail('Extension provider must extend Laravel ServiceProvider.');
        }

        return new $class(app());
    }

    private function inside(string $root, string $relative): string
    {
        $path = realpath($root.'/'.$relative);
        if (! $path || ! str_starts_with(str_replace('\\', '/', $path).'/', str_replace('\\', '/', $root).'/')) {
            $this->fail('Extension path escapes package.');
        }

        return $path;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['package' => $message]);
    }
}
