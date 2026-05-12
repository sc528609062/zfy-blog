<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockItem extends Model
{
    protected $fillable = ['product_id', 'product_variant_id', 'type', 'quantity', 'reserved', 'metadata'];

    protected $casts = ['metadata' => 'array'];
}
