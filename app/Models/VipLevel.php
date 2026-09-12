<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VipLevel extends Model
{
    protected $fillable = ['name', 'slug', 'level', 'price_monthly', 'price_yearly', 'price_lifetime', 'discount_percent', 'fixed_discount', 'benefits'];

    protected $casts = ['benefits' => 'array', 'price_monthly' => 'decimal:2', 'price_yearly' => 'decimal:2', 'price_lifetime' => 'decimal:2', 'fixed_discount' => 'decimal:2'];
}
