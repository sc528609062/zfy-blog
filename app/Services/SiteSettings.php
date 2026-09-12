<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\Zfy\SettingsRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SiteSettings
{
    public function __construct(private readonly SettingsRegistry $registry) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get(Setting::where('key', $key)->value('value'), 'raw', $default);
    }

    public function schema(?string $groupKey = null): array
    {
        $stored = Setting::all()->keyBy('key');

        return collect($this->registry->schema())
            ->filter(fn ($group) => $groupKey === null || $group['key'] === $groupKey)
            ->map(function ($group) use ($stored) {
                $group['fields'] = array_map(function ($field) use ($stored) {
                    $field['value'] = data_get($stored->get($field['key']), 'value.raw', $field['default']);

                    return $field;
                }, $group['fields']);

                return $group;
            })->values()->all();
    }

    public function save(string $groupKey, array $values): void
    {
        $group = collect($this->registry->schema())->firstWhere('key', $groupKey);
        abort_unless($group && ($group['scope'] ?? 'system') === 'system', 404);
        $fields = collect($group['fields'])->keyBy('key');

        if (array_diff(array_keys($values), $fields->keys()->all())) {
            throw ValidationException::withMessages(['values' => '包含未注册的设置项。']);
        }

        $validated = [];
        foreach ($values as $key => $value) {
            $field = $fields[$key];
            $rules = $field['rules'] ?: match ($field['type']) {
                'boolean' => ['required', 'boolean'],
                'number' => ['required', 'integer', 'min:1', 'max:100'],
                'url' => ['required', 'url:http,https', 'max:2048'],
                'select' => ['required', Rule::in(array_keys($field['options']))],
                default => ['required', 'string', 'max:255'],
            };
            if ($key === 'permalink.content_base') {
                $rules = ['required', 'string', 'regex:/^[a-z][a-z0-9-]{0,39}$/', Rule::notIn(['admin', 'api', 'install', 'login', 'register', 'user', 'media', 'storage', 'p', 'c', 'tag', 'topic', 'payments', 'orders', 'buy', 'shop', 'vip', 'authors', 'author', 'downloads', 'reset-password', 'points-store'])];
            }
            $validated[$key] = Validator::make(['value' => $value], ['value' => $rules], [], ['value' => $field['label']])->validate()['value'];
        }

        DB::transaction(function () use ($validated) {
            zfy_validate('zfy_settings_saving', $validated);
            foreach ($validated as $key => $value) {
                Setting::updateOrCreate(['key' => $key], ['value' => ['raw' => $value], 'autoload' => true]);
            }
            zfy_after_commit('zfy_settings_saved', $validated);
        });
    }
}
