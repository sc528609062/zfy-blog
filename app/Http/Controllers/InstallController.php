<?php

namespace App\Http\Controllers;

use App\Services\Install\InstallationService;
use App\Services\Install\InstallationState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class InstallController extends Controller
{
    public function show(InstallationState $state, InstallationService $installer)
    {
        if ($state->installed()) {
            return redirect()->route('admin.dashboard');
        }

        return view('install.shell', [
            'payload' => [
                'installed' => false,
                'checks' => $installer->checks(),
                'defaults' => [
                    'db' => [
                        'driver' => 'mysql',
                        'host' => env('DB_HOST', '127.0.0.1'),
                        'port' => env('DB_PORT', '3306'),
                        'database' => env('DB_DATABASE', 'zfy_blog'),
                        'username' => env('DB_USERNAME', 'zfy_blog'),
                        'password' => env('DB_PASSWORD', 'zfy_blog'),
                    ],
                    'site' => [
                        'name' => env('APP_NAME', 'zfy-blog'),
                        'url' => env('APP_URL', url('/')),
                    ],
                    'admin' => [
                        'name' => '站长',
                        'username' => 'admin',
                        'email' => 'admin@zfy-blog.test',
                    ],
                ],
                'routes' => [
                    'install' => route('install.store', [], false),
                    'login' => route('login', [], false),
                    'admin' => route('admin.dashboard', [], false),
                ],
            ],
        ]);
    }

    public function store(Request $request, InstallationState $state, InstallationService $installer): JsonResponse
    {
        if ($state->installed()) {
            return response()->json(['message' => '系统已经安装，安装页面已锁定。'], 409);
        }

        $data = $request->validate([
            'db.host' => ['required', 'string', 'max:120'],
            'db.port' => ['required', 'integer', 'between:1,65535'],
            'db.database' => ['required', 'string', 'max:120'],
            'db.username' => ['required', 'string', 'max:120'],
            'db.password' => ['nullable', 'string', 'max:240'],
            'site.name' => ['required', 'string', 'max:120'],
            'site.url' => ['nullable', 'url', 'max:240'],
            'admin.name' => ['required', 'string', 'max:120'],
            'admin.username' => ['required', 'alpha_dash', 'max:80'],
            'admin.email' => ['required', 'email', 'max:160'],
            'admin.password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $data['site']['url'] = $data['site']['url'] ?? $request->getSchemeAndHttpHost();

        try {
            $environment = $installer->install($data);
            app()->terminating(fn () => $installer->persistEnvironment($environment));
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => '安装失败：'.$exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => '安装完成',
            'redirect' => route('login', [], false),
        ]);
    }
}
