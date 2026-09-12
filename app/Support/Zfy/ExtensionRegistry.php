<?php

namespace App\Support\Zfy;

use InvalidArgumentException;

class ExtensionRegistry
{
    private array $definitions = [];

    public function register(string $kind, string $key, array $definition): void
    {
        if (! in_array($kind, ['content_type', 'shortcode', 'block', 'widget', 'payment', 'api'], true) || ! preg_match('/^[a-z][a-z0-9_-]{0,79}$/', $key)) {
            throw new InvalidArgumentException('Invalid extension registration.');
        }
        if (isset($this->definitions[$kind][$key])) {
            throw new InvalidArgumentException('Extension key already registered: '.$key);
        }
        $this->definitions[$kind][$key] = $definition;
        if ($kind === 'content_type') {
            config(['zfy.content_types' => array_values(array_unique([...config('zfy.content_types', []), $key]))]);
        }
    }

    public function get(string $kind, string $key): ?array
    {
        return $this->definitions[$kind][$key] ?? null;
    }

    public function all(string $kind): array
    {
        return $this->definitions[$kind] ?? [];
    }
}
