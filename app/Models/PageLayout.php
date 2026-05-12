<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageLayout extends Model
{
    protected $fillable = ['scope', 'theme_id', 'title', 'schema', 'status'];

    protected $casts = ['schema' => 'array'];
}
