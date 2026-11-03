<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Vehicle;
use App\Services\BookingService;
use App\Services\BookingWorkflow;
use App\Services\Payments\PaymentGateways;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookings, private BookingWorkflow $workflow)
    {
    }

    /**
     * Review the rental, enter details, apply a voucher, confirm.
     * Open to everyone: a visitor without an account creates one right here.
     */
    public function checkout(Request $request, Business $business, Vehicle $vehicle)
    {
        $this->guard($business, $vehicle);

        $details = route('vehicles.show', ['business' => $business, 'vehicle' => $vehicle]
            + $request->only(['pickup_date', 'pickup_time', 'return_date', 'return_time']));

        try {
            [$start, $end] = $this->bookings->parseWindow(
                $request->query('pickup_date'), $request->query('pickup_time'),
                $request->query('return_date'), $request->query('return_time'),
            );
        } catch (ValidationException $e) {
            return redirect($details)->withErrors($e->errors());
        }

        if (! $this->bookings->isAvailable($vehicle, $start, $end)) {
            return redirect($details)->withErrors([
                'pickup_date' => 'Sorry, this vehicle is not available for those dates. Please try different dates.',
            ]);
        }

        $account = $request->user();

        return view('customer.bookings.checkout', [
            'business' => $business,
            'vehicle' => $vehicle->load('vehicleType'),
            'start' => $start,
            'end' => $end,
            'quote' => $this->bookings->quote($vehicle, $start, $end, $request->query('voucher_code')),
            'detailsUrl' => $details,
            'account' => $account && $account->role === 'customer' ? $account : null,
            'isOwnerLoggedIn' => $account && $account->role !== 'customer',
            'loginUrl' => route('login', ['next' => $request->getRequestUri()]),
            'methods' => BookingService::PAYMENT_METHODS,
            'holdHours' => intdiv(BookingService::HOLD_MINUTES, 60),
            'freeCancelHours' => BookingWorkflow::FREE_CANCEL_HOURS,
        ]);
    }

    /** Submit the booking. Creates a pending request that the shop must confirm. */
    public function store(Request $request, Business $business, Vehicle $vehicle)
    {
        $this->guard($business, $vehicle);

        $user = $request->user();

        if ($user && $user->role !== 'customer') {
            throw ValidationException::withMessages([
                'vehicle' => 'You are signed in with a business account. Please sign out of it to book as a customer.',
            ]);
        }

        $needsAccount = $user === null;

        $data = $request->validate([
            'pickup_date' => ['required', 'string'],
            'pickup_time' => ['nullable', 'string'],
            'return_date' => ['required', 'string'],
            'return_time' => ['nullable', 'string'],
            'name' => [Rule::requiredIf($needsAccount), 'nullable', 'string', 'min:2', 'max:100'],
            'phone' => [Rule::requiredIf($needsAccount), 'nullable', 'string', 'max:25'],
            'password' => [Rule::requiredIf($needsAccount), 'nullable', 'string', 'min:8', 'max:200'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'voucher_code' => ['nullable', 'string', 'max:40'],
            'payment_method' => ['required', 'string', 'in:' . implode(',', BookingService::PAYMENT_METHODS)],
            'terms' => ['accepted'],
        ], [
            'name.required' => 'Please enter your name.',
            'phone.required' => 'Please enter your phone number.',
            'password.required' => 'Please choose a password so you can see your bookings later.',
            'password.min' => 'Your password needs at least 8 characters.',
            'email.email' => 'Please enter a valid email address, or leave it empty.',
            'terms.accepted' => 'Please accept the rental terms to continue.',
            'payment_method.required' => 'Please choose a payment method.',
        ]);

        [$start, $end] = $this->bookings->parseWindow(
            $data['pickup_date'], $data['pickup_time'] ?? null,
            $data['return_date'], $data['return_time'] ?? null,
        );

        // Check the dates first so nobody gets an account for a booking that can't happen.
        if (! $this->bookings->isAvailable($vehicle, $start, $end)) {
            throw ValidationException::withMessages([
                'pickup_date' => 'Sorry, this vehicle is already booked for those dates. Please try different dates.',
            ]);
        }

        if ($needsAccount) {
            $user = $this->bookings->registerCustomer(
                trim(strip_tags($data['name'])), $data['phone'], $data['password'], $data['email'] ?? null,
            );
            Auth::login($user);
            $request->session()->regenerate();
        }

        $booking = $this->bookings->createBooking($user, $vehicle, $start, $end, [
            'email' => $data['email'] ?? null,
            'notes' => isset($data['notes']) ? trim(strip_tags($data['notes'])) : null,
            'voucher_code' => $data['voucher_code'] ?? null,
            'payment_method' => $data['payment_method'],
        ]);

        return redirect()->route('bookings.show', ['booking' => $booking->id, 'new' => 1]);
    }

    /** My Bookings: current and previous reservations of the signed-in customer. */
    public function index(Request $request)
    {
        $this->bookings->expireStale();

        $userId = $request->user()->id;
        $tab = in_array($request->query('tab'), ['active', 'upcoming', 'completed', 'cancelled'], true)
            ? $request->query('tab') : 'all';
        $search = trim((string) $request->query('q', ''));

        $mine = fn () => Booking::query()->where('customer_id', $userId);

        $counts = [
            'all' => $mine()->count(),
            'active' => $mine()->where('status', 'ongoing')->count(),
            'upcoming' => $mine()->whereIn('status', ['pending', 'confirmed'])->count(),
            'completed' => $mine()->where('status', 'completed')->count(),
            'cancelled' => $mine()->where('status', 'cancelled')->count(),
        ];

        // The rental the customer is riding right now gets a featured card.
        $ongoing = in_array($tab, ['all', 'active'], true) && $search === ''
            ? $this->withRelations($mine())->where('status', 'ongoing')->orderBy('end_datetime')->first()
            : null;

        $list = $this->withRelations($mine())
            ->when($ongoing, fn ($q) => $q->where('id', '!=', $ongoing->id))
            ->when($tab === 'active', fn ($q) => $q->where('status', 'ongoing'))
            ->when($tab === 'upcoming', fn ($q) => $q->whereIn('status', ['pending', 'confirmed']))
            ->when($tab === 'completed', fn ($q) => $q->where('status', 'completed'))
            ->when($tab === 'cancelled', fn ($q) => $q->where('status', 'cancelled'))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], ltrim($search, '#')) . '%';
                $q->where(function ($q) use ($like) {
                    $q->where('reference', 'like', $like)
                        ->orWhereHas('vehicle', fn ($v) => $v->where('brand', 'like', $like)->orWhere('model', 'like', $like));
                });
            })
            ->latest('created_at')
            ->paginate(8)
            ->withQueryString();

        return view('customer.bookings.index', [
            'bookings' => $list,
            'ongoing' => $ongoing,
            'counts' => $counts,
            'tab' => $tab,
            'search' => $search,
            'freeCancelHours' => BookingWorkflow::FREE_CANCEL_HOURS,
        ]);
    }

    private function withRelations($query)
    {
        return $query->with([
            'vehicle:id,brand,model,plate_number,photo_url,status',
            'business:id,name,slug,phone,address,city,is_verified',
        ]);
    }

    /** One booking: status, details, contact the shop, cancel. */
    public function show(Request $request, Booking $booking)
    {
        $this->own($request, $booking);

        $this->bookings->expireStale();
        $booking->refresh()->load(['vehicle.vehicleType', 'business', 'customer']);

        // Optional online payment: offered only while the gateway is on and the booking is eligible.
        $gatewayOn = PaymentGateways::current() !== null;
        $payProblem = PaymentService::problem($booking);
        $lastPayment = $booking->payments()->latest()->first();

        return view('customer.bookings.show', [
            'gatewayOn' => $gatewayOn,
            'canPayOnline' => $payProblem === null,
            'payProblem' => $payProblem,
            'lastPayment' => $lastPayment,
            'payUrl' => route('payments.start', $booking),
            'booking' => $booking,
            'justBooked' => $request->boolean('new'),
            'cancelProblem' => $this->workflow->customerCancelProblem($booking),
            'cancelUrl' => route('bookings.cancel', $booking),
            'holdHours' => intdiv(BookingService::HOLD_MINUTES, 60),
        ]);
    }

    public function cancel(Request $request, Booking $booking)
    {
        $this->own($request, $booking);

        $this->workflow->customerCancel(
            $booking,
            $request->filled('reason') ? mb_substr(strip_tags((string) $request->input('reason')), 0, 200) : null,
        );

        return redirect()->route('bookings.show', $booking)->with('status', 'Your booking has been cancelled.');
    }

    /** A customer can only ever open their own bookings (404, not 403, so IDs can't be probed). */
    private function own(Request $request, Booking $booking): void
    {
        abort_unless($booking->customer_id === $request->user()->id, 404);
    }

    private function guard(Business $business, Vehicle $vehicle): void
    {
        abort_unless($business->isListed(), 404);
        abort_unless($vehicle->business_id === $business->id, 404);
        abort_if($vehicle->status === 'retired', 404);
    }
}