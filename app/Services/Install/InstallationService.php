<?php

namespace App\Services\Install;

use App\Models\PageLayout;
use App\Models\PointsAccount;
use App\Models\Setting;
use App\Models\Theme;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InstallationService
{
    public function __construct(private readonly InstallationState $state) {}

    public function checks(): array
    {
        $envPath = base_path('.env');

        return [
            ...array_map(fn ($extension) => ['key' => $extension, 'label' => 'PHP '.$extension, 'ok' => extension_loaded($extension), 'detail' => extension_loaded($extension) ? '已启用' : '未启用'], ['bcmath', 'curl', 'dom', 'fileinfo', 'gd', 'mbstring', 'openssl', 'phar', 'sodium', 'xmlwriter']),
            [
                'key' => 'php',
                'label' => 'PHP 版本',
                'ok' => version_compare(PHP_VERSION, '8.2.0', '>='),
                'detail' => PHP_VERSION,
            ],
            [
                'key' => 'pdo_mysql',
                'label' => 'PDO MySQL 扩展',
                'ok' => extension_loaded('pdo_mysql'),
                'detail' => extension_loaded('pdo_mysql') ? '已启用' : '未启用',
            ],
            [
                'key' => 'storage',
                'label' => 'storage 可写',
                'ok' => is_writable(storage_path()),
                'detail' => storage_path(),
            ],
            [
                'key' => 'env',
                'label' => '.env 可写',
                'ok' => (File::exists($envPath) && is_writable($envPath)) || (! File::exists($envPath) && is_writable(base_path())),
                'detail' => $envPath,
            ],
        ];
    }

    public function install(array $payload): array
    {
        return Cache::store('file')->lock('zfy-install', 180)->block(1, function () use ($payload) {
            if ($this->state->installed()) {
                throw ValidationException::withMessages(['install' => '系统已经安装。']);
            }

            return $this->performInstallation($payload);
        });
    }

    private function performInstallation(array $payload): array
    {
        $failed = array_filter($this->checks(), fn ($check) => ! $check['ok']);
        if ($failed !== []) {
            throw ValidationException::withMessages(['environment' => '环境检查未通过：'.implode('、', array_column($failed, 'label'))]);
        }
        $db = $payload['db'];
        $site = $payload['site'];
        $admin = $payload['admin'];
        $environment = [
            'APP_NAME' => $site['name'],
            'APP_URL' => $site['url'],
            'APP_KEY' => config('app.key') ?: 'base64:'.base64_encode(random_bytes(32)),
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $db['host'],
            'DB_PORT' => $db['port'],
            'DB_DATABASE' => $db['database'],
            'DB_USERNAME' => $db['username'],
            'DB_PASSWORD' => $db['password'],
            'SESSION_DRIVER' => 'database',
            'CACHE_STORE' => 'file',
            'QUEUE_CONNECTION' => 'database',
            'ZFY_INSTALLED' => 'true',
        ];

        $this->configureDatabase($db);
        DB::connection('mysql')->getPdo();
        if (Schema::getTables() !== []) {
            throw ValidationException::withMessages(['db.database' => '安装需要空数据库，请选择一个新的数据库。']);
        }
        if (Artisan::call('migrate', ['--force' => true]) !== 0) {
            throw ValidationException::withMessages(['install' => '数据库迁移失败。']);
        }

        $this->seedCore($site, $admin);

        $this->persistEnvironment($environment);
        Artisan::call('config:clear');

        $this->state->markInstalled([
            'site' => $site['name'],
            'admin_email' => $admin['email'],
        ]);

        zfy_emit('zfy_system_installed', $site, array_diff_key($admin, ['password' => true, 'password_confirmation' => true]));

        return $environment;
    }

    public function persistEnvironment(array $values): void
    {
        $this->writeEnvironment($values);
    }

    private function configureDatabase(array $db): void
    {
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.host', $db['host']);
        Config::set('database.connections.mysql.port', $db['port']);
        Config::set('database.connections.mysql.database', $db['database']);
        Config::set('database.connections.mysql.username', $db['username']);
        Config::set('database.connections.mysql.password', $db['password']);

        DB::purge('mysql');
    }

    private function seedCore(array $site, array $admin): void
    {
        foreach (config('zfy.roles') as $role) {
            Role::findOrCreate($role);
        }

        foreach ([
            'manage system',
            'manage contents',
            'manage commerce',
            'manage themes',
            'manage plugins',
            'manage users',
            'manage links',
            'publish contents',
            'buy contents',
        ] as $permission) {
            Permission::findOrCreate($permission);
        }

        Role::findByName('SUPER_ADMIN')->syncPermissions(Permission::all());
        Role::findByName('ADMIN')->syncPermissions(['manage contents', 'publish contents', 'manage commerce', 'manage themes', 'manage plugins', 'manage links']);
        Role::findByName('EDITOR')->syncPermissions(['manage contents', 'publish contents']);
        Role::findByName('USER')->syncPermissions(['buy contents']);

        $user = User::create([
            'name' => $admin['name'],
            'username' => $admin['username'],
            'email' => $admin['email'],
            'password' => Hash::make($admin['password']),
            'avatar_url' => '/assets/zfy/placeholders/avatar.svg',
            'bio' => 'zfy-blog 超级管理员',
            'is_author' => true,
            'author_status' => 'approved',
        ]);
        $user->syncRoles(['SUPER_ADMIN']);

        Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);
        PointsAccount::firstOrCreate(['user_id' => $user->id], ['points' => 0]);

        foreach (config('zfy.themes') as $slug => $theme) {
            Theme::updateOrCreate(['slug' => $slug], [
                'name' => $theme['name'],
                'version' => '1.0.0',
                'author' => 'zfy-blog',
                'compatible' => '^1.0',
                'entry_view' => "themes.{$slug}.layout",
                'preview' => "/assets/zfy/placeholders/{$theme['tone']}.svg",
                'menus' => ['primary', 'mobile', 'footer'],
                'regions' => ['home', 'sidebar', 'footer', 'user-center'],
                'settings_schema' => [
                    'global' => ['logo_text', 'primary_color', 'nav', 'footer_text'],
                    'pages' => ['home', 'channel', 'detail', 'vip', 'user', 'admin'],
                    'builder' => ['row', 'columns', 'content-feed', 'rank', 'vip', 'ad', 'html', 'theme-component'],
                ],
                'is_active' => $slug === config('zfy.default_theme'),
            ]);
        }

        Setting::updateOrCreate(['key' => 'site.name'], ['value' => ['raw' => $site['name']], 'autoload' => true]);
        Setting::updateOrCreate(['key' => 'site.url'], ['value' => ['raw' => $site['url']], 'autoload' => true]);
        Setting::updateOrCreate(['key' => 'site.active_theme'], [
            'value' => ['slug' => config('zfy.default_theme')],
            'autoload' => true,
        ]);

        PageLayout::updateOrCreate(['scope' => 'home'], [
            'title' => '首页布局',
            'schema' => [
                'blocks' => [
                    ['type' => 'hero', 'title' => $site['name'], 'subtitle' => '内容、资源与商城一体化平台'],
                    ['type' => 'content-feed', 'title' => '最新内容'],
                ],
            ],
            'status' => 'published',
        ]);
    }

    private function writeEnvironment(array $values): void
    {
        $path = base_path('.env');

        if (! File::exists($path)) {
            File::copy(base_path('.env.example'), $path);
        }

        $contents = File::get($path);

        foreach ($values as $key => $value) {
            $line = $key.'='.$this->envValue($value);
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

            if (preg_match($pattern, $contents)) {
                $contents = preg_replace_callback($pattern, fn () => $line, $contents) ?? $contents;
            } else {
                $contents .= PHP_EOL.$line;
            }
        }

        File::replace($path, $contents);
    }

    private function envValue(mixed $value): string
    {
        $value = (string) $value;

        if ($value === '') {
            return '';
        }

        if (str_contains($value, "\n") || str_contains($value, "\r")) {
            throw ValidationException::withMessages(['environment' => '配置值不能包含换行。']);
        }

        return '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"';
    }
}
