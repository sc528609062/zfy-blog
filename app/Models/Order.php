<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_no', 'user_id', 'type', 'status', 'amount', 'paid_amount',
        'wallet_used', 'points_used', 'currency', 'gateway',
        'gateway_trade_no', 'subject', 'meta', 'snapshot',
        'paid_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'      => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'wallet_used' => 'decimal:2',
            'meta'        => 'array',
            'snapshot'    => 'array',
            'paid_at'     => 'datetime',
            'expires_at'  => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
