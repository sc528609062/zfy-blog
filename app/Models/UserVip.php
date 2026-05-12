<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserVip extends Model
{
    protected $fillable = [
        'user_id', 'vip_level_id', 'starts_at', 'expires_at',
        'source', 'source_order_id', 'active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at'  => 'datetime',
            'expires_at' => 'datetime',
            'active'     => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(VipLevel::class, 'vip_level_id');
    }
}
