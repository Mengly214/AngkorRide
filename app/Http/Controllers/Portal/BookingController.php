<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\BookingWorkflow;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    private const TABS = ['all', 'pending', 'confirmed', 'ongoing', 'completed', 'cancelled'];

    public function __construct(private BookingService $bookings, private BookingWorkflow $workflow)
    {
    }

    public function index(Request $request)
    {
        $business = $this->business($request);

        // Unanswered requests whose hold ran out stop showing as "pending".
        $this->bookings->expireStale();

        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'all';
        $search = is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 60) : '';

        $query = $business->bookings()
            ->with(['customer:id,full_name,phone', 'vehicle:id,brand,model,plate_number'])
            ->latest('created_at');

        if ($tab !== 'all') {
            $query->where('status', $tab);
        }

        if ($search !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
            $query->where(function ($w) use ($like) {
                $w->where('reference', 'like', $like)
                    ->orWhereHas('customer', fn ($c) => $c->where('full_name', 'like', $like)->orWhere('phone', 'like', $like));
            });
        }

        $counts = $business->bookings()
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('portal.bookings.index', [
            'business' => $business,
            'bookings' => $query->paginate(15)->withQueryString(),
            'tab' => $tab,
            'search' => $search,
            'counts' => [
                'all' => (int) $counts->sum(),
                'pending' => (int) ($counts['pending'] ?? 0),
                'confirmed' => (int) ($counts['confirmed'] ?? 0),
                'ongoing' => (int) ($counts['ongoing'] ?? 0),
                'completed' => (int) ($counts['completed'] ?? 0),
                'cancelled' => (int) ($counts['cancelled'] ?? 0),
            ],
        ]);
    }

    /** One endpoint for every button: confirm, reject, cancel, pickup, return, paid. */
    public function act(Request $request, Booking $booking, string $action)
    {
        $business = $this->business($request);

        // A shop can only touch its own bookings.
        abort_unless($booking->business_id === $business->id, 404);

        $this->bookings->expireStale();
        $booking->refresh();

        $reason = $request->filled('reason') ? mb_substr(strip_tags((string) $request->input('reason')), 0, 200) : null;

        switch ($action) {
            case 'confirm':
                $this->workflow->confirm($booking);
                $message = "Booking {$booking->reference} confirmed.";
                break;
            case 'reject':
                $this->workflow->reject($booking, $reason);
                $message = "Booking {$booking->reference} rejected.";
                break;
            case 'cancel':
                abort_unless(in_array($booking->status, ['confirmed'], true), 404);
                $this->workflow->cancel($booking, 'business', $reason ?: 'Cancelled by the shop.');
                $message = "Booking {$booking->reference} cancelled.";
                break;
            case 'pickup':
                $this->workflow->pickedUp($booking);
                $message = "Booking {$booking->reference}: vehicle handed over.";
                break;
            case 'return':
                $this->workflow->returned($booking);
                $message = "Booking {$booking->reference}: vehicle returned.";
                break;
            case 'paid':
                $this->workflow->markPaid($booking);
                $message = "Booking {$booking->reference} marked as paid.";
                break;
            default:
                abort(404);
        }

        return back()->with('status', $message);
    }

    private function business(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return $user->businesses()->firstOrFail();
    }
}