<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PluginSetting extends Model
{
    protected $fillable = ['plugin_id', 'key', 'value'];

    protected $casts = ['value' => 'array'];
}
