<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;

/** Every online payment on the platform, and the refunds AngkorRide still owes. */
class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->query('status'), Payment::STATUSES, true) ? $request->query('status') : null;
        $q = is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 60) : '';

        $base = Payment::query();
        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
            $base->where(function ($w) use ($like) {
                $w->where('tran_id', 'like', $like)->orWhere('provider_ref', 'like', $like)
                    ->orWhereHas('booking', fn ($b) => $b->where('reference', 'like', $like))
                    ->orWhereHas('business', fn ($b) => $b->where('name', 'like', $like));
            });
        }

        $counts = ['all' => (clone $base)->count()];
        foreach (Payment::STATUSES as $s) {
            $counts[$s] = (clone $base)->where('status', $s)->count();
        }

        // Money figures ignore the search box on purpose: they describe the whole platform.
        $kpi = [
            'collected' => (float) Payment::where('status', 'paid')->sum('amount'),
            'refund_due' => (float) Payment::where('status', 'refund_due')->sum('amount'),
            'refunded' => (float) Payment::where('status', 'refunded')->sum('amount'),
            'review' => Payment::where('status', 'review')->count(),
        ];

        // What AngkorRide holds for each shop (paid online, not yet refunded). Payouts are not built yet.
        $owed = Payment::query()->where('status', 'paid')
            ->selectRaw('business_id, SUM(amount) as total, COUNT(*) as n')->groupBy('business_id')
            ->with('business:id,name,slug')->orderByDesc('total')->limit(10)->get();

        $rows = (clone $base)->when($status, fn ($b) => $b->where('status', $status))
            ->with(['booking:id,reference,customer_id', 'booking.customer:id,full_name,phone', 'business:id,name,slug'])
            ->latest()->paginate(20)->withQueryString();

        return view('admin.payments.index', compact('rows', 'counts', 'status', 'q', 'kpi', 'owed'));
    }

    public function refunded(Request $request, Payment $payment, PaymentService $payments)
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:200']]);

        $payments->markRefunded($payment, $request->user(), isset($data['note']) ? trim(strip_tags($data['note'])) : null);

        return back()->with('status', 'Marked as refunded.');
    }
}