<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Voucher extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected static function booted()
    {
        static::creating(function ($voucher) {
            if (! $voucher->id) {
                $voucher->id = (string) Str::uuid();
            }
            $voucher->code = Str::upper(trim($voucher->code));
        });
    }

    protected $fillable = [
        'business_id', 'code', 'description', 'type', 'value',
        'min_subtotal', 'max_uses', 'used_count', 'starts_at', 'expires_at', 'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_subtotal' => 'decimal:2',
        'max_uses' => 'integer',
        'used_count' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}