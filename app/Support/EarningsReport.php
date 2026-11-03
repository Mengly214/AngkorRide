<?php

namespace App\Support;

use App\Models\Business;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;

/**
 * The numbers on the portal "Earnings & Transactions" page.
 *
 * What counts: real rentals only (confirmed, ongoing, completed). Pending
 * requests, rejected and cancelled bookings never count as money.
 *  - Collected  = rentals marked paid (total_price, deposits are not included)
 *  - To collect = rentals not yet marked paid
 * Rentals are placed in a period by their pickup date, which is stored as
 * Cambodia wall-clock time, so there is no time-zone shifting.
 */
class EarningsReport
{
    public const TIMEZONE = 'Asia/Phnom_Penh';

    public const RENTAL_STATUSES = ['confirmed', 'ongoing', 'completed'];

    /** Month / year / everything. Returns the dates plus links to move around. */
    public static function period(array $input): array
    {
        $now = Carbon::now(self::TIMEZONE);
        $range = in_array($input['range'] ?? null, ['month', 'year', 'all'], true) ? $input['range'] : 'month';

        if ($range === 'all') {
            return [
                'range' => 'all',
                'start' => Carbon::create(2000, 1, 1, 0, 0, 0, self::TIMEZONE),
                'end' => Carbon::create(2100, 1, 1, 0, 0, 0, self::TIMEZONE),
                'label' => 'All time',
                'prev' => null,
                'next' => null,
                'param' => [],
                'is_current' => true,
            ];
        }

        $min = $now->copy()->startOfMonth()->subYears(5);
        $max = $now->copy()->startOfMonth()->addMonths(12);

        if ($range === 'year') {
            $year = (is_string($input['y'] ?? null) && preg_match('/^\d{4}$/', $input['y'])) ? (int) $input['y'] : $now->year;
            $year = max($min->year, min($max->year, $year));
            $start = Carbon::create($year, 1, 1, 0, 0, 0, self::TIMEZONE);

            return [
                'range' => 'year',
                'start' => $start,
                'end' => $start->copy()->addYear(),
                'label' => (string) $year,
                'prev' => $year > $min->year ? ['range' => 'year', 'y' => $year - 1] : null,
                'next' => $year < $max->year ? ['range' => 'year', 'y' => $year + 1] : null,
                'param' => ['range' => 'year', 'y' => $year],
                'is_current' => $year === $now->year,
            ];
        }

        $start = $now->copy()->startOfMonth();
        if (is_string($input['m'] ?? null) && preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $input['m'], $mm)) {
            $try = Carbon::create((int) $mm[1], (int) $mm[2], 1, 0, 0, 0, self::TIMEZONE);
            if ($try->gte($min) && $try->lte($max)) {
                $start = $try;
            }
        }

