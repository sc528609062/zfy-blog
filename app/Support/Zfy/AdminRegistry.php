<?php

namespace App\Support\Zfy;

use Illuminate\Contracts\Auth\Authenticatable;

class AdminRegistry
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $groups = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $pages = [];

    public function group(array $definition): void
    {
        $key = (string) ($definition['key'] ?? '');

        if ($key === '') {
            return;
        }

        $this->groups[$key] = array_replace([
            'key' => $key,
            'label' => $key,
            'icon' => 'Menu',
            'position' => 100,
        ], $this->groups[$key] ?? [], $definition);
    }

    public function page(array $definition): void
    {
        $key = (string) ($definition['key'] ?? '');
        $group = (string) ($definition['group'] ?? '');

        if ($key === '' || $group === '') {
            return;
        }

        if (! isset($this->groups[$group])) {
            $this->group(['key' => $group, 'label' => $group]);
        }

        $this->pages[$key] = array_replace([
            'key' => $key,
            'label' => $key,
            'description' => '后台管理页面',
            'group' => $group,
            'kind' => 'placeholder',
            'status' => 'placeholder',
            'permission' => null,
            'position' => 100,
        ], $definition);
    }

    public function findPage(string $key): ?array
    {
        return $this->pages[$key] ?? null;
    }

    public function pageOrFallback(string $key): array
    {
        return $this->findPage($key) ?? [
            'key' => $key,
            'label' => '后台',
            'description' => '这个后台页面尚未注册，当前显示占位说明。',
            'group' => 'dashboard',
            'kind' => 'placeholder',
            'status' => 'placeholder',
            'permission' => null,
            'position' => 999,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function menuFor(?Authenticatable $user = null): array
    {
        $groups = $this->groups;
        uasort($groups, fn (array $a, array $b) => [$a['position'], $a['label']] <=> [$b['position'], $b['label']]);

        return collect($groups)->map(function (array $group) use ($user) {
            $items = collect($this->pages)
                ->filter(fn (array $page) => $page['group'] === $group['key'])
                ->filter(fn (array $page) => $this->allowed($page, $user))
                ->sortBy([['position', 'asc'], ['label', 'asc']])
                ->values()
                ->map(fn (array $page) => [
                    'key' => $page['key'],
                    'label' => $page['label'],
                    'description' => $page['description'],
                    'kind' => $page['kind'],
                    'status' => $page['status'],
                ])
                ->all();

            return [
                'key' => $group['key'],
                'label' => $group['label'],
                'icon' => $group['icon'],
                'items' => $items,
            ];
        })->filter(fn (array $group) => $group['items'] !== [])->values()->all();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function pages(): array
    {
        return $this->pages;
    }

    private function allowed(array $page, ?Authenticatable $user): bool
    {
        $permission = $page['permission'] ?? null;

        if (! $permission || ! $user || ! method_exists($user, 'can')) {
            return true;
        }

        return $user->can($permission);
    }
}
