<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentAccessRule extends Model
{
    protected $fillable = ['content_id', 'rule_type', 'config', 'enabled'];

    protected function casts(): array
    {
        return [
            'config'  => 'array',
            'enabled' => 'boolean',
        ];
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }
}
