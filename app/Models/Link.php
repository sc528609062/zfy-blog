<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Link extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'link_category_id', 'submitted_by', 'name', 'url', 'status', 'target',
        'nofollow', 'rating', 'sort_order', 'checked_at', 'metadata',
    ];

    protected $casts = [
        'nofollow' => 'boolean',
        'checked_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(LinkCategory::class, 'link_category_id');
    }
}
