<?php

namespace App\Support\Zfy;

use Illuminate\Support\Facades\Log;
use Throwable;

class HookBus
{
    /**
     * @var array<string, array<int, list<callable>>>
     */
    private array $listeners = [];

    public function on(string $hook, callable $callback, int $priority = 10): void
    {
        $this->listeners[$hook][$priority] ??= [];
        $this->listeners[$hook][$priority][] = $callback;
    }

    public function emit(string $hook, mixed ...$args): void
    {
        foreach ($this->callbacks($hook) as $callback) {
            try {
                $callback(...$args);
            } catch (Throwable $exception) {
                Log::error('zfy hook failed', [
                    'hook' => $hook,
                    'exception' => $exception,
                ]);
            }
        }
    }

    public function has(string $hook): bool
    {
        return isset($this->listeners[$hook]);
    }

    /**
     * @return list<callable>
     */
    public function callbacks(string $hook): array
    {
        $groups = $this->listeners[$hook] ?? [];
        ksort($groups);

        return array_merge(...array_values($groups ?: [[]]));
    }
}
