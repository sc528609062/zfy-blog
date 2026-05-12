<?php

namespace App\Providers;

use App\Domain\Theme\ThemeManager;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * zfy-blog 核心服务注册。
 *
 * - 单例 ThemeManager / SettingsService。
 * - 给所有视图共享 $site 配置（站点名、tagline、版本、当前主题）。
 * - 注册 @theme Blade 指令，方便主题视图引用资源 URL。
 */
class ZfyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ThemeManager::class);
        $this->app->singleton(SettingsService::class);
        $this->app->alias(ThemeManager::class, 'zfy.theme');
        $this->app->alias(SettingsService::class, 'zfy.settings');
    }

    public function boot(): void
    {
        // 共享 site 信息给所有视图
        View::composer('*', function ($view) {
            $settings = app(SettingsService::class);

            $view->with('site', [
                'name'     => $settings->get('site.name', config('zfy.name')),
                'tagline'  => $settings->get('site.tagline', config('zfy.tagline')),
                'logo'     => $settings->get('site.logo'),
                'icp'      => $settings->get('site.icp'),
                'copyright'=> $settings->get('site.copyright', '© ' . date('Y') . ' zfy-blog'),
                'version'  => config('zfy.version'),
            ]);
        });

        // @theme('css/style.css') -> /themes/{active}/assets/css/style.css
        Blade::directive('theme', function ($expression) {
            return "<?php echo '/themes/'.app('zfy.theme')->active()->slug.'/'.ltrim({$expression}, '/'); ?>";
        });

        // @setting('site.name', 'zfy-blog')
        Blade::directive('setting', function ($expression) {
            return "<?php echo app('zfy.settings')->get({$expression}); ?>";
        });
    }
}
