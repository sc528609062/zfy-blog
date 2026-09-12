<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Updates\ExtensionUpdater;
use Illuminate\Http\Request;

class AdminExtensionUpdateController extends Controller
{
    private function authorizeType(Request $request, string $type): void
    {
        abort_unless(in_array($type, ['plugin', 'theme'], true) && $request->user()?->can('manage '.$type.'s'), 403);
    }

    public function source(Request $request, string $type, string $slug, ExtensionUpdater $updater)
    {
        $this->authorizeType($request, $type);

        return response()->json(['data' => $updater->source($type, $slug), 'can_configure' => $request->user()->can('manage system'), 'updates' => array_values(array_filter($updater->statuses(), fn ($state) => $state['type'] === $type && $state['slug'] === $slug))]);
    }

    public function save(Request $request, string $type, string $slug, ExtensionUpdater $updater)
    {
        $this->authorizeType($request, $type);
        abort_unless($request->user()->can('manage system'), 403);
        $data = $request->validate(['provider' => ['required', 'in:github,gitee'], 'repository' => ['required', 'string', 'max:150', 'regex:~^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$~'], 'public_key' => ['required', 'string', 'max:8192']]);
        $updater->saveSource($type, $slug, $data);

        return response()->json(['message' => '更新源和信任公钥已保存']);
    }

    public function check(Request $request, string $type, string $slug, ExtensionUpdater $updater)
    {
        $this->authorizeType($request, $type);

        return response()->json(['data' => $updater->check($type, $slug)]);
    }

    public function apply(Request $request, string $type, string $slug, ExtensionUpdater $updater)
    {
        $this->authorizeType($request, $type);
        $data = $request->validate(['id' => ['required', 'uuid']]);
        $updater->enqueue($type, $slug, $data['id']);

        return response()->json(['message' => '扩展更新已排队，计划任务将执行安装', 'id' => $data['id']], 202);
    }
}
