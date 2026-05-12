<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LinkCheck extends Model
{
    protected $fillable = ['link_id', 'status', 'http_code', 'message', 'checked_at', 'metadata'];

    protected $casts = [
        'checked_at' => 'datetime',
        'metadata' => 'array',
    ];
}
