<?php

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * 安装器：6 步流程
 *
 * 1. index         欢迎页
 * 2. environment   环境检查（PHP/扩展/可写目录）
 * 3. database      数据库配置 + 测试 + 写 .env
 * 4. redis         Redis 配置（可跳过）
 * 5. site          站点信息（名称/邮箱/时区）
 * 6. admin         创建超级管理员 + 跑迁移 + 跑 seeder
 * 7. complete      完成（写 lock 文件）
 */
class InstallController extends Controller
{
    public function index(): View
    {
        return view('install.index', [
            'step' => 0,
            'title' => '欢迎安装 zfy-blog',
        ]);
    }

    public function environment(): View
    {
        $checks = [];

        $checks[] = [
            'name' => 'PHP 版本',
            'required' => '>= ' . config('zfy.install.min_php'),
            'current' => PHP_VERSION,
            'pass' => version_compare(PHP_VERSION, config('zfy.install.min_php'), '>='),
        ];

        foreach (config('zfy.install.required_extensions') as $ext) {
            $checks[] = [
                'name' => "扩展 {$ext}",
                'required' => '必需',
                'current' => extension_loaded($ext) ? '已启用' : '未启用',
                'pass' => extension_loaded($ext),
            ];
        }

        foreach (config('zfy.install.recommended_extensions') as $ext) {
            $checks[] = [
                'name' => "扩展 {$ext}",
                'required' => '推荐',
                'current' => extension_loaded($ext) ? '已启用' : '未启用',
                'pass' => true,
                'warn' => ! extension_loaded($ext),
            ];
        }

        foreach (config('zfy.install.required_writable') as $path) {
            $abs = base_path($path);
            $writable = is_writable($abs);
            $checks[] = [
                'name' => "可写 {$path}",
                'required' => '必需',
                'current' => $writable ? '可写' : '不可写',
                'pass' => $writable,
            ];
        }

        $passed = collect($checks)->every(fn ($c) => $c['pass']);

        return view('install.environment', [
            'step' => 1,
            'checks' => $checks,
            'passed' => $passed,
        ]);
    }

    public function database(): View
    {
        return view('install.database', [
            'step' => 2,
            'old' => session('install.db', [
                'connection' => 'mysql',
                'host' => '127.0.0.1',
                'port' => '3306',
                'database' => 'zfy_blog',
                'username' => 'zfy_blog',
                'password' => '',
            ]),
        ]);
    }

