<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    protected $fillable = ['user_id', 'username', 'ip', 'ua', 'result', 'reason', 'attempted_at'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['attempted_at' => 'datetime'];
    }
}
