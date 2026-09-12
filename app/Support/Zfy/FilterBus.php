<?php

namespace App\Support\Zfy;

class FilterBus extends CallbackRegistry
{
    public function once(string $hook, callable $callback, int $priority = 10, ?int $acceptedArgs = null): string
    {
        return $this->add($hook, $callback, $priority, $acceptedArgs, true);
    }

    public function filter(string $hook, callable $callback, int $priority = 10, ?int $acceptedArgs = null): string
    {
        return $this->add($hook, $callback, $priority, $acceptedArgs, false);
    }

    public function apply(string $hook, mixed $value, mixed ...$args): mixed
    {
        return $this->dispatch($hook, [$value, ...$args], true, false);
    }

    public function applyStrict(string $hook, mixed $value, mixed ...$args): mixed
    {
        return $this->dispatch($hook, [$value, ...$args], true, true);
    }
}
