<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardCode extends Model
{
    protected $fillable = [
        'product_id', 'product_variant_id', 'order_item_id', 'code_hash',
        'code_payload', 'status', 'delivered_at', 'metadata',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'metadata' => 'array',
    ];
}
