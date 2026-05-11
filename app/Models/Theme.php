<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    protected $fillable = [
        'name', 'slug', 'version', 'author', 'compatible', 'entry_view',
        'preview', 'menus', 'regions', 'settings_schema', 'is_active',
    ];

    protected $casts = [
        'menus' => 'array',
        'regions' => 'array',
        'settings_schema' => 'array',
        'is_active' => 'boolean',
    ];

    public function settings()
    {
        return $this->hasMany(ThemeSetting::class);
    }
}
