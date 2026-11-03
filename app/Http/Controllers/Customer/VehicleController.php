<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Vehicle;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VehicleController extends Controller
{
    public function __construct(private BookingService $bookings)
    {
    }

    /** Vehicle details + the booking form. */
    public function show(Request $request, Business $business, Vehicle $vehicle)
    {
        $this->guard($business, $vehicle);

        $vehicle->load(['vehicleType', 'photos']);

        return view('customer.vehicles.show', [
            'business' => $business,
            'vehicle' => $vehicle,
            'bookable' => $vehicle->status === 'available',
            'isSaved' => $request->user() !== null
                && $request->user()->favoriteVehicles()->whereKey($vehicle->id)->exists(),
            'maxDays' => BookingService::MAX_DAYS,
        ]);
    }

    /**
     * Live price + availability for the chosen dates (called by the page
     * while the customer picks dates). The final price is always recalculated
     * again on the server when the booking is submitted.
     */
    public function quote(Request $request, Business $business, Vehicle $vehicle): JsonResponse
    {
        $this->guard($business, $vehicle);

        try {
            [$start, $end] = $this->bookings->parseWindow(
                $request->query('pickup_date'),
                $request->query('pickup_time'),
                $request->query('return_date'),
                $request->query('return_time'),
            );
        } catch (ValidationException $e) {
            return response()->json([
                'ok' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ]);
        }

        if (! $this->bookings->isAvailable($vehicle, $start, $end)) {
            return response()->json([
                'ok' => false,
                'message' => 'Sorry, this vehicle is not available for those dates. Please try different dates.',
            ]);
        }

        $quote = $this->bookings->quote($vehicle, $start, $end, $request->query('voucher_code'));

        return response()->json([
            'ok' => true,
            'days' => $quote['days'],
            'rate' => $quote['rate'],
            'subtotal' => $quote['subtotal'],
            'discount' => $quote['discount'],
            'total' => $quote['total'],
            'deposit' => $quote['deposit'],
            'voucher' => $quote['voucher'],
            'voucher_ok' => $quote['voucher_ok'],
            'voucher_message' => $quote['voucher_message'],
            'pickup' => $start->format('D, M j · g:i A'),
            'return' => $end->format('D, M j · g:i A'),
        ]);
    }

    /** Heart button: save / un-save a vehicle (signed-in customers only). */
    public function favorite(Request $request, Business $business, Vehicle $vehicle)
    {
        $this->guard($business, $vehicle);

        $changes = $request->user()->favoriteVehicles()->toggle($vehicle->id);
        $saved = count($changes['attached']) > 0;

        return back()->with('status', $saved ? 'Saved to your favourites.' : 'Removed from your favourites.');
    }

    /** Only vehicles of this business, whose business is public, can be opened. */
    private function guard(Business $business, Vehicle $vehicle): void
    {
        abort_unless($business->isListed(), 404);
        abort_unless($vehicle->business_id === $business->id, 404);
        abort_if($vehicle->status === 'retired', 404);
    }
}