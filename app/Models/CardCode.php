<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class CardCode extends Model
{
    protected $fillable = [
        'product_id', 'product_variant_id', 'order_item_id', 'code_hash',
        'code_payload', 'status', 'delivered_at', 'metadata',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $hidden = ['code_payload'];

    protected function codePayload(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value === null) {
                    return null;
                }
                try {
                    return Crypt::decryptString($value);
                } catch (DecryptException $exception) {
                    // Legacy plain text is accepted only when its stored digest matches.
                    if (hash_equals((string) $this->code_hash, hash('sha256', $value))) {
                        return $value;
                    }
                    throw $exception;
                }
            },
            set: fn ($value) => $value === null ? null : Crypt::encryptString($value),
        );
    }
}
