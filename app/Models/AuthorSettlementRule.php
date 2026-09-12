<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthorSettlementRule extends Model
{
    protected $fillable = ['name', 'share_percent', 'scope', 'target_id', 'hold_days', 'is_default', 'conditions'];

    protected $casts = ['share_percent' => 'decimal:2', 'hold_days' => 'integer', 'is_default' => 'boolean', 'conditions' => 'array'];
}
