<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plugin extends Model
{
    protected $fillable = ['name', 'slug', 'version', 'provider', 'permissions', 'events', 'enabled'];

    protected $casts = ['permissions' => 'array', 'events' => 'array', 'enabled' => 'boolean'];

    public function settings()
    {
        return $this->hasMany(PluginSetting::class);
    }
}
