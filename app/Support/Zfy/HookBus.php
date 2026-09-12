<?php

namespace App\Support\Zfy;

class HookBus extends CallbackRegistry
{
    public function on(string $hook, callable $callback, int $priority = 10, ?int $acceptedArgs = null): string
    {
        return $this->add($hook, $callback, $priority, $acceptedArgs, false);
    }

    public function once(string $hook, callable $callback, int $priority = 10, ?int $acceptedArgs = null): string
    {
        return $this->add($hook, $callback, $priority, $acceptedArgs, true);
    }

    public function emit(string $hook, mixed ...$args): void
    {
        $this->dispatch($hook, $args, false, false);
    }

    public function emitStrict(string $hook, mixed ...$args): void
    {
        $this->dispatch($hook, $args, false, true);
    }
}
