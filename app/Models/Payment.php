<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** One attempt to pay a booking online. A booking can have several (e.g. a failed try, then a good one). */
class Payment extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    public const STATUSES = ['pending', 'paid', 'failed', 'expired', 'refund_due', 'refunded', 'review'];

    protected $fillable = [
        'booking_id', 'business_id', 'provider', 'method', 'tran_id', 'amount', 'currency',
        'status', 'provider_ref', 'note', 'meta', 'paid_at', 'refunded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta' => 'array',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($p) {
            if (! $p->id) {
                $p->id = (string) Str::uuid();
            }
            if (! $p->tran_id) {
                $p->tran_id = 'ARP' . strtoupper(Str::random(12));
            }
        });
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function label(): string
    {
        return match ($this->status) {
            'pending' => 'Waiting for payment',
            'paid' => 'Paid',
            'failed' => 'Failed',
            'expired' => 'Expired',
            'refund_due' => 'Refund due',
            'refunded' => 'Refunded',
            'review' => 'Needs review',
            default => ucfirst($this->status),
        };
    }
}