<?php

namespace App\Domain\Theme;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

/**
 * 主题管理器。
 *
 * - 扫描 themes/{slug}/theme.json，构造主题元数据集合。
 * - 维护当前激活主题（存于 settings 表 `active_theme`）。
 * - 启动时把当前主题视图路径注入 view finder，让前台 view('home')
 *   优先解析到 themes/{active}/views/home.blade.php。
 *
 * 后续 Sprint 6 (M11) 会扩展：主题包上传、安全校验、版本兼容、settings_schema 渲染。
 */
class ThemeManager
{
    protected ?Theme $active = null;
    protected bool $booted = false;

    public function basePath(): string
    {
        return rtrim(config('zfy.theme.path'), DIRECTORY_SEPARATOR);
    }

    public function all(): Collection
    {
        return Cache::remember('zfy.themes.all', 60, function () {
            $base = $this->basePath();

            if (! is_dir($base)) {
                return collect();
            }

            return collect(File::directories($base))
                ->map(fn ($dir) => Theme::loadFromPath($dir))
                ->filter()
                ->values()
                ->keyBy(fn (Theme $t) => $t->slug);
        });
    }

    public function find(string $slug): ?Theme
    {
        return $this->all()->get($slug);
    }

    public function activeSlug(): string
    {
        return Cache::rememberForever(config('zfy.theme.cache_key'), function () {
            $slug = null;

            // 优先从 settings 表读
            try {
                if (\Schema::hasTable('settings')) {
                    $row = \DB::table('settings')->where('key', 'active_theme')->value('value');
                    if ($row) {
                        $slug = $row;
                    }
                }
            } catch (\Throwable $e) {
                // 安装前 settings 表不存在
            }

            return $slug ?: config('zfy.theme.default');
        });
    }

    public function active(): Theme
    {
        if ($this->active) {
            return $this->active;
        }

        $slug = $this->activeSlug();
        $theme = $this->find($slug);

        if (! $theme) {
            $theme = $this->find(config('zfy.theme.fallback'));
        }

        if (! $theme) {
            throw new \RuntimeException("No theme found in {$this->basePath()}. At least default-blue must exist.");
        }

        return $this->active = $theme;
    }

    /**
     * 切换激活主题。会更新 settings 并清缓存。
     */
    public function activate(string $slug): Theme
    {
        $theme = $this->find($slug);
        if (! $theme) {
            throw new \InvalidArgumentException("Theme [{$slug}] not found.");
        }

        \DB::table('settings')->updateOrInsert(
            ['key' => 'active_theme'],
            ['value' => $slug, 'type' => 'string', 'updated_at' => now(), 'created_at' => now()]
        );

        $this->flushCache();
        $this->active = $theme;

        return $theme;
    }

    /**
     * 把当前主题视图路径加入 view finder 的前置位置。
     * 这样 view('home') 会优先解析 themes/{active}/views/home.blade.php，
     * 找不到时回退到 resources/views。
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $theme = $this->active();
        $viewsPath = $theme->viewsPath();

        if (is_dir($viewsPath)) {
            View::getFinder()->prependLocation($viewsPath);
            View::addNamespace('theme', $viewsPath);
        }

        View::share('zfyTheme', $theme);

        $this->booted = true;
    }

    public function flushCache(): void
    {
        Cache::forget('zfy.themes.all');
        Cache::forget(config('zfy.theme.cache_key'));
        $this->active = null;
        $this->booted = false;
    }
}
