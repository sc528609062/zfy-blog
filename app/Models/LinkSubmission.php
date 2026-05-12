<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LinkSubmission extends Model
{
    protected $fillable = [
        'link_category_id', 'user_id', 'name', 'url', 'email', 'description',
        'status', 'ip_address', 'reviewed_at', 'metadata',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'metadata' => 'array',
    ];
}
