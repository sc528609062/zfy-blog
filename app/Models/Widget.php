<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Widget extends Model
{
    protected $fillable = ['region', 'type', 'title', 'config', 'sort_order', 'enabled'];

    protected $casts = ['config' => 'array', 'enabled' => 'boolean'];
}
