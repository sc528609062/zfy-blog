<?php

namespace App\Support\Zfy;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/** Registrations made during dispatch run on the next dispatch. */
abstract class CallbackRegistry
{
    private array $listeners = [];

    private array $stack = [];

    private array $counts = [];

    private array $failures = [];

    private int $sequence = 0;

    public static function canonical(string $hook): string
    {
        return match ($hook) {
            'content.created' => 'zfy_content_created',
            'content.updated' => 'zfy_content_updated',
            'content.published' => 'zfy_content_published',
            'content.deleted' => 'zfy_content_deleted',
            'order.paid' => 'zfy_order_paid',
            'vip.activated' => 'zfy_vip_activated',
            'download.created' => 'zfy_download_created',
            'comment.created' => 'zfy_comment_created',
            'user.registered' => 'zfy_user_registered',
            default => $hook,
        };
    }

    protected function add(string $hook, callable $callback, int $priority, ?int $acceptedArgs, bool $once): string
    {
        if ($hook === '' || ($acceptedArgs !== null && $acceptedArgs < 0)) {
            throw new InvalidArgumentException('A hook name and non-negative argument count are required.');
        }
        $hook = self::canonical($hook);
        $id = 'listener-'.++$this->sequence;
        $this->listeners[$hook][$id] = compact('id', 'callback', 'priority', 'acceptedArgs', 'once');

        return $id;
    }

    public function remove(string $hook, callable|string $callback, ?int $priority = null): bool
    {
        $hook = self::canonical($hook);
        $removed = false;
        foreach ($this->listeners[$hook] ?? [] as $id => $entry) {
            if (($id === $callback || $entry['callback'] === $callback) && ($priority === null || $entry['priority'] === $priority)) {
                unset($this->listeners[$hook][$id]);
                $removed = true;
            }
        }

        return $removed;
    }

    public function removeAll(string $hook): void
    {
        unset($this->listeners[self::canonical($hook)]);
    }

    public function has(string $hook): bool
    {
        return ! empty($this->listeners[self::canonical($hook)]);
    }

    public function callbacks(string $hook): array
    {
        return array_column($this->ordered(self::canonical($hook)), 'callback');
    }

    public function current(): ?string
    {
        return $this->stack === [] ? null : $this->stack[array_key_last($this->stack)];
    }

    public function did(string $hook): int
    {
        return $this->counts[self::canonical($hook)] ?? 0;
    }

    public function diagnostics(): array
    {
        $hooks = [];
        foreach ($this->listeners as $hook => $entries) {
            $hooks[$hook] = array_map(fn ($entry) => array_diff_key($entry, ['callback' => true]), array_values($entries));
        }

        return ['hooks' => $hooks, 'counts' => $this->counts, 'stack' => $this->stack, 'failures' => $this->failures];
    }

    protected function dispatch(string $hook, array $args, bool $filter, bool $strict): mixed
    {
        $hook = self::canonical($hook);
        $this->counts[$hook] = ($this->counts[$hook] ?? 0) + 1;
        $this->stack[] = $hook;
        $value = $args[0] ?? null;
        try {
            foreach ($this->ordered($hook) as $entry) {
                if (! isset($this->listeners[$hook][$entry['id']])) {
                    continue;
                }
                if ($entry['once']) {
                    unset($this->listeners[$hook][$entry['id']]);
                }
                if ($filter) {
                    $args[0] = $value;
                }
                try {
                    $result = ($entry['callback'])(...($entry['acceptedArgs'] === null ? $args : array_slice($args, 0, $entry['acceptedArgs'])));
                    if ($filter) {
                        $value = $result;
                    }
                } catch (Throwable $exception) {
                    $this->failures[] = ['hook' => $hook, 'listener' => $entry['id'], 'exception' => $exception::class];
                    $this->failures = array_slice($this->failures, -100);
                    if ($strict) {
                        throw $exception;
                    }
                    Log::error('zfy extension callback failed', ['hook' => $hook, 'exception' => $exception]);
                }
            }
        } finally {
            array_pop($this->stack);
        }

        return $value;
    }

    private function ordered(string $hook): array
    {
        $entries = array_values($this->listeners[$hook] ?? []);
        usort($entries, fn ($a, $b) => $a['priority'] <=> $b['priority']);

        return $entries;
    }
}
