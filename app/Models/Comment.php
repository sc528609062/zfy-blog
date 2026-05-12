<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = ['content_id', 'user_id', 'parent_id', 'status', 'body', 'ip_address', 'meta'];

    protected $casts = ['meta' => 'array'];
}
