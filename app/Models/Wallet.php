<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = ['user_id', 'balance', 'frozen_balance'];

    protected $casts = ['balance' => 'decimal:2', 'frozen_balance' => 'decimal:2'];
}
