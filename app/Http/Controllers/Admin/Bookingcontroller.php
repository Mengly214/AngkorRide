<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

/** Read-only view of every reservation on the platform. Shops run their own bookings. */
class BookingController extends Controller
{
    public const STATUSES = ['pending', 'confirmed', 'ongoing', 'completed', 'cancelled'];

    public function index(Request $request)
    {
        $status = in_array($request->query('status'), self::STATUSES, true) ? $request->query('status') : null;
        $payment = in_array($request->query('payment'), ['paid', 'unpaid'], true) ? $request->query('payment') : null;
        $q = is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 60) : '';

        $base = Booking::query();
        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
            $base->where(function ($w) use ($like) {
                $w->where('reference', 'like', $like)
                    ->orWhereHas('customer', fn ($c) => $c->where('full_name', 'like', $like)->orWhere('phone', 'like', $like))
                    ->orWhereHas('business', fn ($b) => $b->where('name', 'like', $like));
            });
        }

        $counts = ['all' => (clone $base)->count()];
        foreach (self::STATUSES as $s) {
            $counts[$s] = (clone $base)->where('status', $s)->count();
        }

        $rows = (clone $base)
            ->when($status, fn ($b) => $b->where('status', $status))
            ->when($payment, fn ($b) => $payment === 'paid' ? $b->where('payment_status', 'paid') : $b->where('payment_status', '!=', 'paid'))
            ->with(['customer:id,full_name,phone', 'vehicle:id,brand,model,plate_number', 'business:id,name,slug'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.bookings.index', compact('rows', 'counts', 'status', 'payment', 'q'));
    }
}