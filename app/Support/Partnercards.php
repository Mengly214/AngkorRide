<?php

namespace App\Support;

use App\Models\Business;
use App\Models\Vehicle;
use Illuminate\Support\Collection;

/**
 * Rental shops customers can see, with the numbers a card needs
 * (how many vehicles, from what price, which kinds, a cover photo).
 * Only shops that are listed AND have at least one available vehicle appear.
 */
class PartnerCards
{
    /** Query for the shops, best first: approved shops, then the biggest fleets. */
    public static function query(?string $search = null, ?string $city = null)
    {
        $available = fn ($v) => $v->where('status', 'available');

        $q = Business::query()
            ->listed()
            ->whereHas('vehicles', $available)
            ->withCount(['vehicles as available_count' => $available])
            ->withMin(['vehicles as from_price' => $available], 'price_per_day');

        if ($search !== null && $search !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
            $q->where(fn ($w) => $w->where('businesses.name', 'like', $like)->orWhere('businesses.city', 'like', $like));
        }

        if ($city !== null && $city !== '') {
            $q->where('businesses.city', $city);
        }

        return $q->orderByRaw("case when businesses.admin_status = 'approved' then 0 else 1 end")
            ->orderByDesc('available_count')
            ->orderBy('businesses.name');
    }

    /** The first few shops, for the home page. */
    public static function top(int $limit): Collection
    {
        return static::decorate(static::query()->limit($limit)->get());
    }

    /** Adds ->types (kinds of vehicle, most common first) and ->cover (a vehicle photo) to each shop. */
    public static function decorate(Collection $businesses): Collection
    {
        if ($businesses->isEmpty()) {
            return $businesses;
        }

        $vehicles = Vehicle::query()
            ->whereIn('business_id', $businesses->pluck('id'))
            ->where('status', 'available')
            ->with('vehicleType:id,name')
            ->orderBy('price_per_day')
            ->get(['id', 'business_id', 'vehicle_type_id', 'photo_url', 'price_per_day'])
            ->groupBy('business_id');

        return $businesses->each(function (Business $b) use ($vehicles) {
            $mine = $vehicles->get($b->id, collect());

            $b->types = $mine->pluck('vehicleType.name')->filter()->countBy()->sortDesc()->keys()->take(3)->all();
            $b->cover = $mine->first(fn ($v) => filled($v->photo_url))?->photo_url;
        });
    }

    /** Cities that currently have at least one visible shop (for the filter on the partners page). */
    public static function cities(): array
    {
        return static::query()->reorder()->whereNotNull('businesses.city')->where('businesses.city', '!=', '')
            ->distinct()->orderBy('businesses.city')->pluck('businesses.city')->all();
    }
}