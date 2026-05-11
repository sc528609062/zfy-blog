<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $fillable = ['content_id', 'media_id', 'role', 'meta'];

    protected $casts = ['meta' => 'array'];
}
