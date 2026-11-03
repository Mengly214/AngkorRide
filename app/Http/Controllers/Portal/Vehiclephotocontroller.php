<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehiclePhoto;
use Illuminate\Http\Request;

class VehiclePhotoController extends Controller
{
    /** Photo manager for one vehicle. */
    public function index(Request $request, Vehicle $vehicle)
    {
        $business = $this->own($request, $vehicle);

        return view('portal.vehicles.photos', [
            'business' => $business,
            'vehicle' => $vehicle->load('photos'),
            'max' => VehiclePhoto::MAX_PER_VEHICLE,
        ]);
    }

    /** Add one or more photos. */
    public function store(Request $request, Vehicle $vehicle)
    {
        $this->own($request, $vehicle);

        $request->validate([
            'photos' => 'required|array|min:1|max:' . VehiclePhoto::MAX_PER_VEHICLE,
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'photos.required' => 'Please choose at least one photo.',
            'photos.*.image' => 'Please choose JPG, PNG or WebP images only.',
            'photos.*.mimes' => 'Please choose JPG, PNG or WebP images only.',
            'photos.*.max' => 'One of the images is too large (maximum 5 MB each).',
            'photos.*.uploaded' => 'The upload failed. An image may be too large for the server.',
        ]);

        $files = $request->file('photos', []);
        $room = VehiclePhoto::MAX_PER_VEHICLE - $vehicle->photos()->count();

        if (count($files) > $room) {
            return back()->withErrors([
                'photos' => "A vehicle can have at most " . VehiclePhoto::MAX_PER_VEHICLE . " photos. You can add {$room} more.",
            ]);
        }

        foreach ($files as $file) {
            VehiclePhoto::storeUpload($vehicle, $file);
        }
        $vehicle->syncCover();

        return back()->with('status', count($files) === 1 ? 'Photo added.' : count($files) . ' photos added.');
    }

    /** Make this photo the first one (the cover shown on cards). */
    public function cover(Request $request, Vehicle $vehicle, VehiclePhoto $photo)
    {
        $this->own($request, $vehicle);
        abort_unless($photo->vehicle_id === $vehicle->id, 404);

        $order = $vehicle->photos()->where('id', '!=', $photo->id)->pluck('id')->prepend($photo->id);
        foreach ($order as $i => $id) {
            VehiclePhoto::where('id', $id)->update(['sort_order' => $i]);
        }
        $vehicle->syncCover();

        return back()->with('status', 'Cover photo updated.');
    }

    public function destroy(Request $request, Vehicle $vehicle, VehiclePhoto $photo)
    {
        $this->own($request, $vehicle);
        abort_unless($photo->vehicle_id === $vehicle->id, 404);

        $photo->deleteWithFile();
        $vehicle->syncCover();

        return back()->with('status', 'Photo removed.');
    }

    /** A business may only touch its own vehicles. */
    private function own(Request $request, Vehicle $vehicle)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $business = $user->businesses()->firstOrFail();

        abort_unless($vehicle->business_id === $business->id, 404);

        return $business;
    }
}