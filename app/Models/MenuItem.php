<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    protected $fillable = [
        'menu_id', 'parent_id', 'label', 'icon',
        'target_type', 'target_ref', 'open_in', 'visibility',
        'sort_order', 'enabled',
    ];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function resolveUrl(): string
    {
        return match ($this->target_type) {
            'url'      => $this->target_ref ?? '#',
            'route'    => $this->target_ref ? (function () {
                try { return route($this->target_ref); } catch (\Throwable) { return '#'; }
            })() : '#',
            'content'  => $this->target_ref ? "/content/{$this->target_ref}" : '#',
            'page'     => $this->target_ref ? "/p/{$this->target_ref}" : '#',
            'category' => $this->target_ref ? "/c/{$this->target_ref}" : '#',
            'tag'      => $this->target_ref ? "/tag/{$this->target_ref}" : '#',
            default    => '#',
        };
    }
}
