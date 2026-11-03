<?php

namespace App\Support;

use App\Models\Business;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The people who have booked with this shop, built from its own bookings only.
 *
 * "Rental" = a confirmed, ongoing or completed booking. A customer who rented
 * two or more times is "returning". Money is the rental total (no deposits).
 */
class CustomerReport
{
    public const PER_PAGE = 15;

    private const RENTAL = "('confirmed','ongoing','completed')";

    /** One row per customer, newest activity first by default. */
    public static function all(Business $business): Collection
    {
        $rows = $business->bookings()
            ->selectRaw('customer_id,'
                . ' count(*) as bookings_count,'
                . ' sum(case when status in ' . self::RENTAL . ' then 1 else 0 end) as rentals_count,'
                . " sum(case when status = 'cancelled' then 1 else 0 end) as cancelled_count,"
                . " sum(case when status in " . self::RENTAL . " and payment_status = 'paid' then total_price else 0 end) as paid_total,"
                . " sum(case when status in " . self::RENTAL . " and payment_status != 'paid' then total_price else 0 end) as unpaid_total,"
                . ' max(created_at) as last_booking_at,'
                . ' min(created_at) as first_booking_at,'
                . ' max(case when status in ' . self::RENTAL . ' then start_datetime end) as last_rental_at')
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->get();

        $users = User::query()->whereIn('id', $rows->pluck('customer_id'))
            ->get(['id', 'full_name', 'phone', 'email'])->keyBy('id');

        return $rows->map(function ($r) use ($users) {
            $u = $users[$r->customer_id] ?? null;
            $rentals = (int) $r->rentals_count;

            return (object) [
                'id' => $r->customer_id,
                'name' => $u?->full_name ?: 'Customer',
                'phone' => $u?->phone,
                'email' => $u?->email,
                'bookings' => (int) $r->bookings_count,
                'rentals' => $rentals,
                'cancelled' => (int) $r->cancelled_count,
                'paid' => (float) $r->paid_total,
                'unpaid' => (float) $r->unpaid_total,
                'spent' => (float) $r->paid_total + (float) $r->unpaid_total,
                'first_booking_at' => Carbon::parse($r->first_booking_at),
                'last_booking_at' => Carbon::parse($r->last_booking_at),
                'last_rental_at' => $r->last_rental_at ? Carbon::parse($r->last_rental_at) : null,
                'returning' => $rentals >= 2,
            ];
        })->values();
    }

    public static function summary(Collection $all): array
    {
        $withRentals = $all->where('rentals', '>', 0);
        $returning = $all->where('returning', true);
        $cutoff = Carbon::now('UTC')->subDays(30);

        return [
            'total' => $all->count(),
            'returning' => $returning->count(),
            'repeat_rate' => $withRentals->count() > 0 ? round($returning->count() / $withRentals->count() * 100) : 0,
            'avg_rentals' => $withRentals->count() > 0 ? round($withRentals->sum('rentals') / $withRentals->count(), 1) : 0.0,
            'new_30d' => $all->filter(fn ($c) => $c->first_booking_at->gte($cutoff))->count(),
            'top' => $all->sortByDesc('spent')->first(fn ($c) => $c->spent > 0),
        ];
    }

    /** Search, tab and sort the list, then cut out one page. */
    public static function page(Collection $all, string $q, string $tab, string $sort, int $page, string $path, array $query): LengthAwarePaginator
    {
        $list = $all;

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $digits = preg_replace('/\D+/', '', $q);
            $list = $list->filter(fn ($c) => str_contains(mb_strtolower($c->name), $needle)
                || ($digits !== '' && str_contains(preg_replace('/\D+/', '', (string) $c->phone), $digits)));
        }

        $list = match ($tab) {
            'returning' => $list->where('returning', true),
            'new' => $list->where('rentals', 1),
            default => $list,
        };

        $list = (match ($sort) {
            'spent' => $list->sortByDesc('spent'),
            'rentals' => $list->sortByDesc(fn ($c) => [$c->rentals, $c->spent]),
            default => $list->sortByDesc(fn ($c) => $c->last_booking_at->timestamp),
        })->values();

        return new LengthAwarePaginator(
            $list->forPage($page, self::PER_PAGE)->values(),
            $list->count(),
            self::PER_PAGE,
            $page,
            ['path' => $path, 'query' => $query],
        );
    }

    public static function counts(Collection $all): array
    {
        return [
            'all' => $all->count(),
            'returning' => $all->where('returning', true)->count(),
            'new' => $all->where('rentals', 1)->count(),
        ];
    }
}