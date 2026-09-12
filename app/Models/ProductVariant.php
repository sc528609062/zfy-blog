<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'sku', 'title', 'price', 'stock', 'status', 'attributes'];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'reserved' => 'integer',
        'attributes' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
