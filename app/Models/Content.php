<?php

namespace App\Models;

use App\Models\Concerns\HasJsonMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Content extends Model
{
    use HasJsonMeta, Searchable, SoftDeletes;

    protected $fillable = [
        'author_id', 'category_id', 'type', 'status', 'title', 'slug', 'subtitle',
        'excerpt', 'cover_url', 'block_json', 'rendered_html', 'markdown_cache',
        'seo', 'pricing', 'access_rules', 'view_count', 'comment_count',
        'like_count', 'download_count', 'published_at',
    ];

    protected $casts = [
        'block_json' => 'array',
        'seo' => 'array',
        'pricing' => 'array',
        'access_rules' => 'array',
        'published_at' => 'datetime',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'status' => $this->status,
            'published_at' => optional($this->published_at)->toISOString(),
        ];
    }
}
