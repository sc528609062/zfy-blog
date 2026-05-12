<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $fillable = [
        'order_id', 'status', 'carrier', 'tracking_no', 'address_snapshot',
        'shipped_at', 'received_at', 'metadata',
    ];

    protected $casts = [
        'address_snapshot' => 'array',
        'shipped_at' => 'datetime',
        'received_at' => 'datetime',
        'metadata' => 'array',
    ];
}
