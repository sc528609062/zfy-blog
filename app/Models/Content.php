<?php

namespace App\Models;

use App\Models\Concerns\HasJsonMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Content extends Model
{
    public function toArray(): array
    {
        $data = parent::toArray();
        unset($data['access_rules']['password_hash']);

        return $data;
    }

    use HasJsonMeta, Searchable, SoftDeletes;

    protected $fillable = [
        'author_id', 'category_id', 'type', 'status', 'title', 'slug', 'subtitle',
        'excerpt', 'cover_url', 'block_json', 'rendered_html', 'markdown_cache',
        'seo', 'pricing', 'access_rules', 'view_count', 'comment_count',
        'like_count', 'download_count', 'published_at',
    ];

    protected $hidden = ['markdown_cache', 'rendered_html', 'block_json'];

    protected $casts = [
        'block_json' => 'array',
        'seo' => 'array',
        'pricing' => 'array',
        'access_rules' => 'array',
        'published_at' => 'datetime',
    ];

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->where(function ($query) {
            $query->whereNull('published_at')->orWhere('published_at', '<=', now());
        });
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function shouldBeSearchable(): bool
    {
        return ! $this->trashed() && $this->status === 'published' && (! $this->published_at || ! $this->published_at->isFuture());
    }

    public function scopeMatchingPublicText($query, string $keyword)
    {
        $keyword = mb_substr(trim($keyword), 0, 120);

        return $query->where(function ($query) use ($keyword) {
            $query->where('title', 'like', '%'.$keyword.'%')->orWhere('excerpt', 'like', '%'.$keyword.'%');
            if ($this->getConnection()->getDriverName() === 'mysql' && preg_match('/[a-zA-Z]{3,}/', $keyword)) {
                $query->orWhereFullText(['title', 'excerpt'], $keyword);
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function topics()
    {
        return $this->belongsToMany(Topic::class);
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

    public function revisions()
    {
        return $this->hasMany(ContentRevision::class);
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
