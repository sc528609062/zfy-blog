<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LinkCategory extends Model
{
    protected $fillable = ['parent_id', 'name', 'slug', 'description', 'sort_order', 'metadata'];

    protected $casts = ['metadata' => 'array'];

    public function links()
    {
        return $this->hasMany(Link::class);
    }
}
