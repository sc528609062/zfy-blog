<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = ['folder_id', 'user_id', 'disk', 'type', 'name', 'path', 'mime', 'size', 'metadata'];

    protected $casts = ['metadata' => 'array'];
}
