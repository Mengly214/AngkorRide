<?php

namespace App\Support;

use App\Models\Business;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds every number shown on the business portal Overview page.
 *
 * Everything here is calculated from real vehicle and booking rows —
 * nothing is hard-coded or estimated.
 *
 * Time zones: the app stores timestamps (created_at) in config('app.timezone'),
 * which is UTC in this project. "Today" and the chart buckets are calculated
 * in Cambodia time so a booking made at 1 AM in Siem Reap lands on the right
 * day. Rental dates (start_datetime / end_datetime) are treated as local
 * wall-clock times, exactly as they are stored.
 */
class DashboardStats
{
    public const TIMEZONE = 'Asia/Phnom_Penh';

    /** Statuses that represent a real rental (not waiting for a decision, not cancelled). */
    private const RENTAL_STATUSES = ['confirmed', 'ongoing', 'completed'];

    /** Longest custom range we draw as a chart (keeps the bars readable). */
    private const MAX_CUSTOM_DAYS = 62;

    /**
     * @param  array{range?:mixed, from?:mixed, to?:mixed, tab?:mixed, metric?:mixed}  $input
     */
    public static function build(Business $business, array $input = []): array
    {
        $range = self::resolveRange($input['range'] ?? null, $input['from'] ?? null, $input['to'] ?? null);
        $metric = ($input['metric'] ?? null) === 'revenue' ? 'revenue' : 'bookings';
        $tab = in_array($input['tab'] ?? null, ['all', 'action', 'pickups'], true) ? $input['tab'] : 'all';

        $now = Carbon::now(self::TIMEZONE);
        $appTz = config('app.timezone', 'UTC');
        $todayStart = $now->copy()->startOfDay()->toDateTimeString();
        $todayEnd = $now->copy()->endOfDay()->toDateTimeString();

        // ------------------------------------------------------------------
        // Fleet
        // ------------------------------------------------------------------
        $vehicles = $business->vehicles()
            ->with('vehicleType:id,name')
            ->get(['id', 'vehicle_type_id', 'status']);

        $fleet = [
            'total' => $vehicles->count(),
            'available' => $vehicles->where('status', 'available')->count(),
            'rented' => $vehicles->where('status', 'rented')->count(),
            'maintenance' => $vehicles->where('status', 'maintenance')->count(),
            'retired' => $vehicles->where('status', 'retired')->count(),
        ];
        $fleet['available_pct'] = $fleet['total'] > 0
            ? (int) round($fleet['available'] / $fleet['total'] * 100)
            : 0;

        $byType = $vehicles
            ->groupBy(fn ($v) => $v->vehicleType->name ?? 'Uncategorized')
            ->map(fn (Collection $group) => [
                'total' => $group->count(),
                'available' => $group->where('status', 'available')->count(),
            ])
            ->sortByDesc(fn (array $t) => $t['total']);

        // ------------------------------------------------------------------
        // Bookings created inside the selected range
        // ------------------------------------------------------------------
        $rows = $business->bookings()
            ->whereBetween('created_at', [
                $range['start']->copy()->setTimezone($appTz),
                $range['end']->copy()->setTimezone($appTz),
            ])
            ->get(['id', 'status', 'payment_status', 'total_price', 'created_at']);

        $paidRows = $rows->filter(
            fn ($b) => in_array($b->status, self::RENTAL_STATUSES, true) && $b->payment_status === 'paid'
        );

        // ------------------------------------------------------------------
        // All-time booking counts per status (pending, active, ...)
        // ------------------------------------------------------------------
        $statusCounts = $business->bookings()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pending = (int) ($statusCounts['pending'] ?? 0);
        $active = (int) ($statusCounts['ongoing'] ?? 0);
        $allTime = (int) $statusCounts->sum();

        // ------------------------------------------------------------------
        // Today's pickups and returns (+ overdue returns)
        // ------------------------------------------------------------------
        $with = ['customer:id,full_name,phone', 'vehicle:id,brand,model,plate_number'];

        $pickups = $business->bookings()->with($with)
            ->where('status', 'confirmed')
            ->whereBetween('start_datetime', [$todayStart, $todayEnd])
            ->orderBy('start_datetime')
            ->get();

        $returns = $business->bookings()->with($with)
            ->where('status', 'ongoing')
            ->whereBetween('end_datetime', [$todayStart, $todayEnd])
            ->orderBy('end_datetime')
            ->get();

        $overdue = (int) $business->bookings()
            ->where('status', 'ongoing')
            ->where('end_datetime', '<', $now->toDateTimeString())
            ->count();

        // ------------------------------------------------------------------
        // Recent bookings table (with the All / Needs Action / Pickups tabs)
        // ------------------------------------------------------------------
        $recentQuery = $business->bookings()->with($with)->latest('created_at');

        if ($tab === 'action') {
            $recentQuery->where('status', 'pending');
        } elseif ($tab === 'pickups') {
            $recentQuery->where('status', 'confirmed')
                ->whereBetween('start_datetime', [$todayStart, $todayEnd]);
        }

        $recent = $recentQuery->limit(6)->get();

        // ------------------------------------------------------------------
        // "Needs attention" list
        // ------------------------------------------------------------------
        $attention = [];

        if ($pending > 0) {
            $attention[] = [
                'tone' => 'error',
                'icon' => 'pending_actions',
                'title' => $pending === 1 ? '1 booking request waiting' : "{$pending} booking requests waiting",
                'detail' => 'Approve or reject so customers know where they stand.',
            ];
        }
        if ($overdue > 0) {
            $attention[] = [
                'tone' => 'error',
                'icon' => 'schedule',
                'title' => $overdue === 1 ? '1 rental is overdue' : "{$overdue} rentals are overdue",
                'detail' => 'The return time has passed and the vehicle is still out.',
            ];
        }
        if ($pickups->count() > 0) {
            $attention[] = [
                'tone' => 'info',
                'icon' => 'key',
                'title' => $pickups->count() === 1 ? '1 pickup today' : "{$pickups->count()} pickups today",
                'detail' => 'Have the vehicle and helmets ready.',
            ];
        }
        if ($returns->count() > 0) {
            $attention[] = [
                'tone' => 'info',
                'icon' => 'assignment_return',
                'title' => $returns->count() === 1 ? '1 return due today' : "{$returns->count()} returns due today",
                'detail' => 'Check the vehicle condition when it comes back.',
            ];
        }
        if ($fleet['maintenance'] > 0) {
            $attention[] = [
                'tone' => 'warn',
                'icon' => 'build',
                'title' => $fleet['maintenance'] === 1 ? '1 vehicle in maintenance' : "{$fleet['maintenance']} vehicles in maintenance",
                'detail' => 'Not bookable until you set them back to available.',
            ];
        }

        return [
            'range' => $range,
            'metric' => $metric,
            'tab' => $tab,
            'greeting' => self::greeting($now),
            'today' => $now->format('l, F j'),
            'fleet' => $fleet,
            'byType' => $byType,
            'kpi' => [
                'bookings' => $rows->count(),
                'pending' => $pending,
                'active' => $active,
                'revenue' => (float) $paidRows->sum('total_price'),
                'paid_bookings' => $paidRows->count(),
            ],
            'chart' => self::chart($range, $rows, $metric),
            'attention' => $attention,
            'pickups' => $pickups,
            'returns' => $returns,
            'overdue' => $overdue,
            'recent' => $recent,
            'tabCounts' => [
                'all' => $allTime,
                'action' => $pending,
                'pickups' => $pickups->count(),
            ],
        ];
    }

