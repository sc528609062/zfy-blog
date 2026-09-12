<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Theme;
use App\Models\ThemeSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ThemeManager
{
    public function active(): array
    {
        if (! app()->runningInConsole() && request()->filled('theme_preview') && auth()->user()?->can('manage themes')) {
            $preview = Cache::store('file')->get('zfy-theme-preview-'.request()->string('theme_preview'));
            if ($preview && $preview['user_id'] === auth()->id() && ($theme = Theme::find($preview['theme_id']))) {
                if ($this->blockedInSafeMode($theme->slug)) {
                    return $this->fallbackTheme(config('zfy.default_theme'));
                }
                $settings = $this->applyGlobalOptions(array_replace_recursive($this->settingsFor($theme), ['global' => $preview['values']]));
                $accent = $this->primaryColor($settings, '#1684ff');
                $onAccent = $this->contrastingTextColor($accent);

                return ['model' => $theme, 'slug' => $theme->slug, 'name' => $theme->name, 'view' => $theme->entry_view, 'settings' => $settings, 'accent' => $accent, 'on_accent' => $onAccent, 'accent_hover' => $this->hoverAccent($accent, $onAccent), 'accent_text' => $this->foregroundAccent($accent)];
            }
        }
        $defaultSlug = config('zfy.default_theme');

        if (! Schema::hasTable('settings') || ! Schema::hasTable('themes')) {
            return $this->fallbackTheme($defaultSlug);
        }

        $active = Cache::remember('zfy.active_theme', 60, function () {
            $slug = data_get(Setting::where('key', 'site.active_theme')->first()?->value, 'slug', config('zfy.default_theme'));
            $theme = Theme::where('slug', $slug)->first();

            if (! $theme) {
                return $this->fallbackTheme($slug);
            }

            $settings = $this->settingsFor($theme);
            $fallbackAccent = data_get(config("zfy.themes.{$theme->slug}"), 'accent', '#1684ff');
            $accent = $this->primaryColor($settings, $fallbackAccent);
            $onAccent = $this->contrastingTextColor($accent);

            return [
                'model' => $theme,
                'slug' => $theme->slug,
                'name' => $theme->name,
                'view' => $theme->entry_view,
                'accent' => $accent,
                'on_accent' => $onAccent,
                'accent_hover' => $this->hoverAccent($accent, $onAccent),
                'accent_text' => $this->foregroundAccent($accent),
                'settings' => $settings,
            ];
        });

        return $this->blockedInSafeMode($active['slug']) ? $this->fallbackTheme(config('zfy.default_theme')) : $active;
    }

    private function blockedInSafeMode(string $slug): bool
    {
        return (config('extensions.safe_mode') || is_file(storage_path('app/private/extensions/safe-mode'))) && ! array_key_exists($slug, config('zfy.themes', []));
    }

    public function activate(string $slug): void
    {
        $theme = Theme::where('slug', $slug)->firstOrFail();
        abort_unless(is_file(base_path('themes/'.$slug.'/theme.json')), 422, '主题安装文件不存在。');
        $packages = app(PackageManifestService::class);
        $manifest = $packages->themes()[$slug];
        $validation = array_replace(['permissions' => [], 'events' => []], $manifest);
        unset($validation['schema_version']);
        $errors = $packages->validatePluginPayload($validation);
        if (isset($manifest['schema_version'])) {
            $errors = [...$errors, ...$packages->validateSchema($manifest, 'theme')];
        }
        if ($errors) {
            throw ValidationException::withMessages(['theme' => implode('; ', $errors)]);
        }
        $packages->assertDependencies($manifest);
        abort_unless(view()->exists($theme->entry_view), 422, '主题模板不存在。');
        app(ThemeLifecycleManager::class)->activate($theme, function () use ($slug) {
            Theme::orderBy('id')->lockForUpdate()->get();
            Theme::query()->update(['is_active' => false]);
            Theme::where('slug', $slug)->update(['is_active' => true]);

            Setting::updateOrCreate(
                ['key' => 'site.active_theme'],
                ['value' => ['slug' => $slug], 'autoload' => true]
            );
        });

        $this->forgetActiveCache();
    }

    public function forgetActiveCache(): void
    {
        Cache::forget('zfy.active_theme');
    }

    public function settingsFor(Theme $theme): array
    {
        $settings = ThemeSetting::where('theme_id', $theme->id)->get()
            ->groupBy('scope')
            ->map(fn ($items) => $items->mapWithKeys(fn (ThemeSetting $setting) => [
                $setting->key => $this->settingValue($setting->value),
            ])->all())
            ->all();

        return $this->applyGlobalOptions(array_replace_recursive($this->defaultSettings($theme->slug), $settings));
    }

    private function applyGlobalOptions(array $settings): array
    {
        foreach (['hero_enabled', 'channels_enabled', 'sidebar_enabled'] as $key) {
            if (array_key_exists($key, $settings['global'] ?? [])) {
                $settings['home'][$key] = $settings['global'][$key];
            }
        }
        foreach (['show_author_card', 'show_related'] as $key) {
            if (array_key_exists($key, $settings['global'] ?? [])) {
                $settings['content-detail'][$key] = $settings['global'][$key];
            }
        }

        return $settings;
    }

    public function defaultSettings(string $slug): array
    {
        $base = [
            'global' => [
                'logo_text' => 'zfy-blog',
                'primary_color' => data_get(config("zfy.themes.{$slug}"), 'accent', '#1684ff'),
                'nav' => ['首页', '资源', '教程', '社区', '活动', '排行榜'],
                'footer_text' => 'zfy-blog 内容商业平台',
            ],
            'home' => [
                'hero_enabled' => true,
                'channels_enabled' => true,
                'feed_enabled' => true,
                'sidebar_enabled' => true,
            ],
            'content-detail' => [
                'show_author_card' => true,
                'show_related' => true,
                'show_paywall' => true,
                'markdown_theme' => 'juejin',
                'code_theme' => 'atom-one-dark',
            ],
            'files-channel' => [
                'card_style' => 'commerce',
                'show_vip_price' => true,
            ],
        ];

        $defaults = match ($slug) {
            'style-b-marketplace' => array_replace_recursive($base, [
                'global' => ['nav' => ['首页', '资源商城', '教程中心', '文章资讯', '会员中心', '帮助中心']],
                'home' => ['coupon_panel' => true, 'market_stats' => true],
            ]),
            'style-c-creative' => array_replace_recursive($base, [
                'global' => ['nav' => ['首页', '资源', '教程', '工具', '社区', '活动']],
                'home' => ['creator_panel' => true, 'playful_sidebar' => true],
            ]),
            default => $base,
        };
        $manifest = app(PackageManifestService::class)->themes()[$slug] ?? [];

        return array_replace_recursive($defaults, is_array($manifest['defaults'] ?? null) ? $manifest['defaults'] : []);
    }

    private function fallbackTheme(string $slug): array
    {
        $accent = data_get(config("zfy.themes.{$slug}"), 'accent', '#1684ff');
        $onAccent = $this->contrastingTextColor($accent);

        return [
            'model' => null,
            'slug' => $slug,
            'name' => data_get(config("zfy.themes.{$slug}"), 'name', 'Default'),
            'view' => "themes.{$slug}.layout",
            'accent' => $accent,
            'on_accent' => $onAccent,
            'accent_hover' => $this->hoverAccent($accent, $onAccent),
            'accent_text' => $this->foregroundAccent($accent),
            'settings' => $this->defaultSettings($slug),
        ];
    }

    private function settingValue(mixed $value): mixed
    {
        return is_array($value) && array_key_exists('raw', $value) ? $value['raw'] : $value;
    }

    private function primaryColor(array $settings, string $fallback): string
    {
        $color = data_get($settings, 'global.primary_color');

        if (! is_string($color)) {
            return $fallback;
        }

        $color = trim($color);

        return preg_match('/^#(?:[\da-f]{3}|[\da-f]{4}|[\da-f]{6}|[\da-f]{8})$/i', $color) ? $color : $fallback;
    }

    private function contrastingTextColor(string $color): string
    {
        $hex = $this->normalizedHex($color);

        $backgroundLuminance = $this->relativeLuminance($hex);
        $lightContrast = 1.05 / ($backgroundLuminance + 0.05);
        $darkContrast = ($backgroundLuminance + 0.05) / ($this->relativeLuminance('101828') + 0.05);

        return $lightContrast >= $darkContrast ? '#ffffff' : '#101828';
    }

    private function relativeLuminance(string $hex): float
    {
        $channels = [
            hexdec(substr($hex, 0, 2)) / 255,
            hexdec(substr($hex, 2, 2)) / 255,
            hexdec(substr($hex, 4, 2)) / 255,
        ];

        [$red, $green, $blue] = array_map(
            fn (float $channel): float => $channel <= 0.04045
                ? $channel / 12.92
                : (($channel + 0.055) / 1.055) ** 2.4,
            $channels,
        );

        return 0.2126 * $red + 0.7152 * $green + 0.0722 * $blue;
    }

    private function hoverAccent(string $color, string $onAccent): string
    {
        $hex = $this->normalizedHex($color);
        $target = $onAccent === '#ffffff' ? '101828' : 'ffffff';
        $targetWeight = $onAccent === '#ffffff' ? 0.14 : 0.10;

        return $this->mixHexColors($hex, $target, $targetWeight);
    }

    private function foregroundAccent(string $color): string
    {
        $hex = $this->normalizedHex($color);

        if ($this->contrastRatio($hex, 'ffffff') >= 4.5) {
            return '#'.$hex;
        }

        for ($weight = 0.05; $weight <= 0.95; $weight += 0.05) {
            $candidate = $this->mixHexColors($hex, '101828', $weight);

            if ($this->contrastRatio(ltrim($candidate, '#'), 'ffffff') >= 4.5) {
                return $candidate;
            }
        }

        return '#101828';
    }

    private function contrastRatio(string $first, string $second): float
    {
        $firstLuminance = $this->relativeLuminance($first);
        $secondLuminance = $this->relativeLuminance($second);

        return (max($firstLuminance, $secondLuminance) + 0.05)
            / (min($firstLuminance, $secondLuminance) + 0.05);
    }

    private function normalizedHex(string $color): string
    {
        $hex = ltrim($color, '#');

        if (strlen($hex) === 3 || strlen($hex) === 4) {
            return $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return substr($hex, 0, 6);
    }

    private function mixHexColors(string $base, string $target, float $targetWeight): string
    {
        $channels = [];

        for ($index = 0; $index < 3; $index++) {
            $baseChannel = hexdec(substr($base, $index * 2, 2));
            $targetChannel = hexdec(substr($target, $index * 2, 2));
            $channels[] = (int) round($baseChannel * (1 - $targetWeight) + $targetChannel * $targetWeight);
        }

        return sprintf('#%02x%02x%02x', ...$channels);
    }
}
