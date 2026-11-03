@extends('layouts.customer')

@section('title', 'Rent a bicycle, scooter or motorbike in Cambodia')

@php
    $active = 'home';
    $freeHours = \App\Services\BookingWorkflow::FREE_CANCEL_HOURS;
    $tileInput = 'w-full bg-transparent text-body-lg font-semibold text-on-surface placeholder:text-on-surface-variant/50 focus:outline-none';

    $iconFor = function ($name) {
        $n = mb_strtolower($name);
        return match (true) {
            str_contains($n, 'electric') || str_contains($n, 'e-') => 'electric_bike',
            str_contains($n, 'bicycle') || (str_contains($n, 'bike') && ! str_contains($n, 'motor')) => 'pedal_bike',
            str_contains($n, 'scooter') => 'moped',
            default => 'two_wheeler',
        };
    };
    $tabOn = 'bg-primary text-on-primary shadow-sm';
    $tabOff = 'bg-surface-container-lowest text-on-surface-variant hover:text-on-surface';
    $cityGradients = [
        'linear-gradient(135deg,#0F5961 0%,#159A9C 60%,#72D5D0 100%)',
        'linear-gradient(135deg,#0B2533 0%,#0F5961 70%,#159A9C 100%)',
        'linear-gradient(135deg,#7a4a2a 0%,#d98a4e 65%,#ffd9a0 100%)',
        'linear-gradient(135deg,#1f3a5f 0%,#3d6a9e 70%,#9cc4e4 100%)',
    ];
    $stats = [
        [$totalVehicles, $totalVehicles === 1 ? 'Ride ready to book' : 'Rides ready to book', 'text-secondary'],
        [$totalShops, $totalShops === 1 ? 'Local rental shop' : 'Local rental shops', 'text-on-surface'],
        [$totalCities, $totalCities === 1 ? 'City to explore' : 'Cities to explore', 'text-secondary'],
        [$freeHours . 'h', 'Free cancellation window', 'text-on-surface'],
    ];
@endphp

@section('content')

