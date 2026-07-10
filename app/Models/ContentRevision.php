<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentRevision extends Model
{
    protected $fillable = [
        'content_id',
        'user_id',
        'kind',
        'draft_key',
        'snapshot',
        'source_updated_at',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'source_updated_at' => 'datetime',
    ];

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
