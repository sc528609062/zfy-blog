<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemVersion extends Model
{
    protected $fillable = ['version', 'status', 'meta'];

    protected $casts = ['meta' => 'array'];
}
