<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointsStoreItem extends Model
{
    protected $fillable = ['title', 'slug', 'points_price', 'stock', 'status', 'meta'];

    protected $casts = ['meta' => 'array'];
}
