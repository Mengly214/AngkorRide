<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Booking;
use App\Models\Business;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    private const COUNTED = ['confirmed', 'ongoing', 'completed'];

    public function index(Request $request)
    {
        $range = in_array($request->query('range'), ['7d', '30d', '12m'], true) ? $request->query('range') : '30d';

        $gross = (float) Booking::whereIn('status', self::COUNTED)->sum('total_price');
        $collected = (float) Booking::whereIn('status', self::COUNTED)->where('payment_status', 'paid')->sum('total_price');

        return view('admin.dashboard', [
            'range' => $range,
            'kpi' => [
                'customers' => User::where('role', 'customer')->count(),
                'businesses' => Business::where('admin_status', 'approved')->count(),
                'vehicles' => Vehicle::where('status', 'available')->whereHas('business', fn ($b) => $b->listed())->count(),
                'bookings' => Booking::count(),
                'gross' => $gross,
                'collected' => $collected,
                'pending_shops' => Business::where('admin_status', 'pending')->count(),
                'pending_bookings' => Booking::where('status', 'pending')->count(),
            ],
            'charts' => $this->charts($range),
            'pending' => Business::where('admin_status', 'pending')->with('owner:id,full_name,phone')->withCount('vehicles')->latest()->limit(5)->get(),
            'recent' => Booking::with(['customer:id,full_name', 'vehicle:id,brand,model', 'business:id,name'])->latest()->limit(8)->get(),
            'activity' => AdminLog::with('admin:id,full_name')->latest('created_at')->latest('id')->limit(8)->get(),
        ]);
    }

    /** Bookings made, and their value, per day (7d / 30d) or per month (12m). */
    private function charts(string $range): array
    {
        $tz = 'Asia/Phnom_Penh';
        $now = Carbon::now($tz);
        $monthly = $range === '12m';

        $start = match ($range) {
            '7d' => $now->copy()->subDays(6)->startOfDay(),
            '30d' => $now->copy()->subDays(29)->startOfDay(),
            default => $now->copy()->subMonths(11)->startOfMonth(),
        };

        $buckets = [];
        for ($d = $start->copy(); $d <= $now; $monthly ? $d->addMonth() : $d->addDay()) {
            $key = $d->format($monthly ? 'Y-m' : 'Y-m-d');
            $buckets[$key] = [
                'label' => $monthly ? $d->format('M') : $d->format('j'),
                'title' => $monthly ? $d->format('F Y') : $d->format('D, M j'),
                'count' => 0,
                'value' => 0.0,
            ];
        }

        Booking::where('created_at', '>=', $start->copy()->utc())
            ->get(['created_at', 'status', 'total_price'])
            ->each(function ($b) use (&$buckets, $tz, $monthly) {
                $key = $b->created_at->copy()->setTimezone($tz)->format($monthly ? 'Y-m' : 'Y-m-d');
                if (! isset($buckets[$key])) {
                    return;
                }
                $buckets[$key]['count']++;
                if (in_array($b->status, self::COUNTED, true)) {
                    $buckets[$key]['value'] += (float) $b->total_price;
                }
            });

        $buckets = array_values($buckets);

        return [
            'buckets' => $buckets,
            'total_count' => array_sum(array_column($buckets, 'count')),
            'total_value' => array_sum(array_column($buckets, 'value')),
            'max_count' => max(1, ...array_column($buckets, 'count')),
            'max_value' => max(1, ...array_column($buckets, 'value')),
        ];
    }
}