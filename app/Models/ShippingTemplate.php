<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingTemplate extends Model
{
    protected $fillable = ['name', 'base_fee', 'base_quantity', 'additional_fee', 'free_threshold', 'regions'];

    protected $casts = ['base_fee' => 'decimal:2', 'base_quantity' => 'integer', 'additional_fee' => 'decimal:2', 'free_threshold' => 'decimal:2', 'regions' => 'array'];
}
