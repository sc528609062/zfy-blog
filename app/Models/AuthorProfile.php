<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthorProfile extends Model
{
    protected $fillable = [
        'user_id', 'display_name', 'contact', 'direction', 'payout_info',
        'apply_reason', 'status', 'approved_at', 'approved_by', 'reject_reason',
        'settlement_rate', 'skip_review',
    ];

    protected function casts(): array
    {
        return [
            'payout_info'     => 'array',
            'approved_at'     => 'datetime',
            'settlement_rate' => 'decimal:4',
            'skip_review'     => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
