<?php

namespace App\Services;

use App\Models\Theme;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ThemeConfiguration
{
    public function defaults(Theme $theme): array
    {
        $defaults = app(ThemeManager::class)->defaultSettings($theme->slug)['global'] ?? [];

        return collect($this->fields($theme))->mapWithKeys(fn ($field) => [
            $field['key'] => $defaults[$field['key']] ?? $field['default'] ?? null,
        ])->all();
    }

    public function fields(Theme $theme): array
    {
        $fields = array_key_exists($theme->slug, config('zfy.themes', [])) ? [
            'logo_text' => ['key' => 'logo_text', 'label' => '站点标识', 'type' => 'text', 'rules' => ['required', 'string', 'max:80']],
            'primary_color' => ['key' => 'primary_color', 'label' => '强调色', 'type' => 'color', 'rules' => ['required', 'regex:/^#[a-fA-F0-9]{6}$/']],
            'footer_text' => ['key' => 'footer_text', 'label' => '页脚文字', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:500']],
        ] : [];
        if (array_key_exists($theme->slug, config('zfy.themes', []))) {
            foreach (['hero_enabled' => '显示首页横幅', 'channels_enabled' => '显示首页分类', 'sidebar_enabled' => '显示首页侧栏', 'show_author_card' => '显示作者资料', 'show_related' => '显示相关内容'] as $key => $label) {
                $fields[$key] = ['key' => $key, 'label' => $label, 'type' => 'boolean', 'default' => true];
            }
        }
        foreach (($theme->settings_schema['global'] ?? []) as $field) {
            if (! is_array($field) || ! preg_match('/^[a-z][a-z0-9_]{0,79}$/', $field['key'] ?? '')) {
                continue;
            }
            $type = $field['type'] ?? 'text';
            if (! in_array($type, ['text', 'textarea', 'color', 'boolean', 'number', 'select'], true)) {
                continue;
            }
            $fields[$field['key']] = [...$field, 'type' => $type, 'label' => $field['label'] ?? $field['key']];
        }

        return array_values($fields);
    }

    public function validate(Theme $theme, array $values): array
    {
        $fields = collect($this->fields($theme))->keyBy('key');
        if (array_diff(array_keys($values), $fields->keys()->all())) {
            throw ValidationException::withMessages(['theme' => '包含未注册的主题设置。']);
        }
        $result = [];
        foreach ($values as $key => $value) {
            $field = $fields[$key];
            $rules = $field['rules'] ?? match ($field['type']) {
                'boolean' => ['required', 'boolean'],
                'number' => ['nullable', 'numeric', 'between:-1000000,1000000'],
                'color' => ['nullable', 'regex:/^#[a-fA-F0-9]{6}$/'],
                'select' => ['nullable', Rule::in(array_keys($field['options'] ?? []))],
                default => ['nullable', 'string', 'max:4000'],
            };
            $result[$key] = Validator::make(['value' => $value], ['value' => $rules], [], ['value' => $field['label']])->validate()['value'];
        }

        return $result;
    }
}
