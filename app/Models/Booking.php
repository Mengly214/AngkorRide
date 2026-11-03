<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Vehicle;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function booted()
    {
        static::creating(function ($booking) {
            if (!$booking->id) {
                $booking->id = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'reference',
        'customer_id',
        'vehicle_id',
        'business_id',
        'start_datetime',
        'end_datetime',
        'pickup_location',
        'return_location',
        'status',
        'rate_type',
        'rate_amount',
        'total_price',
        'deposit_amount',
        'deposit_refunded',
        'payment_status',
        'payment_method',
        'customer_notes',
        'confirmed_at',
        'picked_up_at',
        'returned_at',
        'cancelled_at',
        'cancellation_reason',
        'customer_email',
        'voucher_code',
        'discount_amount',
        'expires_at',
        'cancelled_by',
        'terms_accepted_at',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',

        'rate_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'deposit_amount' => 'decimal:2',

        'deposit_refunded' => 'boolean',

        'confirmed_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'returned_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expires_at' => 'datetime',
        'terms_accepted_at' => 'datetime',
        'discount_amount' => 'decimal:2',
    ];

    /**
     * What the customer sees: Pending / Confirmed / Rejected / Cancelled / Expired
     * (plus Ongoing / Completed). Rejected, cancelled and expired are all stored
     * as status "cancelled" and told apart by cancelled_by + confirmed_at.
     */
    public function displayStatus(): string
    {
        if ($this->status !== 'cancelled') {
            return ucfirst($this->status);
        }

        return match (true) {
            $this->cancelled_by === 'system' => 'Expired',
            $this->cancelled_by === 'business' && $this->confirmed_at === null => 'Rejected',
            default => 'Cancelled',
        };
    }

    /** Subtotal before any voucher: total_price + discount. */
    public function subtotal(): float
    {
        return round((float) $this->total_price + (float) $this->discount_amount, 2);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'booking_id');
    }
}