{{-- ============ Hero with integrated search ============ --}}
<section class="relative -mt-20 w-full overflow-hidden bg-surface-container-low px-4 pb-14 pt-28 sm:px-6 lg:px-12 lg:pb-20">
    <div class="absolute inset-0 z-0" aria-hidden="true">
        @if ($heroPhoto)
            <div class="h-full w-full scale-105 bg-cover bg-center opacity-30" style="background-image:url('{{ $heroPhoto }}')"></div>
        @else
            <svg class="absolute inset-x-0 bottom-0 h-full w-full" viewBox="0 0 1440 600" preserveAspectRatio="xMidYMax slice" focusable="false">
                <g fill="#006a61">
                    <path d="M0 470 C180 420 320 450 500 430 S820 400 1000 440 1300 420 1440 450 V600 H0Z" opacity=".07"/>
                    <path d="M0 505 C220 470 380 495 600 480 S980 455 1180 490 1380 480 1440 495 V600 H0Z" opacity=".09"/>
                    <g opacity=".11">
                        <rect x="800" y="470" width="480" height="14"/><rect x="820" y="458" width="440" height="14"/>
                        <rect x="845" y="446" width="390" height="14"/><rect x="870" y="434" width="340" height="14"/>
                        <path d="M1000 434 V394 L1006 338 q4-16 8 0 L1020 394 V434Z"/><path d="M1060 434 V394 L1066 338 q4-16 8 0 L1080 394 V434Z"/>
                        <path d="M1020 434 V380 Q1040 352 1060 380 V434Z"/><path d="M912 434 V412 L918 372 q4-14 8 0 L932 412 V434Z"/>
                        <path d="M1148 434 V412 L1154 372 q4-14 8 0 L1168 412 V434Z"/><path d="M960 434 V406 L966 366 q4-14 8 0 L980 406 V434Z"/>
                        <path d="M1100 434 V406 L1106 366 q4-14 8 0 L1120 406 V434Z"/><path d="M1030 388 L1040 286 L1050 388Z"/><path d="M1034 300 Q1040 262 1046 300Z"/>
                    </g>
                </g>
            </svg>
        @endif
        <div class="absolute inset-0 bg-gradient-to-b from-surface/90 via-surface/60 to-surface"></div>
        <div class="pointer-events-none absolute -top-24 right-1/4 h-96 w-96 rounded-full bg-secondary-container/40 blur-3xl"></div>
        <div class="pointer-events-none absolute left-4 top-1/2 h-72 w-72 rounded-full bg-secondary/10 blur-2xl"></div>
    </div>

    <div class="relative z-10 mx-auto flex max-w-7xl flex-col items-center text-center">
        <div class="mb-6 inline-flex items-center gap-space-xs rounded-full bg-surface-container-lowest px-4 py-1.5 shadow-sm">
            <span class="text-sm" aria-hidden="true">🇰🇭</span>
            <span class="text-label-sm font-bold uppercase tracking-wide text-secondary">Explore Cambodia on two wheels</span>
        </div>
        <h1 class="max-w-4xl text-[38px] font-extrabold leading-[1.1] tracking-tight text-on-surface sm:text-5xl lg:text-6xl">
            Your journey, <span class="italic text-secondary">your ride.</span>
        </h1>
        <p class="mt-4 max-w-2xl text-body-lg leading-relaxed text-on-surface-variant">
            Discover Cambodia your way. Find the perfect bicycle, scooter or motorbike from local rental shops, with clear prices and no payment until pickup.
        </p>

        @if ($types->isNotEmpty())
            <div class="mt-10 flex flex-wrap items-center justify-center gap-2 rounded-2xl bg-surface-container-highest/60 p-1.5 shadow-sm backdrop-blur-md" role="tablist" aria-label="Vehicle category">
                <button type="button" role="tab" aria-selected="true" data-type="" class="vehicle-tab flex items-center gap-2 rounded-xl px-4 py-2.5 text-label-md transition-all sm:px-5 {{ $tabOn }}">
                    <span class="material-symbols-outlined text-base">directions_bike</span><span>All Vehicles</span>
                </button>
                @foreach ($types as $t)
                    <button type="button" role="tab" aria-selected="false" data-type="{{ $t->id }}" class="vehicle-tab flex items-center gap-2 rounded-xl px-4 py-2.5 text-label-md transition-all sm:px-5 {{ $tabOff }}">
                        <span class="material-symbols-outlined text-base">{{ $iconFor($t->name) }}</span><span>{{ $t->name }}</span>
                    </button>
                @endforeach
            </div>
        @endif

        <form method="GET" action="{{ route('explore') }}" id="heroSearch" class="mt-4 w-full rounded-2xl bg-surface-container-lowest p-4 shadow-xl lg:p-6">
            <div class="grid grid-cols-1 gap-4 text-left md:grid-cols-2 lg:grid-cols-4">
                <label class="flex cursor-text flex-col gap-1 rounded-xl bg-surface-container-low p-3 transition-colors hover:bg-surface-container">
                    <span class="flex items-center gap-1 text-label-sm uppercase text-on-surface-variant"><span class="material-symbols-outlined text-xs text-secondary">location_on</span> Pickup location</span>
                    <input type="search" name="q" maxlength="60" list="cityList" autocomplete="off" placeholder="City, area or shop" class="{{ $tileInput }}">
                    <datalist id="cityList">@foreach ($cities as $c)<option value="{{ $c->city }}">@endforeach</datalist>
                    <span class="text-body-sm font-medium text-secondary">Pick up from the shop you choose</span>
                </label>

                <div class="flex flex-col gap-1 rounded-xl bg-surface-container-low p-3 transition-colors hover:bg-surface-container">
                    <span class="flex items-center gap-1 text-label-sm uppercase text-on-surface-variant"><span class="material-symbols-outlined text-xs text-secondary">calendar_today</span> Pickup</span>
                    <div class="mt-0.5 grid grid-cols-[1fr_auto] items-center gap-2">
                        <input type="date" name="pickup_date" min="{{ $todayPP }}" class="{{ $tileInput }}" aria-label="Pickup date">
                        <input type="time" name="pickup_time" value="09:00" class="w-[6.75rem] bg-transparent text-body-lg font-semibold text-on-surface focus:outline-none" aria-label="Pickup time">
                    </div>
                    <span class="text-body-sm text-on-surface-variant">Choose when you collect it</span>
                </div>

                <div class="flex flex-col gap-1 rounded-xl bg-surface-container-low p-3 transition-colors hover:bg-surface-container">
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1 text-label-sm uppercase text-on-surface-variant"><span class="material-symbols-outlined text-xs text-secondary">event_repeat</span> Return</span>
                        <span id="daysTotal" class="text-label-sm text-on-surface-variant"></span>
                    </div>
                    <div class="mt-0.5 grid grid-cols-[1fr_auto] items-center gap-2">
                        <input type="date" name="return_date" min="{{ $todayPP }}" class="{{ $tileInput }}" aria-label="Return date">
                        <input type="time" name="return_time" value="09:00" class="w-[6.75rem] bg-transparent text-body-lg font-semibold text-on-surface focus:outline-none" aria-label="Return time">
                    </div>
                    <span class="text-body-sm font-medium text-secondary">Free cancellation up to {{ $freeHours }}h</span>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="flex flex-col gap-0.5 rounded-xl bg-surface-container-low px-3 py-2">
                        <span class="text-label-sm uppercase text-on-surface-variant">Vehicle category</span>
                        <select name="type" id="heroType" class="w-full cursor-pointer bg-transparent text-body-lg font-semibold text-on-surface focus:outline-none">
                            <option value="">All available types</option>
                            @foreach ($allTypes as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                        </select>
                    </label>
                    <button type="submit" class="flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-secondary text-headline-sm text-on-secondary shadow-md transition-all hover:bg-on-secondary-container hover:shadow-lg active:scale-[0.99]">
                        <span class="material-symbols-outlined text-xl">search</span><span>Find Your Ride</span>
                    </button>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-center gap-x-8 gap-y-2 pt-4 text-label-md text-on-surface-variant">
                <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-base text-secondary">payments</span>Pay at pickup, no card needed online</span>
                <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-base text-secondary">event_available</span>Free cancellation up to {{ $freeHours }} hours before</span>
                <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-base text-secondary">sell</span>Daily price and deposit shown upfront</span>
            </div>
        </form>
    </div>
</section>

{{-- ============ Quick stats (real numbers) ============ --}}
@if ($totalVehicles > 0)
<section class="w-full bg-surface-container-lowest py-6" aria-label="AngkorRide at a glance">
    <div class="mx-auto grid max-w-7xl grid-cols-2 gap-6 px-4 text-center sm:px-6 md:grid-cols-4 lg:px-12">
        @foreach ($stats as [$num, $label, $tone])
            <div class="flex flex-col items-center">
                <span class="text-display-lg font-extrabold {{ $tone }}">{{ $num }}</span>
                <span class="mt-1 text-label-sm uppercase text-on-surface-variant">{{ $label }}</span>
            </div>
        @endforeach
    </div>
</section>
@endif

{{-- ============ Browse by category ============ --}}
@if ($types->isNotEmpty())
<section class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-12" aria-labelledby="typesHeading">
    <div class="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
            <span class="text-label-sm font-bold uppercase tracking-wider text-secondary">Fleet categories</span>
            <h2 id="typesHeading" class="mt-1 text-display-lg font-extrabold tracking-tight text-on-surface">Explore by vehicle class</h2>
            <p class="mt-1 max-w-2xl text-body-md text-on-surface-variant">Pick the ride that suits your trip, from quiet temple cycling to long touring days.</p>
        </div>
        <a class="flex items-center gap-1 text-label-md font-semibold text-secondary hover:text-on-secondary-container" href="#featured-vehicles">
            See popular rides <span class="material-symbols-outlined text-sm">arrow_forward</span>
        </a>
    </div>
    <div class="grid grid-cols-1 gap-6 {{ [1 => '', 2 => 'md:grid-cols-2', 3 => 'md:grid-cols-3'][min(3, $types->count())] }} {{ $types->count() > 3 ? 'md:grid-cols-2 lg:grid-cols-4' : '' }}">
        @foreach ($types as $t)
            <a href="{{ route('explore', ['type' => $t->id]) }}" class="group relative flex flex-col justify-between overflow-hidden rounded-2xl bg-surface-container-lowest p-5 shadow-sm transition-all duration-300 hover:shadow-xl">
                <div>
                    <div class="relative mb-4 h-56 w-full overflow-hidden rounded-xl bg-surface-container-low">
                        @if ($t->cover)
                            <img src="{{ $t->cover }}" alt="" loading="lazy" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                        @else
                            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-secondary-container/60 to-surface-container text-secondary/60">
                                <span class="material-symbols-outlined text-[72px]">{{ $iconFor($t->name) }}</span>
                            </div>
                        @endif
                        <span class="absolute right-3 top-3 rounded-full bg-secondary-container px-3 py-1 text-label-sm text-on-secondary-container">{{ $t->listed_count }} available</span>
                    </div>
                    <div class="mb-2 flex items-baseline justify-between gap-3">
                        <h3 class="text-headline-md text-on-surface transition-colors group-hover:text-secondary">{{ $t->name }}</h3>
                        <span class="shrink-0 text-headline-sm font-bold text-secondary">From ${{ number_format($t->from_price, 0) }}<span class="text-body-sm font-normal text-on-surface-variant">/day</span></span>
                    </div>
                    @if ($t->description)
                        <p class="text-body-md leading-relaxed text-on-surface-variant">{{ \Illuminate\Support\Str::limit($t->description, 130) }}</p>
                    @endif
                </div>
                <div class="mt-4 flex items-center justify-end pt-2">
                    <span class="inline-flex items-center gap-1 text-label-md font-bold text-secondary transition-transform group-hover:translate-x-1">
                        Explore category <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </span>
                </div>
            </a>
        @endforeach
    </div>
</section>
@endif

{{-- ============ Popular rides ============ --}}
<section class="w-full bg-surface-container-low px-4 py-16 sm:px-6 lg:px-12" id="featured-vehicles" aria-labelledby="popularHeading">
    <div class="mx-auto max-w-7xl">
        <div class="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <div class="inline-flex items-center gap-1 text-label-sm font-bold uppercase tracking-wider text-secondary"><span class="material-symbols-outlined text-sm">stars</span> Ready to book</div>
                <h2 id="popularHeading" class="mt-1 text-display-lg font-extrabold tracking-tight text-on-surface">Popular rides</h2>
                <p class="mt-1 text-body-md text-on-surface-variant">Available now from local rental shops.</p>
            </div>
            @if ($featured->isNotEmpty())
                <a class="group flex items-center gap-1 text-label-md font-bold text-on-surface hover:text-secondary" href="{{ route('explore') }}">
                    View all vehicles <span class="material-symbols-outlined text-sm transition-transform group-hover:translate-x-1">arrow_forward</span>
                </a>
            @endif
        </div>

        @if ($featured->isEmpty())
            <div class="rounded-2xl bg-surface-container-lowest p-10 text-center text-on-surface-variant shadow-sm">No vehicles are listed yet. Check back soon.</div>
        @else
            <div class="grid grid-cols-2 gap-3 sm:gap-6 lg:grid-cols-4">
                @foreach ($featured as $vehicle)
                    @include('partials.vehicle-card', ['vehicle' => $vehicle, 'saved' => in_array($vehicle->id, $savedIds, true), 'next' => $next])
                @endforeach
            </div>
        @endif
    </div>
</section>

{{-- ============ Local partners ============ --}}
@if ($partners->isNotEmpty())
<section class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-12" aria-labelledby="shopsHeading">
    <div class="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
            <div class="inline-flex items-center gap-1 text-label-sm font-bold uppercase tracking-wider text-secondary"><span class="material-symbols-outlined text-sm">storefront</span> Trusted local shops</div>
            <h2 id="shopsHeading" class="mt-1 text-display-lg font-extrabold tracking-tight text-on-surface">Meet our local partners</h2>
            <p class="mt-1 text-body-md text-on-surface-variant">Rent straight from independent shops across Cambodia.</p>
        </div>
        <a class="group flex items-center gap-1 text-label-md font-bold text-on-surface hover:text-secondary" href="{{ route('partners.index') }}">
            View all partners <span class="material-symbols-outlined text-sm transition-transform group-hover:translate-x-1">arrow_forward</span>
        </a>
    </div>
    <div class="-mx-4 flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 pb-3 sm:mx-0 sm:grid sm:grid-cols-2 sm:overflow-visible sm:px-0 sm:pb-0 lg:grid-cols-3 lg:gap-6">
        @foreach ($partners as $business)
            <div class="w-[82%] shrink-0 snap-start sm:w-auto">@include('partials.business-card', ['business' => $business])</div>
        @endforeach
    </div>
</section>
@endif

{{-- ============ How it works ============ --}}
<section id="how-it-works" class="mx-auto w-full max-w-7xl scroll-mt-24 px-4 py-20 sm:px-6 lg:px-12" aria-labelledby="howHeading">
    <div class="mx-auto mb-14 max-w-2xl text-center">
        <span class="text-label-sm font-bold uppercase tracking-wider text-secondary">Simple and clear</span>
        <h2 id="howHeading" class="mt-1 text-display-lg font-extrabold tracking-tight text-on-surface">How AngkorRide works</h2>
        <p class="mt-2 text-body-md text-on-surface-variant">Rent with peace of mind. Real prices, real shops, and nothing to pay until you collect your ride.</p>
    </div>
    <ol class="grid grid-cols-1 gap-8 md:grid-cols-3">
        @foreach ([
            ['1', 'bg-secondary-container text-on-secondary-container', 'Find your ride', 'Search by place and dates, or browse by bicycle, scooter and motorbike. Only vehicles free on your dates are shown, with the daily rate up front.', 'storefront', 'Rides from local shops'],
            ['2', 'bg-primary text-on-primary', 'Book in a minute', 'Send your request and the shop confirms it. No card needed online: you pay when you pick the vehicle up.', 'lock', 'Pay at pickup'],
            ['3', 'bg-secondary text-on-secondary', 'Explore Cambodia', 'Collect your ride, travel at your own pace and return it on time. Plans change? Cancel free up to ' . $freeHours . ' hours before pickup.', 'event_available', 'Free cancellation up to ' . $freeHours . 'h'],
        ] as [$n, $badge, $title, $text, $icon, $foot])
            <li class="relative flex flex-col items-start rounded-2xl bg-surface-container-lowest p-8 shadow-sm transition-all hover:shadow-md">
                <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl text-headline-md font-bold {{ $badge }}">{{ $n }}</div>
                <h3 class="mb-2 text-headline-md text-on-surface">{{ $title }}</h3>
                <p class="mb-6 text-body-md leading-relaxed text-on-surface-variant">{{ $text }}</p>
                <div class="mt-auto flex items-center gap-2 text-label-sm text-secondary"><span class="material-symbols-outlined text-base">{{ $icon }}</span><span>{{ $foot }}</span></div>
            </li>
        @endforeach
    </ol>
</section>

{{-- ============ Destinations ============ --}}
@if ($cities->isNotEmpty())
<section class="w-full bg-surface-container-low px-4 py-16 sm:px-6 lg:px-12" aria-labelledby="placesHeading">
    <div class="mx-auto max-w-7xl">
        <div class="mb-10 flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <span class="text-label-sm font-bold uppercase tracking-wider text-secondary">Explore Cambodia</span>
                <h2 id="placesHeading" class="mt-1 text-display-lg font-extrabold tracking-tight text-on-surface">Two wheels across the kingdom</h2>
                <p class="mt-1 text-body-md text-on-surface-variant">Find rides near the places you want to see.</p>
            </div>
            <div class="inline-flex items-center gap-2 rounded-full bg-surface-container px-3 py-1.5 text-label-sm text-on-surface">
                <span class="h-2 w-2 animate-pulse rounded-full bg-secondary"></span>{{ $totalShops }} partner {{ $totalShops === 1 ? 'shop' : 'shops' }}
            </div>
        </div>
        @php $tiles = $cities->count() < 3 ? $cities->count() + 1 : $cities->count(); @endphp
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 {{ $tiles >= 3 ? 'lg:grid-cols-3' : '' }}">
            @foreach ($cities as $i => $c)
                <a href="{{ route('explore', ['q' => $c->city]) }}" class="group relative h-80 overflow-hidden rounded-2xl shadow-sm transition-all hover:shadow-xl sm:h-96"
                   style="background:{{ $cityGradients[$i % count($cityGradients)] }}">
                    <span class="material-symbols-outlined pointer-events-none absolute right-5 top-5 text-[88px] text-white/15 transition-transform duration-700 group-hover:scale-110">landscape</span>
                    @if ($i === 0)<span class="absolute left-4 top-4 rounded-full bg-secondary px-3 py-1 text-label-sm text-on-secondary">Most rides</span>@endif
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                    <div class="absolute inset-x-6 bottom-6 text-white">
                        <h3 class="text-headline-lg font-bold text-white">{{ $c->city }}</h3>
                        <p class="mt-1 line-clamp-2 text-body-sm text-slate-200">{{ $c->blurb }}</p>
                        <div class="mt-4 flex items-center justify-between border-t border-white/20 pt-3 text-xs">
                            <span class="font-medium text-white/90">{{ $c->total }} {{ (int) $c->total === 1 ? 'ride' : 'rides' }} • from ${{ number_format($c->from_price, 0) }}/day</span>
                            <span class="flex items-center gap-1 font-bold text-secondary-container transition-transform group-hover:translate-x-1">See rides <span class="material-symbols-outlined text-xs">arrow_forward</span></span>
                        </div>
                    </div>
                </a>
            @endforeach
            @if ($cities->count() < 3)
                <div class="flex h-80 flex-col items-center justify-center rounded-2xl border border-dashed border-outline-variant bg-surface-container-lowest/60 p-6 text-center text-on-surface-variant sm:h-96">
                    <span class="material-symbols-outlined text-[36px] text-secondary">add_location_alt</span>
                    <h3 class="mt-2 text-headline-sm text-on-surface">More cities coming</h3>
                    <p class="mt-0.5 max-w-xs text-body-sm">We're adding rental shops across Cambodia.</p>
                </div>
            @endif
        </div>
    </div>
</section>
@endif

{{-- ============ Partner banner ============ --}}
<section id="partners" class="mx-auto w-full max-w-7xl scroll-mt-24 px-4 py-16 sm:px-6 lg:px-12" aria-labelledby="partnerHeading">
    <div class="relative overflow-hidden rounded-3xl bg-primary-container p-8 text-on-primary shadow-xl lg:p-14">
        <div class="pointer-events-none absolute -bottom-20 -right-20 h-96 w-96 rounded-full bg-secondary/20 blur-3xl"></div>
        <div class="pointer-events-none absolute right-1/3 top-0 h-64 w-64 rounded-full bg-secondary-container/10 blur-2xl"></div>
        <div class="relative z-10 grid grid-cols-1 items-center gap-8 lg:grid-cols-12">
            <div class="lg:col-span-8">
                <div class="mb-4 inline-flex items-center gap-1.5 rounded-full bg-surface-container-lowest/15 px-3 py-1 text-label-sm text-secondary-fixed backdrop-blur-md">
                    <span class="material-symbols-outlined text-sm">storefront</span> Partner program
                </div>
                <h2 id="partnerHeading" class="text-display-lg font-extrabold leading-tight tracking-tight text-white sm:text-4xl">Own a vehicle rental shop in Cambodia?</h2>
                <p class="mt-3 max-w-2xl text-body-lg leading-relaxed text-on-primary-container">
                    List your fleet, manage booking requests and keep your calendar under control, while travellers find and book your vehicles online.
                </p>
                <div class="mt-6 flex flex-wrap gap-x-6 gap-y-2 text-sm text-white/90">
                    @foreach (['Your own prices and photos', 'Confirm or reject requests', 'Block days for maintenance'] as $point)
                        <div class="flex items-center gap-2"><span class="material-symbols-outlined text-base text-secondary-fixed">check_circle</span><span>{{ $point }}</span></div>
                    @endforeach
                </div>
                <div class="mt-8 flex flex-wrap gap-4">
                    <a class="flex items-center gap-2 rounded-xl bg-secondary px-6 py-3.5 text-headline-sm font-bold text-on-secondary shadow-lg transition-all hover:bg-secondary-fixed hover:text-on-secondary-fixed" href="{{ route('portal.register') }}">
                        <span>Become a partner</span><span class="material-symbols-outlined text-lg">arrow_forward</span>
                    </a>
                    <a class="flex items-center gap-2 rounded-xl bg-white/10 px-6 py-3.5 text-headline-sm font-semibold text-white backdrop-blur-md transition-colors hover:bg-white/20" href="{{ route('portal.login') }}">
                        <span class="material-symbols-outlined text-lg">login</span><span>Partner sign in</span>
                    </a>
                </div>
            </div>
            <div class="flex justify-center lg:col-span-4">
                <div class="w-full max-w-sm rounded-2xl bg-surface-container-lowest p-6 text-on-surface shadow-2xl">
                    <div class="border-b border-surface-container pb-4">
                        <span class="text-label-sm uppercase text-on-surface-variant">Your shop dashboard</span>
                        <h4 class="text-headline-md font-extrabold text-on-surface">Everything in one place</h4>
                    </div>
                    <div class="space-y-3 py-4 text-body-sm text-on-surface-variant">
                        <div class="flex justify-between gap-3"><span>Bookings</span><span class="text-right font-bold text-on-surface">Confirm in one tap</span></div>
                        <div class="flex justify-between gap-3"><span>Calendar</span><span class="text-right font-bold text-secondary">See every booked day</span></div>
                        <div class="flex justify-between gap-3"><span>Payments</span><span class="text-right font-bold text-on-surface">Customers pay at pickup</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    (function () {
        var form = document.getElementById('heroSearch');
        if (!form) return;
        var pd = form.querySelector('[name="pickup_date"]'), rd = form.querySelector('[name="return_date"]');
        var pt = form.querySelector('[name="pickup_time"]'), rt = form.querySelector('[name="return_time"]');
        var label = document.getElementById('daysTotal');
        var sel = document.getElementById('heroType');
        var on = {{ \Illuminate\Support\Js::from($tabOn) }}.split(' '), off = {{ \Illuminate\Support\Js::from($tabOff) }}.split(' ');

        function days() {
            if (!pd.value || !rd.value) { label.textContent = ''; return; }
            var a = new Date(pd.value + 'T' + (pt.value || '09:00')), b = new Date(rd.value + 'T' + (rt.value || '09:00'));
            var n = Math.max(1, Math.ceil((b - a) / 36e5 / 24));
            label.textContent = isNaN(n) ? '' : n + (n === 1 ? ' day total' : ' days total');
        }

        // Return can never be before pickup; suggest the next day.
        pd.addEventListener('change', function () {
            rd.min = pd.value;
            if (!rd.value || rd.value < pd.value) {
                var d = new Date(pd.value + 'T00:00:00'); d.setDate(d.getDate() + 1);
                rd.value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            }
            days();
        });
        [rd, pt, rt].forEach(function (el) { el.addEventListener('change', days); });

        // Category tabs fill the category select.
        function paint(id) {
            document.querySelectorAll('.vehicle-tab').forEach(function (t) {
                var active = t.getAttribute('data-type') === id;
                t.setAttribute('aria-selected', active ? 'true' : 'false');
                on.concat(off).forEach(function (c) { t.classList.remove(c); });
                (active ? on : off).forEach(function (c) { t.classList.add(c); });
            });
        }
        document.querySelectorAll('.vehicle-tab').forEach(function (t) {
            t.addEventListener('click', function () { var id = t.getAttribute('data-type'); sel.value = id; paint(id); });
        });
        sel.addEventListener('change', function () { paint(sel.value); });
    })();
</script>
@endsection