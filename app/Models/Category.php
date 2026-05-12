<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = [
        'parent_id', 'name', 'slug', 'description', 'icon', 'cover',
        'sort_order', 'content_count', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public static function booted(): void
    {
        static::creating(function (self $cat) {
            if (! $cat->slug) {
                $cat->slug = Str::slug($cat->name) ?: 'c-' . Str::lower(Str::random(6));
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }
}
