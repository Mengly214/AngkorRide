<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class VehicleType extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function booted()
    {
        static::creating(function ($type) {
            if (!$type->id) {
                $type->id = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'name',
        'description',
        'passenger_capacity',
        'requires_license',
        'icon_url',
        'is_active',
    ];

    protected $casts = [
        'passenger_capacity' => 'integer',
        'requires_license' => 'boolean',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}