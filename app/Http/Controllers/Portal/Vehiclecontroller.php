<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehiclePhoto;
use App\Services\BookingService;
use App\Models\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    public function __construct(private BookingService $bookings)
    {
    }

    public function index(Request $request)
    {
        $business = $this->business($request);

        $vehicles = $business->vehicles()
            ->with('vehicleType')
            ->withCount('photos')
            ->latest()
            ->get();

        return view('portal.vehicles.index', [
            'business' => $business,
            'vehicles' => $vehicles,
        ]);
    }

    public function create(Request $request)
    {
        return view('portal.vehicles.create', [
            'business' => $this->business($request),
            'vehicleTypes' => VehicleType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $business = $this->business($request);

        $validated = $request->validate([
            'vehicle_type_id' => 'required|uuid|exists:vehicle_types,id',
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:30',
            'year' => 'nullable|integer|min:1980|max:' . (date('Y') + 1),
            'condition_notes' => 'nullable|string',
            'mileage' => 'nullable|integer|min:0',
            'price_per_hour' => 'nullable|numeric|min:0',
            'price_per_day' => 'required|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0|max:100000',
            'photos' => 'nullable|array|max:' . VehiclePhoto::MAX_PER_VEHICLE,
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'photo_url' => 'nullable|url|max:2048',
            'pick_up_location' => 'nullable|string|max:255',
            'status' => 'required|in:available,rented,maintenance,retired',
        ]);

        $validated['deposit_amount'] = $validated['deposit_amount'] ?? 0;

        unset($validated['photos']);
        $pastedUrl = $validated['photo_url'] ?? null;
        unset($validated['photo_url']);

        $vehicle = $business->vehicles()->create($validated);

        $files = $request->file('photos', []);
        if ($files) {
            foreach ($files as $file) {
                VehiclePhoto::storeUpload($vehicle, $file);
            }
        } elseif ($pastedUrl) {
            $vehicle->photos()->create(['path' => $pastedUrl, 'sort_order' => 0]);
        }
        $vehicle->syncCover();

        return redirect()
            ->route('portal.vehicles.index')
            ->with('status', 'Vehicle added to your fleet.');
    }

    public function edit(Request $request, Vehicle $vehicle)
    {
        $business = $this->business($request);
        $this->ownVehicle($business, $vehicle);

        return view('portal.vehicles.edit', [
            'business' => $business,
            'vehicle' => $vehicle->loadCount('photos'),
            'vehicleTypes' => VehicleType::where('is_active', true)->orderBy('name')->get(),
            'activeBookings' => $this->bookings->activeBookingsFor($vehicle)->count(),
        ]);
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $business = $this->business($request);
        $this->ownVehicle($business, $vehicle);

        $validated = $request->validate([
            'vehicle_type_id' => 'required|uuid|exists:vehicle_types,id',
            'plate_number' => ['required', 'string', 'max:20', Rule::unique('vehicles', 'plate_number')->ignore($vehicle->id)],
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:30',
            'year' => 'nullable|integer|min:1980|max:' . (date('Y') + 1),
            'condition_notes' => 'nullable|string|max:2000',
            'mileage' => 'nullable|integer|min:0|max:2000000',
            'price_per_hour' => 'nullable|numeric|min:0|max:100000',
            'price_per_day' => 'required|numeric|min:0|max:100000',
            'deposit_amount' => 'nullable|numeric|min:0|max:100000',
            'pick_up_location' => 'nullable|string|max:255',
            'status' => 'required|in:available,rented,maintenance,retired',
        ]);

        $validated['deposit_amount'] = $validated['deposit_amount'] ?? 0;

        // Taking a vehicle out of service while customers still hold it would strand them.
        if ($validated['status'] !== 'available' && $validated['status'] !== $vehicle->status) {
            $active = $this->bookings->activeBookingsFor($vehicle)->count();

            if ($active > 0) {
                return back()->withInput()->withErrors([
                    'status' => "This vehicle still has {$active} active or upcoming booking" . ($active === 1 ? '' : 's')
                        . ". Complete or cancel " . ($active === 1 ? 'it' : 'them') . " in Bookings first, then change the status.",
                ]);
            }
        }

        $vehicle->update($validated);

        return redirect()
            ->route('portal.vehicles.index')
            ->with('status', 'Vehicle updated.');
    }

    /** A business may only edit its own vehicles. */
    private function ownVehicle($business, Vehicle $vehicle): void
    {
        abort_unless($vehicle->business_id === $business->id, 404);
    }

    /**
     * The business owned by the logged-in portal user.
     */
    private function business(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return $user->businesses()->firstOrFail();
    }
}