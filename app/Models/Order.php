<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'order_no', 'type', 'status', 'pay_channel', 'total_amount',
        'paid_amount', 'paid_at', 'expires_at', 'buyer_snapshot', 'meta',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
        'buyer_snapshot' => 'array',
        'meta' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
