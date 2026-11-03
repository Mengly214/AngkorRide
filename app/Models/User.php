<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'password_hash',
        'role',
        'profile_photo_url',
        'is_verified',
        'is_active',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($user) {
            if (!$user->id) {
                $user->id = (string) Str::uuid();
            }
        });
    }

    /**
     * The users table stores the hash in `password_hash`, not the
     * Laravel-default `password` column — point auth at the real column.
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function getAuthPasswordName()
    {
        return 'password_hash';
    }

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class, 'owner_user_id');
    }

    /** Vehicles this customer has saved (heart button). */
    public function favoriteVehicles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Vehicle::class, 'vehicle_favorites')->withTimestamps();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'customer_id');
    }
}