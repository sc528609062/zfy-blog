<?php

namespace App\Models;

use App\Models\Concerns\HasJsonMeta;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasJsonMeta;

    protected $fillable = ['name', 'slug', 'color', 'meta'];

    protected $casts = ['meta' => 'array'];

    public function contents()
    {
        return $this->belongsToMany(Content::class);
    }
}
