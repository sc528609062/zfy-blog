<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $fillable = ['menu_id', 'parent_id', 'title', 'url', 'icon', 'sort_order', 'meta'];

    protected $casts = ['meta' => 'array'];
}
