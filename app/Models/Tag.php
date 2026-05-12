<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'content_count'];

    public static function booted(): void
    {
        static::creating(function (self $tag) {
            if (! $tag->slug) {
                $tag->slug = Str::slug($tag->name) ?: 't-' . Str::lower(Str::random(6));
            }
        });
    }

    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'content_tag');
    }
}
