<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'item_type', 'item_id', 'title', 'quantity', 'unit_price', 'meta'];

    protected $casts = ['meta' => 'array'];
}
