<?php

namespace App\Domain\Theme;

use Illuminate\Support\Arr;

/**
 * 主题元数据值对象，读取自 themes/{slug}/theme.json。
 */
class Theme
{
    public function __construct(
        public string $slug,
        public string $name,
        public string $version,
        public string $author,
        public string $description,
        public string $compatible,
        public string $entry,
        public ?string $preview,
        public array $menus,
        public array $regions,
        public array $settingsSchema,
        public string $path,
    ) {}

    public static function loadFromPath(string $path): ?self
    {
        $json = $path . DIRECTORY_SEPARATOR . 'theme.json';
        if (! is_file($json)) {
            return null;
        }

        $data = json_decode(file_get_contents($json), true);
        if (! is_array($data)) {
            return null;
        }

        $slug = $data['slug'] ?? basename($path);

        return new self(
            slug: $slug,
            name: Arr::get($data, 'name', $slug),
            version: Arr::get($data, 'version', '1.0.0'),
            author: Arr::get($data, 'author', 'unknown'),
            description: Arr::get($data, 'description', ''),
            compatible: Arr::get($data, 'compatible', '^3.0'),
            entry: Arr::get($data, 'entry', 'views/layouts/app.blade.php'),
            preview: Arr::get($data, 'preview'),
            menus: Arr::get($data, 'menus', ['primary', 'footer']),
            regions: Arr::get($data, 'regions', ['home', 'sidebar', 'footer']),
            settingsSchema: Arr::get($data, 'settings_schema', []),
            path: $path,
        );
    }

    public function viewsPath(): string
    {
        return $this->path . DIRECTORY_SEPARATOR . 'views';
    }

    public function assetsPath(): string
    {
        return $this->path . DIRECTORY_SEPARATOR . 'assets';
    }

    public function previewUrl(): ?string
    {
        if (! $this->preview) {
            return null;
        }
        return '/themes/' . $this->slug . '/' . ltrim($this->preview, '/');
    }
}
