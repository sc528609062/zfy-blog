<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    protected $fillable = [
        'order_id', 'order_item_id', 'user_id', 'status', 'amount',
        'reason', 'handled_at', 'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'handled_at' => 'datetime',
        'metadata' => 'array',
    ];
}
