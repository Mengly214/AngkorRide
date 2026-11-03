<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class HomeController extends Controller
{
    public function __construct(private BookingService $bookings)
    {
    }

    /** Search-related query keys; if any are present on "/", we show results instead. */
    private const SEARCH_KEYS = ['q', 'type', 'max_price', 'sort', 'pickup_date', 'pickup_time', 'return_date', 'return_time', 'page'];

    /** Short lines for the destination cards (only shown for cities that have listings). */
    private const CITY_BLURBS = [
        'siem reap' => 'Angkor temples, Pub Street and quiet countryside roads',
        'phnom penh' => 'Riverside capital, markets and the Mekong',
        'kampot' => 'Pepper farms, river sunsets and the coast',
        'kep' => 'Crab market and sea views',
        'battambang' => 'Colonial streets and the bamboo train',
    ];

    /**
     * The home page: hero + search, vehicle types, popular rides, destinations,
     * how it works and the partner call-to-action. All of it comes from real listings.
     */
    public function index(Request $request)
    {
        // Old links like "/?type=..." keep working: they go to the results page.
        if (collect(self::SEARCH_KEYS)->contains(fn ($k) => $request->query($k) !== null)) {
            return redirect()->route('explore', $request->query());
        }

        $listed = fn () => Vehicle::query()
            ->where('vehicles.status', 'available')
            ->whereHas('business', fn ($b) => $b->listed());

        $stats = $listed()->selectRaw('vehicle_type_id, count(*) as total, min(price_per_day) as from_price')
            ->groupBy('vehicle_type_id')->get()->keyBy('vehicle_type_id');

        $types = VehicleType::where('is_active', true)->orderBy('name')->get()
            ->filter(fn ($t) => isset($stats[$t->id]))
            ->each(function ($t) use ($stats, $listed) {
                $t->listed_count = (int) $stats[$t->id]->total;
                $t->from_price = (float) $stats[$t->id]->from_price;
                // a photo for the category card: the cheapest listed vehicle of this type that has one
                $t->cover = $listed()->where('vehicle_type_id', $t->id)
                    ->whereNotNull('photo_url')->where('photo_url', '!=', '')
                    ->orderBy('price_per_day')->first()?->photo_url;
            })->values();

        $featured = $listed()->with(['business', 'vehicleType'])
            ->withCount(['bookings' => fn ($b) => $b->whereIn('status', ['confirmed', 'ongoing', 'completed'])])
            ->orderByDesc('bookings_count')->orderBy('price_per_day')
            ->take(8)->get();

        $cities = $listed()
            ->join('businesses', 'businesses.id', '=', 'vehicles.business_id')
            ->whereNotNull('businesses.city')->where('businesses.city', '!=', '')
            ->selectRaw('businesses.city as city, count(*) as total, min(vehicles.price_per_day) as from_price')
            ->groupBy('businesses.city')->orderByDesc('total')->take(6)->get()
            ->each(fn ($c) => $c->blurb = self::CITY_BLURBS[mb_strtolower($c->city)] ?? 'Rides from local rental shops');

        $savedIds = $this->savedIds($request);

        return view('home', [
            'types' => $types,
            'allTypes' => VehicleType::where('is_active', true)->orderBy('name')->get(),
            'featured' => $featured,
            'cities' => $cities,
            'partners' => \App\Support\PartnerCards::top(3),
            'totalVehicles' => (int) $stats->sum('total'),
            'totalCities' => $cities->count(),
            'totalShops' => $listed()->distinct()->count('vehicles.business_id'),
            'savedIds' => $savedIds,
            'next' => '/',
            'todayPP' => now('Asia/Phnom_Penh')->toDateString(),
            'heroPhoto' => file_exists(public_path('images/hero.jpg')) ? asset('images/hero.jpg') : null,
        ]);
    }

    /** Vehicle ids the signed-in customer has saved. */
    private function savedIds(Request $request): array
    {
        $user = $request->user();

        return $user && $user->role === 'customer'
            ? $user->favoriteVehicles()->pluck('vehicles.id')->all()
            : [];
    }

    /**
     * Customer "Explore" page = vehicle search.
     *
     * Query string: q (text), type (vehicle type id), max_price, sort,
     * pickup_date / pickup_time / return_date / return_time.
     * When dates are given, vehicles that are already booked for them are hidden.
     */
    public function explore(Request $request)
    {
        $q = $this->text($request->query('q'), 60);
        $type = $this->text($request->query('type'), 40);
        $maxPrice = is_numeric($request->query('max_price')) ? (float) $request->query('max_price') : null;
        // The slider's top stop means "$35+", i.e. no upper limit.
        if ($maxPrice !== null && $maxPrice >= 35) {
            $maxPrice = null;
        }
        $sort = in_array($request->query('sort'), ['price_asc', 'price_desc', 'newest'], true)
            ? $request->query('sort')
            : 'recommended';

        // Dates are optional; a bad pair is ignored with a visible message.
        $dateError = null;
        $window = null;
        $hasDates = filled($request->query('pickup_date')) || filled($request->query('return_date'));

        if ($hasDates) {
            try {
                $window = $this->bookings->parseWindow(
                    $request->query('pickup_date'), $request->query('pickup_time'),
                    $request->query('return_date'), $request->query('return_time'),
                );
            } catch (ValidationException $e) {
                $dateError = collect($e->errors())->flatten()->first();
            }
        }

        $query = Vehicle::query()
            ->where('status', 'available')
            ->whereHas('business', fn ($b) => $b->listed())
            ->with(['business', 'vehicleType']);

        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
            $query->where(function ($w) use ($like) {
                $w->where('brand', 'like', $like)
                    ->orWhere('model', 'like', $like)
                    ->orWhere('pick_up_location', 'like', $like)
                    ->orWhereHas('business', fn ($b) => $b->where('name', 'like', $like)
                        ->orWhere('address', 'like', $like)
                        ->orWhere('city', 'like', $like));
            });
        }

        if ($type !== '') {
            $query->where('vehicle_type_id', $type);
        }

        if ($maxPrice !== null && $maxPrice > 0) {
            $query->where('price_per_day', '<=', $maxPrice);
        }

        if ($window) {
            $taken = $this->bookings->unavailableVehicleIds($window[0], $window[1]);
            if ($taken) {
                $query->whereNotIn('id', $taken);
            }
        }

        match ($sort) {
            'price_asc' => $query->orderBy('price_per_day'),
            'price_desc' => $query->orderByDesc('price_per_day'),
            'newest' => $query->orderByDesc('created_at'),
            default => $query->orderBy('price_per_day'),
        };

        $vehicles = $query->paginate(6)->withQueryString();

        return view('explore', [
            'savedIds' => $this->savedIds($request),
            'next' => $request->getRequestUri(),
            'vehicles' => $vehicles,
            'types' => VehicleType::where('is_active', true)->orderBy('name')->get(),
            'filters' => [
                'q' => $q,
                'type' => $type,
                'max_price' => $maxPrice,
                'sort' => $sort,
                'pickup_date' => $window ? $window[0]->toDateString() : '',
                'pickup_time' => $window ? $window[0]->format('H:i') : '09:00',
                'return_date' => $window ? $window[1]->toDateString() : '',
                'return_time' => $window ? $window[1]->format('H:i') : '09:00',
            ],
            'carry' => $window ? [
                'pickup_date' => $window[0]->toDateString(),
                'pickup_time' => $window[0]->format('H:i'),
                'return_date' => $window[1]->toDateString(),
                'return_time' => $window[1]->format('H:i'),
            ] : [],
            'dateError' => $dateError,
            'searched' => $q !== '' || $type !== '' || $maxPrice !== null || $window !== null,
        ]);
    }

    private function text(mixed $value, int $max): string
    {
        return is_string($value) ? mb_substr(trim($value), 0, $max) : '';
    }
}