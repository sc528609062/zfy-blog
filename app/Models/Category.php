<?php

namespace App\Models;

use App\Models\Concerns\HasJsonMeta;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasJsonMeta;

    protected $fillable = ['parent_id', 'name', 'slug', 'type', 'icon', 'description', 'sort_order', 'meta'];

    protected $casts = ['meta' => 'array'];

    public function contents()
    {
        return $this->hasMany(Content::class);
    }
}
