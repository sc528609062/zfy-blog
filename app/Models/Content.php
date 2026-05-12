<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * 统一内容模型：post / images / files / page。
 *
 * - block_json 是编辑器权威源（TipTap）。
 * - rendered_html 是缓存的服务端渲染结果，给前台展示用。
 * - markdown_cache 是 Markdown 模式的缓存。
 * - plain_text 用于全文搜索和摘要回退。
 */
class Content extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_POST   = 'post';
    public const TYPE_IMAGES = 'images';
    public const TYPE_FILES  = 'files';
    public const TYPE_PAGE   = 'page';

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_PENDING   = 'pending';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_PRIVATE   = 'private';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_REJECTED  = 'rejected';
    public const STATUS_TRASH     = 'trash';

    public const VISIBILITY_PUBLIC    = 'public';
    public const VISIBILITY_LOGGED_IN = 'logged_in';
    public const VISIBILITY_VIP       = 'vip';
    public const VISIBILITY_PAID      = 'paid';
    public const VISIBILITY_COMMENTED = 'commented';
    public const VISIBILITY_PASSWORD  = 'password';
    public const VISIBILITY_POINTS    = 'points';

    protected $fillable = [
        'type', 'status', 'title', 'slug', 'excerpt',
        'cover_media_id', 'cover_url', 'author_id', 'editor_id', 'category_id',
        'block_json', 'rendered_html', 'markdown_cache', 'plain_text',
        'visibility', 'access_password', 'price', 'points_price',
        'vip_discount_type', 'vip_discount_value', 'required_vip_level_id',
        'comment_policy', 'download_policy',
        'view_count', 'like_count', 'comment_count', 'favorite_count', 'download_count',
        'seo_title', 'seo_description', 'seo_keywords',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'block_json'         => 'array',
            'price'              => 'decimal:2',
            'vip_discount_value' => 'decimal:4',
            'published_at'       => 'datetime',
        ];
    }

    public static function booted(): void
    {
        static::creating(function (self $content) {
            if (! $content->slug) {
                $content->slug = static::makeUniqueSlug($content->title ?: 'content');
            }
            if (! $content->plain_text && $content->rendered_html) {
                $content->plain_text = static::stripHtml($content->rendered_html);
            }
        });

        static::saving(function (self $content) {
            if ($content->isDirty('rendered_html')) {
                $content->plain_text = static::stripHtml($content->rendered_html ?? '');
            }
        });
    }

    public static function makeUniqueSlug(string $base): string
    {
        $slug = Str::slug($base);
        if ($slug === '') {
            $slug = 'c-' . Str::lower(Str::random(8));
        }
        $original = $slug;
        $i = 2;
        while (static::where('slug', $slug)->exists()) {
            $slug = $original . '-' . $i++;
        }
        return $slug;
    }

    protected static function stripHtml(string $html): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags($html)));
    }

    // === 关系 ===
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'content_tag');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ContentImage::class)->orderBy('sort_order');
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(ContentDownload::class)->orderBy('sort_order');
    }

    public function accessRules(): HasMany
    {
        return $this->hasMany(ContentAccessRule::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(ContentPurchase::class);
    }

    // === Scopes ===
    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_PUBLISHED)
                 ->where(function ($q) {
                     $q->whereNull('published_at')->orWhere('published_at', '<=', now());
                 });
    }

    public function scopeOfType(Builder $q, string $type): Builder
    {
        return $q->where('type', $type);
    }

    public function scopeFeed(Builder $q): Builder
    {
        $types = array_keys(array_filter(config('zfy.content_types'), fn ($c) => $c['feed'] ?? false));
        return $q->whereIn('type', $types);
    }

    // === Helpers ===
    public function isPaid(): bool
    {
        return $this->price > 0 || $this->points_price > 0
            || in_array($this->visibility, [self::VISIBILITY_PAID, self::VISIBILITY_POINTS], true);
    }

    public function isVipOnly(): bool
    {
        return $this->visibility === self::VISIBILITY_VIP || $this->required_vip_level_id;
    }

    public function getUrlAttribute(): string
    {
        return $this->type === self::TYPE_PAGE
            ? route('page.show', $this->slug)
            : route('content.show', $this->slug);
    }

    public function getTypeMetaAttribute(): array
    {
        return config('zfy.content_types.' . $this->type, []);
    }

    public function getCoverImageAttribute(): ?string
    {
        if ($this->cover_url) {
            return $this->cover_url;
        }

        $first = $this->images->first();
        return $first?->url;
    }
}
