<?php

namespace App\Models\Concerns;

trait HasJsonMeta
{
    public function metaValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->meta ?? [], $key, $default);
    }
}
