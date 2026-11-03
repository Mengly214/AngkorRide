<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Business;
use App\Models\Booking;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    public const TABS = ['pending', 'approved', 'rejected', 'suspended', 'all'];

    /** action => [status the shop must be in, status it moves to, reason required?] */
    private const ACTIONS = [
        'approve' => [['pending', 'rejected'], 'approved', false],
        'reject' => [['pending'], 'rejected', true],
        'suspend' => [['approved'], 'suspended', true],
        'reinstate' => [['suspended'], 'approved', false],
    ];

    public function index(Request $request)
    {
        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'pending';
        $q = is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 60) : '';

        $base = Business::query();
        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
            $base->where(function ($w) use ($like) {
                $w->where('name', 'like', $like)->orWhere('city', 'like', $like)->orWhere('phone', 'like', $like)
                    ->orWhereHas('owner', fn ($o) => $o->where('full_name', 'like', $like)->orWhere('phone', 'like', $like));
            });
        }

        $counts = ['all' => (clone $base)->count()];
        foreach (['pending', 'approved', 'rejected', 'suspended'] as $s) {
            $counts[$s] = (clone $base)->where('admin_status', $s)->count();
        }

        $rows = (clone $base)
            ->when($tab !== 'all', fn ($b) => $b->where('admin_status', $tab))
            ->with('owner:id,full_name,phone')
            ->withCount(['vehicles', 'bookings'])
            ->orderByRaw($tab === 'pending' ? 'created_at asc' : 'created_at desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.businesses.index', compact('rows', 'counts', 'tab', 'q'));
    }

    public function show(Business $business)
    {
        $business->load('owner')->loadCount(['vehicles', 'bookings']);

        $gross = (float) $business->bookings()->whereIn('status', ['confirmed', 'ongoing', 'completed'])->sum('total_price');

        return view('admin.businesses.show', [
            'business' => $business,
            'gross' => $gross,
            'vehicles' => $business->vehicles()->with('vehicleType:id,name')->orderBy('brand')->get(),
            'bookings' => $business->bookings()->with(['customer:id,full_name', 'vehicle:id,brand,model'])->latest()->limit(6)->get(),
            'reviewer' => $business->reviewed_by ? \App\Models\User::find($business->reviewed_by) : null,
            'actions' => collect(self::ACTIONS)->filter(fn ($a) => in_array($business->admin_status, $a[0], true))->map(fn ($a) => $a[2])->all(),
        ]);
    }

    public function act(Request $request, Business $business, string $action)
    {
        abort_unless(isset(self::ACTIONS[$action]), 404);
        [$from, $to, $needsReason] = self::ACTIONS[$action];

        if (! in_array($business->admin_status, $from, true)) {
            return back()->withErrors(['action' => 'This shop is already ' . $business->admin_status . ', so that action no longer applies.']);
        }

        $data = $request->validate([
            'reason' => [$needsReason ? 'required' : 'nullable', 'string', 'min:5', 'max:500'],
        ], [
            'reason.required' => 'Please give a reason. The shop owner will see it.',
            'reason.min' => 'Please give a reason of at least 5 characters.',
        ]);

        $reason = $needsReason ? trim($data['reason']) : null;

        $business->forceFill([
            'admin_status' => $to,
            'admin_note' => $reason,
            'is_verified' => $to === 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ])->save();

        $done = ['approve' => 'approved', 'reject' => 'rejected', 'suspend' => 'suspended', 'reinstate' => 'reinstated'][$action];

        AdminLog::record($request->user(), 'business.' . $action, ucfirst($done) . ' ' . $business->name, 'business', $business->id, $reason);

        return redirect()->route('admin.businesses.show', $business)->with('status', "{$business->name} was {$done}.");
    }
}