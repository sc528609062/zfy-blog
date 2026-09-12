<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plugin;
use App\Services\PackageManifestService;
use App\Services\PluginInstaller;
use App\Services\PluginLifecycleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminPluginController extends Controller
{
    public function uninstall(Request $request, Plugin $plugin, PluginLifecycleManager $manager)
    {
        abort_unless($request->user()?->can('manage plugins'), 403);
        $request->validate(['delete_data' => ['prohibited']]);
        $manager->uninstall($plugin);

        return response()->json(['message' => '插件已卸载']);
    }

    public function purge(Request $request, Plugin $plugin, PluginLifecycleManager $manager)
    {
        abort_unless($request->user()?->can('manage plugins'), 403);
        $manager->purge($plugin);

        return response()->json(['message' => '插件数据已清理']);
    }

    public function install(Request $request, PluginInstaller $installer)
    {
        abort_unless($request->user()?->can('manage plugins'), 403);
        $request->validate(['file' => ['required', 'file', 'mimes:zip', 'max:20480']]);
        $plugin = $installer->install($request->file('file'));

        return response()->json(['message' => '插件已安装，可在插件列表启用。', 'data' => $plugin], 201);
    }

    public function settings(Request $request, PackageManifestService $packages)
    {
        abort_unless($request->user()?->can('manage plugins'), 403);
        $manifests = $packages->plugins();

        return response()->json(['data' => Plugin::with('settings')->get()->map(function ($plugin) use ($manifests) {
            $fields = $this->fields($manifests[$plugin->slug] ?? []);

            return ['id' => $plugin->id, 'slug' => $plugin->slug, 'name' => $plugin->name, 'enabled' => $plugin->enabled, 'installed' => isset($manifests[$plugin->slug]), 'fields' => array_map(function ($field) use ($plugin) {
                $field['value'] = data_get($plugin->settings->firstWhere('key', $field['key']), 'value.raw', $field['default'] ?? '');

                return $field;
            }, $fields)];
        })]);
    }

    public function save(Request $request, Plugin $plugin, PackageManifestService $packages)
    {
        abort_unless($request->user()?->can('manage plugins'), 403);
        $data = $request->validate(['values' => ['present', 'array']]);
        $fields = collect($this->fields($packages->plugins()[$plugin->slug] ?? []))->keyBy('key');
        if (array_diff(array_keys($data['values']), $fields->keys()->all())) {
            throw ValidationException::withMessages(['values' => '包含未注册的插件设置。']);
        }
        foreach ($data['values'] as $key => $value) {
            $field = $fields[$key];
            $rules = $field['rules'] ?? match ($field['type'] ?? 'text') {
                'boolean' => ['required', 'boolean'], 'number' => ['nullable', 'numeric'],
                'select' => ['nullable', Rule::in(array_keys($field['options'] ?? []))],
                default => ['nullable', 'string', 'max:10000'],
            };
            Validator::make(['value' => $value], ['value' => $rules])->validate();
        }
        DB::transaction(function () use ($data, $plugin) {
            foreach ($data['values'] as $key => $value) {
                $plugin->settings()->updateOrCreate(['key' => $key], ['value' => ['raw' => $value]]);
            }
        });

        return response()->json(['message' => '插件设置已保存']);
    }

    private function fields(array $manifest): array
    {
        $fields = collect($manifest['settings_schema'] ?? [])->flatMap(fn ($group) => is_array($group) ? ($group['fields'] ?? []) : [])->values()->all();
        if ($fields === []) {
            $fields = array_map(fn ($key) => ['key' => $key, 'label' => $key, 'type' => 'text'], array_filter($manifest['settings'] ?? [], 'is_string'));
        }

        return $fields;
    }
}
