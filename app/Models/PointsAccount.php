<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointsAccount extends Model
{
    protected $fillable = ['user_id', 'points', 'frozen_points'];
}
