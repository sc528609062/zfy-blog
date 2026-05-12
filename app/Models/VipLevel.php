<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VipLevel extends Model
{
    protected $fillable = ['name', 'slug', 'level', 'price_monthly', 'price_yearly', 'discount_percent', 'fixed_discount', 'benefits'];

    protected $casts = ['benefits' => 'array'];
}