        return [
            'range' => 'month',
            'start' => $start,
            'end' => $start->copy()->addMonthNoOverflow()->startOfMonth(),
            'label' => $start->format('F Y'),
            'prev' => $start->gt($min) ? ['range' => 'month', 'm' => $start->copy()->subMonthNoOverflow()->format('Y-m')] : null,
            'next' => $start->lt($max) ? ['range' => 'month', 'm' => $start->copy()->addMonthNoOverflow()->format('Y-m')] : null,
            'param' => ['range' => 'month', 'm' => $start->format('Y-m')],
            'is_current' => $start->format('Y-m') === $now->format('Y-m'),
        ];
    }

    /** Rentals (not pending / cancelled) whose pickup falls in the period. */
    public static function rentals(Business $business, array $period)
    {
        return $business->bookings()
            ->whereIn('status', self::RENTAL_STATUSES)
            ->where('start_datetime', '>=', $period['start']->toDateTimeString())
            ->where('start_datetime', '<', $period['end']->toDateTimeString());
    }

    /** Days a rental is charged for: every started 24 hours, at least one. */
    public static function chargedDays($start, $end): int
    {
        return max(1, (int) ceil(abs($end->diffInMinutes($start)) / 1440));
    }

    public static function summary(Business $business, array $period): array
    {
        $rows = self::rentals($business, $period)
            ->get(['id', 'vehicle_id', 'status', 'payment_status', 'total_price', 'discount_amount', 'voucher_code', 'start_datetime', 'end_datetime']);

        $paid = $rows->where('payment_status', 'paid');
        $unpaid = $rows->where('payment_status', '!=', 'paid');

        $collected = (float) $paid->sum('total_price');
        $toCollect = (float) $unpaid->sum('total_price');
        $count = $rows->count();

        $cancelled = $business->bookings()
            ->where('status', 'cancelled')
            ->where('start_datetime', '>=', $period['start']->toDateTimeString())
            ->where('start_datetime', '<', $period['end']->toDateTimeString())
            ->count();

        return [
            'collected' => $collected,
            'to_collect' => $toCollect,
            'rentals' => $count,
            'paid_count' => $paid->count(),
            'unpaid_count' => $unpaid->count(),
            'average' => $count > 0 ? ($collected + $toCollect) / $count : 0.0,
            'discounts' => (float) $rows->sum('discount_amount'),
            'vouchers_used' => $rows->filter(fn ($r) => filled($r->voucher_code))->count(),
            'cancelled' => $cancelled,
            'chart' => self::chart($rows, $period, $business),
            'vehicles' => self::byVehicle($rows),
        ];
    }

    /** Stacked bars: collected + still to collect, per day (month) or per month (year / all). */
    private static function chart($rows, array $period, Business $business): array
    {
        $buckets = [];

        if ($period['range'] === 'month') {
            for ($d = 1; $d <= $period['start']->daysInMonth; $d++) {
                $buckets[$d] = ['label' => (string) $d, 'title' => $period['start']->copy()->day($d)->format('D j M'), 'paid' => 0.0, 'unpaid' => 0.0];
            }
            $key = fn ($r) => (int) $r->start_datetime->format('j');
        } else {
            if ($period['range'] === 'year') {
                $from = $period['start']->copy();
                $to = $period['start']->copy()->endOfYear();
            } else {
                $first = $business->bookings()->whereIn('status', self::RENTAL_STATUSES)->min('start_datetime');
                $to = Carbon::now(self::TIMEZONE)->startOfMonth();
                $from = $first ? Carbon::parse($first)->startOfMonth() : $to->copy();
                if ($from->diffInMonths($to) > 23) {
                    $from = $to->copy()->subMonths(23);
                }
                // include months with upcoming rentals, too
                $last = $rows->max('start_datetime');
                if ($last && Carbon::parse($last)->gt($to)) {
                    $to = Carbon::parse($last)->startOfMonth();
                }
            }
            for ($m = $from->copy()->startOfMonth(); $m->lte($to); $m->addMonthNoOverflow()) {
                $buckets[$m->format('Y-m')] = [
                    'label' => $period['range'] === 'year' ? $m->format('M') : $m->format("M 'y"),
                    'title' => $m->format('F Y'),
                    'paid' => 0.0,
                    'unpaid' => 0.0,
                ];
            }
            $key = fn ($r) => $r->start_datetime->format('Y-m');
        }

        foreach ($rows as $r) {
            $k = $key($r);
            if (! isset($buckets[$k])) {
                continue;
            }
            $buckets[$k][$r->payment_status === 'paid' ? 'paid' : 'unpaid'] += (float) $r->total_price;
        }

        $max = 0.0;
        foreach ($buckets as $b) {
            $max = max($max, $b['paid'] + $b['unpaid']);
        }

        return ['buckets' => array_values($buckets), 'max' => $max];
    }

    /** Revenue per vehicle, best first. */
    private static function byVehicle($rows): array
    {
        $names = Vehicle::query()->whereIn('id', $rows->pluck('vehicle_id')->unique())
            ->get(['id', 'brand', 'model', 'plate_number'])->keyBy('id');

        return $rows->groupBy('vehicle_id')->map(function ($g, $id) use ($names) {
            $v = $names[$id] ?? null;

            return [
                'name' => $v ? (trim($v->brand . ' ' . $v->model) ?: $v->plate_number) : 'Removed vehicle',
                'plate' => $v?->plate_number,
                'rentals' => $g->count(),
                'days' => $g->sum(fn ($r) => self::chargedDays($r->start_datetime, $r->end_datetime)),
                'paid' => (float) $g->where('payment_status', 'paid')->sum('total_price'),
                'unpaid' => (float) $g->where('payment_status', '!=', 'paid')->sum('total_price'),
            ];
        })->sortByDesc(fn ($v) => $v['paid'] + $v['unpaid'])->values()->all();
    }
}