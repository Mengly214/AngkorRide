<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Vehicle extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function booted()
    {
        static::creating(function ($vehicle) {
            if (!$vehicle->id) {
                $vehicle->id = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'business_id',
        'vehicle_type_id',
        'plate_number',
        'brand',
        'model',
        'color',
        'year',
        'condition_notes',
        'mileage',
        'price_per_hour',
        'price_per_day',
        'deposit_amount',
        'photo_url',
        'pick_up_location',
        'status',
    ];

    protected $casts = [
        'year' => 'integer',
        'mileage' => 'integer',
        'price_per_hour' => 'decimal:2',
        'price_per_day' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Uploaded photos are stored as "/storage/vehicles/xyz.jpg" (works on any
     * host); pasted links are stored as full URLs. Always return a full URL.
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value && str_starts_with($value, '/') ? url($value) : $value,
        );
    }

    public function photos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VehiclePhoto::class)->orderBy('sort_order')->orderBy('id');
    }

    public function blocks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VehicleBlock::class)->orderBy('start_datetime');
    }

    /** Keep vehicles.photo_url pointing at the first photo (used by cards and lists). */
    public function syncCover(): void
    {
        $first = $this->photos()->first();
        $this->forceFill(['photo_url' => $first?->path])->save();
    }

    /** All photo URLs for the details-page gallery (falls back to the cover). */
    public function galleryUrls(): array
    {
        $urls = $this->photos->map(fn ($p) => $p->url)->all();

        return $urls ?: array_values(array_filter([$this->photo_url]));
    }
}