<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FriendLink extends Model
{
    protected $fillable = [
        'name', 'url', 'logo', 'description', 'category',
        'enabled', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }
}
