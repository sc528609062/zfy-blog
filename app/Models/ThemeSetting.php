<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThemeSetting extends Model
{
    protected $fillable = ['theme_id', 'scope', 'key', 'value'];

    protected $casts = ['value' => 'array'];
}
