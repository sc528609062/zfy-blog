<?php

namespace App\Support\Zfy;

class SettingsRegistry
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $groups = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $settings = [];

    public function group(array $definition): void
    {
        $key = (string) ($definition['key'] ?? '');

        if ($key === '') {
            return;
        }

        $this->groups[$key] = array_replace([
            'key' => $key,
            'label' => $key,
            'description' => '',
            'scope' => 'system',
            'position' => 100,
        ], $this->groups[$key] ?? [], $definition);
    }

    public function setting(array $definition): void
    {
        $key = (string) ($definition['key'] ?? '');
        $group = (string) ($definition['group'] ?? '');

        if ($key === '' || $group === '') {
            return;
        }

        if (! isset($this->groups[$group])) {
            $this->group(['key' => $group, 'label' => $group]);
        }

        $this->settings[$key] = array_replace([
            'key' => $key,
            'label' => $key,
            'type' => 'text',
            'default' => null,
            'group' => $group,
            'rules' => [],
            'options' => [],
            'position' => 100,
        ], $definition);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function schema(): array
    {
        $groups = $this->groups;
        uasort($groups, fn (array $a, array $b) => [$a['position'], $a['label']] <=> [$b['position'], $b['label']]);

        return collect($groups)->map(function (array $group) {
            $fields = collect($this->settings)
                ->filter(fn (array $setting) => $setting['group'] === $group['key'])
                ->sortBy([['position', 'asc'], ['label', 'asc']])
                ->values()
                ->all();

            return array_merge($group, ['fields' => $fields]);
        })->filter(fn (array $group) => $group['fields'] !== [])->values()->all();
    }
}
