<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InviteCode extends Model
{
    protected $fillable = ['code', 'usage_limit', 'status', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime'];
}
