<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentDownload extends Model
{
    protected $fillable = [
        'content_id', 'label', 'platform', 'url', 'extract_code', 'unzip_password',
        'version', 'size_bytes', 'note', 'sort_order', 'enabled',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'size_bytes' => 'integer',
        ];
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function getSizeHumanAttribute(): ?string
    {
        if (! $this->size_bytes) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = (float) $this->size_bytes;
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return number_format($bytes, $bytes >= 100 ? 0 : 2) . ' ' . $units[$i];
    }
}
