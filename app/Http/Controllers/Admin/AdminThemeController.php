<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use App\Services\ThemeConfiguration;
use App\Services\ThemeInstaller;
use App\Services\ThemeLifecycleManager;
use App\Services\ThemeManager;
use App\Services\ThemePackageLoader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminThemeController extends Controller
{
    public function install(Request $request, ThemeInstaller $installer)
    {
        abort_unless($request->user()?->can('manage themes'), 403);
        $request->validate(['file' => ['required', 'file', 'mimes:zip', 'max:20480']]);
        $theme = $installer->install($request->file('file'));

        return response()->json(['message' => '主题已安装', 'data' => $theme], 201);
    }

    public function index(Request $request, ThemeManager $themes)
    {
        abort_unless($request->user()?->can('manage themes'), 403);

        return response()->json(['groups' => app(ThemeConfiguration::class)->groups(), 'data' => Theme::all()->map(function ($theme) use ($themes) {
            $fields = app(ThemeConfiguration::class)->fields($theme);
            $defaults = app(ThemeConfiguration::class)->defaults($theme);
            $values = $themes->settingsFor($theme)['global'];

            return ['id' => $theme->id, 'slug' => $theme->slug, 'name' => $theme->name, 'is_active' => $theme->is_active, 'installed' => is_file(base_path('themes/'.$theme->slug.'/theme.json')), 'fields' => $fields, 'defaults' => $defaults, 'values' => collect($fields)->mapWithKeys(fn ($field) => [$field['key'] => $values[$field['key']] ?? $defaults[$field['key']]])];
        })]);
    }

    public function save(Request $request, Theme $theme, ThemeManager $themes)
    {
        abort_unless($request->user()?->can('manage themes'), 403);
        $data = app(ThemeConfiguration::class)->validate($theme, $request->except('_token'));
        DB::transaction(function () use ($data, $theme) {
            zfy_validate('zfy_theme_settings_saving', $theme, $data);
            foreach ($data as $key => $value) {
                $theme->settings()->updateOrCreate(['scope' => 'global', 'key' => $key], ['value' => ['raw' => $value]]);
            }
            zfy_after_commit('zfy_theme_settings_saved', $theme, $data);
        });
        $themes->forgetActiveCache();

        return response()->json(['message' => '主题配置已保存']);
    }

    public function preview(Request $request, Theme $theme)
    {
        abort_unless($request->user()?->can('manage themes'), 403);
        abort_unless(is_file(base_path('themes/'.$theme->slug.'/theme.json')) && view()->exists($theme->entry_view), 422, '主题安装文件或模板不存在，请重新安装。');
        app(ThemePackageLoader::class)->chain($theme->slug);
        $data = app(ThemeConfiguration::class)->validate($theme, $request->except('_token'));
        $token = (string) Str::uuid();
        Cache::store('file')->put('zfy-theme-preview-'.$token, ['theme_id' => $theme->id, 'user_id' => $request->user()->id, 'values' => $data], 600);

        return response()->json(['url' => url('/?theme_preview='.$token)]);
    }

    public function uninstall(Request $request, Theme $theme, ThemeLifecycleManager $manager)
    {
        abort_unless($request->user()?->can('manage themes'), 403);
        $manager->uninstall($theme);

        return response()->json(['message' => '主题已卸载，配置已保留']);
    }

    public function purge(Request $request, Theme $theme, ThemeLifecycleManager $manager)
    {
        abort_unless($request->user()?->can('manage themes'), 403);
        $request->validate(['confirm_slug' => ['required', Rule::in([$theme->slug])]]);
        $manager->purge($theme);

        return response()->json(['message' => '主题配置已清理']);
    }
}
