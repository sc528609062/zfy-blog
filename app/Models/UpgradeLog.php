<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UpgradeLog extends Model
{
    protected $fillable = ['from_version', 'to_version', 'status', 'log'];
}
