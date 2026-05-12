<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserVip extends Model
{
    protected $fillable = ['user_id', 'vip_level_id', 'started_at', 'expires_at', 'meta'];

    protected $casts = ['started_at' => 'datetime', 'expires_at' => 'datetime', 'meta' => 'array'];

    public function level()
    {
        return $this->belongsTo(VipLevel::class, 'vip_level_id');
    }
}