    public function storeDatabase(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'connection' => 'required|in:mysql,mariadb,pgsql,sqlite',
            'host'       => 'required_unless:connection,sqlite|string',
            'port'       => 'required_unless:connection,sqlite|string',
            'database'   => 'required|string',
            'username'   => 'required_unless:connection,sqlite|string',
            'password'   => 'nullable|string',
        ]);

        // 测试连接
        config([
            'database.connections.install' => [
                'driver'   => $data['connection'],
                'host'     => $data['host'] ?? null,
                'port'     => $data['port'] ?? null,
                'database' => $data['database'],
                'username' => $data['username'] ?? null,
                'password' => $data['password'] ?? '',
                'charset'  => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ],
        ]);

        try {
            DB::connection('install')->getPdo();
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['db' => '连接失败：' . $e->getMessage()]);
        }

        session()->put('install.db', $data);
        $this->writeEnv([
            'DB_CONNECTION' => $data['connection'],
            'DB_HOST'       => $data['host'] ?? '127.0.0.1',
            'DB_PORT'       => $data['port'] ?? '3306',
            'DB_DATABASE'   => $data['database'],
            'DB_USERNAME'   => $data['username'] ?? '',
            'DB_PASSWORD'   => $data['password'] ?? '',
        ]);

        return redirect()->route('install.redis');
    }

    public function redis(): View
    {
        return view('install.redis', [
            'step' => 3,
            'old' => session('install.redis', [
                'host'     => '127.0.0.1',
                'port'     => '6379',
                'password' => '',
                'enabled'  => true,
            ]),
        ]);
    }

    public function storeRedis(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled'  => 'sometimes',
            'host'     => 'required_with:enabled|string',
            'port'     => 'required_with:enabled|string',
            'password' => 'nullable|string',
        ]);

        $enabled = ! empty($data['enabled']);

        if ($enabled) {
            try {
                config([
                    'database.redis.install' => [
                        'host' => $data['host'],
                        'port' => $data['port'],
                        'password' => $data['password'] ?: null,
                    ],
                ]);
                Redis::connection('install')->ping();
            } catch (\Throwable $e) {
                return back()->withInput()->withErrors(['redis' => '连接失败：' . $e->getMessage()]);
            }

            $this->writeEnv([
                'REDIS_HOST'     => $data['host'],
                'REDIS_PORT'     => $data['port'],
                'REDIS_PASSWORD' => $data['password'] ?: 'null',
                'CACHE_STORE'    => 'redis',
                'QUEUE_CONNECTION' => 'redis',
            ]);
        } else {
            $this->writeEnv([
                'CACHE_STORE'      => 'database',
                'QUEUE_CONNECTION' => 'database',
            ]);
        }

        session()->put('install.redis', $data);

        return redirect()->route('install.site');
    }

    public function site(): View
    {
        return view('install.site', [
            'step' => 4,
            'old' => session('install.site', [
                'name'    => 'zfy-blog',
                'tagline' => '精品内容 · 资源分享 · 创作者社区',
                'url'     => url('/'),
            ]),
        ]);
    }

    public function storeSite(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'    => 'required|string|max:80',
            'tagline' => 'nullable|string|max:200',
            'url'     => 'required|url',
        ]);

        session()->put('install.site', $data);

        $this->writeEnv([
            'APP_NAME' => '"' . $data['name'] . '"',
            'APP_URL'  => $data['url'],
        ]);

        return redirect()->route('install.admin');
    }

    public function admin(): View
    {
        return view('install.admin', [
            'step' => 5,
        ]);
    }

    public function storeAdmin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => 'required|string|min:3|max:64|alpha_dash',
            'email'    => 'required|email|max:150',
            'password' => 'required|string|min:8|max:60|confirmed',
        ]);

        session()->put('install.admin', $data);

        return redirect()->route('install.finalize');
    }

    public function finalize(): View
    {
        // 跑迁移 + 种子 + 建管理员 + 写 lock
        try {
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);

            $admin = session('install.admin');
            if ($admin) {
                $user = User::firstOrCreate(
                    ['email' => $admin['email']],
                    [
                        'name'     => $admin['username'],
                        'username' => $admin['username'],
                        'password' => Hash::make($admin['password']),
                        'status'   => 'active',
                        'email_verified_at' => now(),
                    ]
                );
                $user->assignRole('SUPER_ADMIN');
            }

            $site = session('install.site');
            if ($site) {
                app(SettingsService::class)->setMany([
                    'site.name'    => $site['name'],
                    'site.tagline' => $site['tagline'] ?? '',
                ]);
            }

            // 写 lock
            $lock = config('zfy.install.lock_file');
            @mkdir(dirname($lock), 0755, true);
            file_put_contents($lock, json_encode([
                'installed_at' => now()->toIso8601String(),
                'version'      => config('zfy.version'),
            ], JSON_PRETTY_PRINT));

            session()->forget(['install.db', 'install.redis', 'install.site', 'install.admin']);

            return view('install.complete', ['step' => 6]);
        } catch (\Throwable $e) {
            return view('install.complete', [
                'step'  => 6,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function complete(): View
    {
        return view('install.complete', ['step' => 6]);
    }

    protected function writeEnv(array $kv): void
    {
        $path = base_path('.env');
        if (! is_file($path)) {
            return;
        }
        $env = file_get_contents($path);

        foreach ($kv as $k => $v) {
            $line = "{$k}={$v}";
            if (preg_match("/^{$k}=.*$/m", $env)) {
                $env = preg_replace("/^{$k}=.*$/m", $line, $env);
            } else {
                $env .= PHP_EOL . $line;
            }
        }

        file_put_contents($path, $env);
    }
}
