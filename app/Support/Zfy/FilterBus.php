<?php

namespace App\Support\Zfy;

use Illuminate\Support\Facades\Log;
use Throwable;

class FilterBus
{
    /**
     * @var array<string, array<int, list<callable>>>
     */
    private array $filters = [];

    public function filter(string $hook, callable $callback, int $priority = 10): void
    {
        $this->filters[$hook][$priority] ??= [];
        $this->filters[$hook][$priority][] = $callback;
    }

    public function apply(string $hook, mixed $value, mixed ...$args): mixed
    {
        foreach ($this->callbacks($hook) as $callback) {
            try {
                $value = $callback($value, ...$args);
            } catch (Throwable $exception) {
                Log::error('zfy filter failed', [
                    'hook' => $hook,
                    'exception' => $exception,
                ]);
            }
        }

        return $value;
    }

    public function has(string $hook): bool
    {
        return isset($this->filters[$hook]);
    }

    /**
     * @return list<callable>
     */
    public function callbacks(string $hook): array
    {
        $groups = $this->filters[$hook] ?? [];
        ksort($groups);

        return array_merge(...array_values($groups ?: [[]]));
    }
}
