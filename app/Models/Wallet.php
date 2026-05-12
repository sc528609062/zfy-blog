<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wallet extends Model
{
    protected $fillable = [
        'user_id', 'balance', 'frozen', 'total_recharged', 'total_spent',
    ];

    protected function casts(): array
    {
        return [
            'balance'         => 'decimal:2',
            'frozen'          => 'decimal:2',
            'total_recharged' => 'decimal:2',
            'total_spent'     => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
