<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code', 'type', 'amount', 'usage_limit', 'used_count', 'status',
        'starts_at', 'expires_at', 'rules',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'rules' => 'array',
    ];
}
