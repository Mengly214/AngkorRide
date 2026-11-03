<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class VehiclePhoto extends Model
{
    /** Most photos one vehicle can have. */
    public const MAX_PER_VEHICLE = 8;

    protected $fillable = ['vehicle_id', 'path', 'sort_order'];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** Full URL for display (uploads are stored host-free as "/storage/..."). */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn () => str_starts_with($this->path, '/') ? url($this->path) : $this->path,
        );
    }

    /** True when the image is a file we stored (so it may be deleted from disk). */
    public function isUploaded(): bool
    {
        return str_starts_with((string) $this->path, '/storage/vehicles/');
    }

    /** Save an uploaded file and attach it to the vehicle as its next photo. */
    public static function storeUpload(Vehicle $vehicle, UploadedFile $file): self
    {
        $path = '/storage/' . $file->storePublicly('vehicles', 'public');

        return $vehicle->photos()->create([
            'path' => $path,
            'sort_order' => ((int) $vehicle->photos()->max('sort_order')) + 1,
        ]);
    }

    /** Delete the row and, for uploads, the file too. */
    public function deleteWithFile(): void
    {
        if ($this->isUploaded()) {
            Storage::disk('public')->delete(substr($this->path, strlen('/storage/')));
        }
        $this->delete();
    }
}