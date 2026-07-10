<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Theme;
use App\Models\ThemeSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ThemeManager
{
    public function active(): array
    {
        $defaultSlug = config('zfy.default_theme');

        if (! Schema::hasTable('settings') || ! Schema::hasTable('themes')) {
            return $this->fallbackTheme($defaultSlug);
        }

        return Cache::remember('zfy.active_theme', 60, function () {
            $slug = data_get(Setting::where('key', 'site.active_theme')->first()?->value, 'slug', config('zfy.default_theme'));
            $theme = Theme::where('slug', $slug)->first();

            if (! $theme) {
                return $this->fallbackTheme($slug);
            }

            $settings = $this->settingsFor($theme);
            $fallbackAccent = data_get(config("zfy.themes.{$theme->slug}"), 'accent', '#1684ff');

            return [
                'model' => $theme,
                'slug' => $theme->slug,
                'name' => $theme->name,
                'view' => $theme->entry_view,
                'accent' => $this->primaryColor($settings, $fallbackAccent),
                'settings' => $settings,
            ];
        });
    }

    public function activate(string $slug): void
    {
        Theme::query()->update(['is_active' => false]);
        Theme::where('slug', $slug)->update(['is_active' => true]);

        Setting::updateOrCreate(
            ['key' => 'site.active_theme'],
            ['value' => ['slug' => $slug], 'autoload' => true]
        );

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

        return array_replace_recursive($this->defaultSettings($theme->slug), $settings);
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
            ],
            'files-channel' => [
                'card_style' => 'commerce',
                'show_vip_price' => true,
            ],
        ];

        return match ($slug) {
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
    }

    private function fallbackTheme(string $slug): array
    {
        return [
            'model' => null,
            'slug' => $slug,
            'name' => data_get(config("zfy.themes.{$slug}"), 'name', 'Default'),
            'view' => "themes.{$slug}.layout",
            'accent' => data_get(config("zfy.themes.{$slug}"), 'accent', '#1684ff'),
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
}
