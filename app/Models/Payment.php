<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id', 'gateway', 'status', 'trade_no', 'amount',
        'request_payload', 'response_payload', 'paid_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'paid_at' => 'datetime',
    ];
}
