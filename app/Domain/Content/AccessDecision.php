<?php

namespace App\Domain\Content;

/**
 * 访问决策值对象。
 */
class AccessDecision
{
    public function __construct(
        public bool $allowed,
        public ?string $reason = null,
        public ?string $lock = null,
        public bool $preview = false,
    ) {}

    public static function allow(): self
    {
        return new self(allowed: true);
    }

    public static function deny(string $reason, ?string $lock = null, bool $preview = false): self
    {
        return new self(allowed: false, reason: $reason, lock: $lock, preview: $preview);
    }
}
