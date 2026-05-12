<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VipLevel extends Model
{
    protected $fillable = [
        'name', 'slug', 'level', 'duration_days', 'price',
        'discount_rate', 'discount_amount', 'download_limit_daily',
        'benefits', 'enabled', 'sort_order', 'color', 'icon',
    ];

    protected function casts(): array
    {
        return [
            'benefits'        => 'array',
            'enabled'         => 'boolean',
            'price'           => 'decimal:2',
            'discount_rate'   => 'decimal:4',
            'discount_amount' => 'decimal:2',
        ];
    }

    public function userVips(): HasMany
    {
        return $this->hasMany(UserVip::class);
    }
}
