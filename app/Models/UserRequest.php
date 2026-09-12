<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRequest extends Model
{
    protected $fillable = ['user_id', 'content_id', 'type', 'status', 'body', 'reply', 'handled_at'];

    protected $casts = ['handled_at' => 'datetime'];
}