    /**
     * Turn the ?range= / ?from= / ?to= query values into a safe date window.
     * Anything invalid quietly falls back to "Last 30 days".
     */
    public static function resolveRange(mixed $key, mixed $from = null, mixed $to = null): array
    {
        $now = Carbon::now(self::TIMEZONE);
        $key = in_array($key, ['today', '7d', '30d', 'custom'], true) ? $key : '30d';

        if ($key === 'custom') {
            $start = $end = null;
            $datePattern = '/^\d{4}-\d{2}-\d{2}$/';

            if (is_string($from) && is_string($to) && preg_match($datePattern, $from) && preg_match($datePattern, $to)) {
                try {
                    $start = Carbon::createFromFormat('Y-m-d', $from, self::TIMEZONE)->startOfDay();
                    $end = Carbon::createFromFormat('Y-m-d', $to, self::TIMEZONE)->endOfDay();
                } catch (\Throwable) {
                    $start = $end = null;
                }
            }

            if ($start && $end) {
                if ($end->lt($start)) {
                    [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
                }
                if ($end->gt($now)) {
                    $end = $now->copy()->endOfDay();
                }
                if ($start->gt($end)) {
                    $start = $end->copy()->startOfDay();
                }
                if ($start->diffInDays($end) > self::MAX_CUSTOM_DAYS - 1) {
                    $start = $end->copy()->subDays(self::MAX_CUSTOM_DAYS - 1)->startOfDay();
                }

                return [
                    'key' => 'custom',
                    'label' => $start->format('M j') . ' – ' . $end->format('M j, Y'),
                    'start' => $start,
                    'end' => $end,
                    'from' => $start->toDateString(),
                    'to' => $end->toDateString(),
                ];
            }

            $key = '30d';
        }

        $days = ['today' => 1, '7d' => 7, '30d' => 30][$key];
        $labels = ['today' => 'Today', '7d' => 'Last 7 days', '30d' => 'Last 30 days'];
        $start = $now->copy()->subDays($days - 1)->startOfDay();
        $end = $now->copy()->endOfDay();

        return [
            'key' => $key,
            'label' => $labels[$key],
            'start' => $start,
            'end' => $end,
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
        ];
    }

    /**
     * One bucket per hour (for "Today") or per day, counting bookings / revenue.
     */
    private static function chart(array $range, Collection $rows, string $metric): array
    {
        $hourly = $range['key'] === 'today';
        $buckets = [];

        if ($hourly) {
            for ($h = 0; $h < 24; $h++) {
                $buckets[sprintf('%02d', $h)] = [
                    'label' => sprintf('%02d:00', $h),
                    'bookings' => 0,
                    'revenue' => 0.0,
                ];
            }
        } else {
            for ($d = $range['start']->copy(); $d->lte($range['end']); $d->addDay()) {
                $buckets[$d->format('Y-m-d')] = [
                    'label' => $d->format('M j'),
                    'bookings' => 0,
                    'revenue' => 0.0,
                ];
            }
        }

        foreach ($rows as $booking) {
            $local = $booking->created_at->copy()->setTimezone(self::TIMEZONE);
            $key = $hourly ? $local->format('H') : $local->format('Y-m-d');

            if (! isset($buckets[$key])) {
                continue;
            }

            $buckets[$key]['bookings']++;

            if (in_array($booking->status, self::RENTAL_STATUSES, true) && $booking->payment_status === 'paid') {
                $buckets[$key]['revenue'] += (float) $booking->total_price;
            }
        }

        $values = array_column($buckets, $metric);

        return [
            'buckets' => array_values($buckets),
            'max' => $values ? max($values) : 0,
            'total' => array_sum($values),
        ];
    }

    private static function greeting(Carbon $now): string
    {
        $hour = (int) $now->format('G');

        return match (true) {
            $hour < 12 => 'Good morning',
            $hour < 18 => 'Good afternoon',
            default => 'Good evening',
        };
    }
}