<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'content_id', 'title', 'slug', 'type', 'status', 'price', 'sale_price',
        'stock_strategy', 'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
}
