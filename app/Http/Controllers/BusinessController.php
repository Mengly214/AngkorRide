<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Support\PartnerCards;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    /** All visible rental shops: /partners */
    public function index(Request $request)
    {
        $q = is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 60) : '';
        $cities = PartnerCards::cities();
        $city = in_array($request->query('city'), $cities, true) ? $request->query('city') : '';

        $page = PartnerCards::query($q, $city)->paginate(12)->withQueryString();
        $page->setCollection(PartnerCards::decorate($page->getCollection()));

        return view('business.index', [
            'active' => 'partners',
            'partners' => $page,
            'cities' => $cities,
            'q' => $q,
            'city' => $city,
        ]);
    }

    /**
     * One shop's public profile, with its available vehicles — the page customers
     * land on from a QR code, Google Maps link, or social post.
     */
    public function show(Request $request, Business $business)
    {
        abort_unless($business->isListed(), 404);

        $vehicles = $business->vehicles()
            ->where('status', 'available')
            ->with('vehicleType')
            ->orderBy('price_per_day')
            ->get();

        $vehicles->each(fn ($v) => $v->setRelation('business', $business));

        $user = $request->user();
        $deposits = $vehicles->pluck('deposit_amount')->map(fn ($d) => (float) $d)->filter(fn ($d) => $d > 0);

        return view('business.show', [
            'active' => 'partners',
            'business' => $business,
            'vehicles' => $vehicles,
            'types' => $vehicles->pluck('vehicleType.name')->filter()->countBy(),
            'cover' => $vehicles->first(fn ($v) => filled($v->photo_url))?->photo_url,
            'fromPrice' => $vehicles->min('price_per_day'),
            'depositMin' => $deposits->min(),
            'depositMax' => $deposits->max(),
            'pickups' => $vehicles->pluck('pick_up_location')->filter()->unique()->take(5)->values(),
            'savedIds' => $user && $user->role === 'customer' ? $user->favoriteVehicles()->pluck('vehicles.id')->all() : [],
            'next' => '/business/' . $business->slug,
        ]);
    }
}