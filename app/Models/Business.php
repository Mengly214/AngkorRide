<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Business extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'owner_user_id',
        'name',
        'slug',
        'description',
        'registration_no',
        'address',
        'city',
        'latitude',
        'longitude',
        'phone',
        'email',
        'logo_url',
        'opening_time',
        'closing_time',
        'is_verified',
        'is_active',
        'admin_status',
        'admin_note',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'latitude' => 'decimal:6',
        'longitude' => 'decimal:6',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'reviewed_at' => 'datetime',
        'opening_time' => 'datetime:H:i',
        'closing_time' => 'datetime:H:i',
    ];

    protected static function booted()
    {
        static::creating(function ($business) {
            if (!$business->id) {
                $business->id = (string) Str::uuid();
            }

            if (!$business->slug && $business->name) {
                $business->slug = static::uniqueSlugFrom($business->name);
            }
        });
    }

    protected static function uniqueSlugFrom(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-" . ++$i;
        }

        return $slug;
    }

    /**
     * Uploaded logos are stored host-free ("/storage/businesses/x.jpg");
     * pasted links stay as they are. Always return a full URL.
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value && str_starts_with($value, '/') ? url($value) : $value,
        );
    }

    /** Delete the logo file from disk when we stored it ourselves. */
    public function deleteLogoFile(): void
    {
        $raw = (string) $this->getRawOriginal('logo_url');

        if (str_starts_with($raw, '/storage/businesses/')) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete(substr($raw, strlen('/storage/')));
        }
    }

    /** Shops that customers may see: switched on by the owner and not rejected or suspended by an admin. */
    public function scopeListed($query)
    {
        return $query->where('businesses.is_active', true)
            ->whereIn('businesses.admin_status', ['pending', 'approved']);
    }

    public function isListed(): bool
    {
        return $this->is_active && in_array($this->admin_status, ['pending', 'approved'], true);
    }

    /**
     * Use the slug for route model binding, e.g. /business/{business}.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}