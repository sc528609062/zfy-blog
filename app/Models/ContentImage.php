<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentImage extends Model
{
    protected $fillable = [
        'content_id', 'media_id', 'url', 'title', 'caption', 'alt',
        'width', 'height', 'sort_order', 'allow_original_download',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }
}
