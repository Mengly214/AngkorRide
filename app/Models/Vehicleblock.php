<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A run of whole days when a vehicle cannot be rented (maintenance, private use...).
 */
class VehicleBlock extends Model
{
    public const REASONS = [
        'maintenance' => 'Maintenance',
        'private' => 'Private use',
        'other' => 'Other',
    ];

    protected $fillable = ['vehicle_id', 'start_datetime', 'end_datetime', 'reason', 'note'];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? 'Blocked';
    }

    /** Last blocked day (end_datetime is the morning after it). */
    public function lastDay(): \Illuminate\Support\Carbon
    {
        return $this->end_datetime->copy()->subSecond()->startOfDay();
    }
}