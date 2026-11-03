<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>Explore vehicles — AngkorRide</title>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">

<style>
    html, body { margin: 0; padding: 0; }
    body { overscroll-behavior: none; }
    ::-webkit-scrollbar { display: none; }
</style>

@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-explore bg-surface font-body-md text-body-md text-on-surface antialiased">

@include('partials.site-header', ['active' => 'explore'])

<main class="w-full pt-20 bg-surface">
@if (session('status'))
    <div role="status" class="max-w-7xl mx-auto px-6 lg:px-12 pt-4"><div class="rounded-xl bg-tertiary-fixed px-4 py-3 text-label-lg text-on-tertiary-fixed">{{ session('status') }}</div></div>
@endif
<div class="flex flex-col w-full">

{{-- Search bar --}}
@php
    $inp = 'w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-body-md text-on-surface focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/30';
    $todayPP = now('Asia/Phnom_Penh')->toDateString();
@endphp
<section class="w-full bg-surface-container-low py-5 shadow-sm">
    <form method="GET" action="{{ route('explore') }}" id="searchForm" class="max-w-7xl mx-auto px-6 lg:px-12 flex flex-col gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-headline-sm text-on-surface">Find a vehicle in Siem Reap</span>
            <span class="px-2 py-0.5 rounded-full bg-secondary-container text-on-secondary-container text-label-sm">Angkor Region</span>
        </div>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-6 lg:grid-cols-12">
            <label class="md:col-span-3 lg:col-span-3">
                <span class="mb-1 block text-label-sm text-on-surface-variant">Location or vehicle</span>
                <input type="search" name="q" value="{{ $filters['q'] }}" maxlength="60" placeholder="e.g. Pub Street, Honda Click" class="{{ $inp }}">
            </label>
            <div class="md:col-span-3 lg:col-span-3">
                <span class="mb-1 block text-label-sm text-on-surface-variant">Pickup</span>
                <div class="grid grid-cols-2 gap-2">
                    <input type="date" name="pickup_date" value="{{ $filters['pickup_date'] }}" min="{{ $todayPP }}" class="{{ $inp }}" aria-label="Pickup date">
                    <input type="time" name="pickup_time" value="{{ $filters['pickup_time'] }}" class="{{ $inp }}" aria-label="Pickup time">
                </div>
            </div>
            <div class="md:col-span-3 lg:col-span-3">
                <span class="mb-1 block text-label-sm text-on-surface-variant">Return</span>
                <div class="grid grid-cols-2 gap-2">
                    <input type="date" name="return_date" value="{{ $filters['return_date'] }}" min="{{ $todayPP }}" class="{{ $inp }}" aria-label="Return date">
                    <input type="time" name="return_time" value="{{ $filters['return_time'] }}" class="{{ $inp }}" aria-label="Return time">
                </div>
            </div>
            <label class="md:col-span-2 lg:col-span-2">
                <span class="mb-1 block text-label-sm text-on-surface-variant">Category</span>
                <select name="type" class="{{ $inp }}">
                    <option value="">All types</option>
                    @foreach ($types as $t)
                        <option value="{{ $t->id }}" @selected($filters['type'] === $t->id)>{{ $t->name }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex items-end md:col-span-1 lg:col-span-1">
                <button type="submit" class="flex w-full items-center justify-center gap-1.5 rounded-xl bg-primary px-4 py-2.5 text-label-md text-on-primary shadow-sm hover:bg-primary/90">
                    <span class="material-symbols-outlined text-base">search</span><span class="lg:hidden xl:inline">Search</span>
                </button>
            </div>
        </div>
        @if ($dateError)
            <p role="alert" class="rounded-lg bg-error-container px-3 py-2 text-body-sm text-on-error-container">{{ $dateError }} Showing all vehicles instead.</p>
        @endif
    </form>
</section>

<div class="max-w-7xl mx-auto px-6 lg:px-12 py-8 w-full">
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

    {{-- Filters (budget is wired to the search form above) --}}
    <aside class="lg:col-span-4 xl:col-span-3 bg-surface-container-lowest p-6 rounded-2xl shadow-sm flex flex-col gap-6 sticky top-28">
        <div class="flex items-center justify-between pb-2 border-b border-surface-container">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary text-xl">tune</span>
                <span class="text-headline-sm text-on-surface">Filters</span>
            </div>
            <a class="text-label-sm text-secondary hover:underline" href="{{ route('explore') }}" id="resetFiltersBtn">Reset All</a>
        </div>

        <div class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <span class="text-label-lg text-on-surface">Daily Budget</span>
                <span class="text-label-md text-secondary font-bold" id="priceDisplay">$6 – ${{ min(35, max(6, (int) ($filters['max_price'] ?? 35))) }} / day</span>
            </div>
            <input class="w-full h-2 bg-surface-container rounded-lg appearance-none cursor-pointer accent-secondary" id="priceRange" name="max_price" form="searchForm" max="35" min="6" type="range" value="{{ min(35, max(6, (int) ($filters['max_price'] ?? 35))) }}">
            <div class="flex justify-between text-on-surface-variant text-body-sm">
                <span>$6 (Economy)</span>
                <span>$35+ (Adventure)</span>
            </div>
        </div>

        <button type="submit" form="searchForm" class="rounded-xl bg-surface-container-low px-3 py-2 text-label-md text-on-surface hover:bg-surface-container-high">Apply filters</button>

    </aside>

    {{-- Catalog results --}}
    <main class="lg:col-span-8 xl:col-span-9 flex flex-col gap-6">

        <div class="bg-surface-container-lowest p-4 rounded-2xl shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2">
                    <span class="text-headline-sm text-on-surface">{{ $vehicles->total() }} Available Vehicles</span>
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                </div>
                <p class="text-body-sm text-on-surface-variant">@if ($carry) Free for your dates &bull; shop confirms after you book @else Prices per day &bull; the shop confirms your booking @endif</p>
            </div>
            <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-end">
                <div class="flex items-center gap-2 bg-surface-container-low px-3 py-2 rounded-xl">
                    <span class="text-label-sm text-on-surface-variant">Sort:</span>
                    <select name="sort" form="searchForm" onchange="document.getElementById('searchForm').submit()" class="bg-transparent text-label-md text-on-surface focus:outline-none cursor-pointer">
                        <option value="recommended" @selected($filters['sort'] === 'recommended')>Recommended</option>
                        <option value="price_asc" @selected($filters['sort'] === 'price_asc')>Price: Low to High</option>
                        <option value="price_desc" @selected($filters['sort'] === 'price_desc')>Price: High to Low</option>
                        <option value="newest" @selected($filters['sort'] === 'newest')>Newest Fleet</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Vehicle cards --}}
        @if ($vehicles->isEmpty())
            <div class="bg-surface-container-lowest p-10 rounded-2xl shadow-sm text-center text-on-surface-variant">
                @if ($searched) No vehicles match your search. Try other dates or <a class="text-secondary underline" href="{{ route('explore') }}">clear the filters</a>. @else No vehicles are available right now — check back soon. @endif
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach ($vehicles as $vehicle)
                    <div class="bg-surface-container-lowest rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-all duration-300 flex flex-col group">
                        <div class="relative w-full aspect-[16/10] overflow-hidden bg-surface-container">
                            @if ($vehicle->photo_url)
                                <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="{{ $vehicle->photo_url }}" alt="{{ $vehicle->brand }} {{ $vehicle->model }}">
                            @endif

                            @if ($vehicle->vehicleType)
                                <div class="absolute top-3 left-3 flex flex-wrap gap-1.5">
                                    <span class="px-2.5 py-1 rounded-full bg-surface-container-lowest/90 backdrop-blur-md text-on-surface text-label-sm font-semibold shadow-sm">
                                        {{ $vehicle->vehicleType->name }}
                                    </span>
                                </div>
                            @endif

                            <div class="absolute top-3 right-3">
                                @include('partials.heart', ['vehicle' => $vehicle, 'saved' => in_array($vehicle->id, $savedIds, true), 'next' => $next])
                            </div>
                        </div>

                        <div class="p-5 flex flex-col flex-1 justify-between gap-4">
                            <div class="flex flex-col gap-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <span class="text-label-sm text-secondary uppercase tracking-wider">{{ $vehicle->color }} &bull; {{ $vehicle->year }}</span>
                                        <h3 class="text-headline-sm text-on-surface group-hover:text-secondary transition-colors">{{ $vehicle->brand }} {{ $vehicle->model }}</h3>
                                    </div>
                                </div>

                                @if ($vehicle->business)
                                    <div class="flex items-center gap-2 text-on-surface-variant text-body-sm">
                                        <span class="material-symbols-outlined text-sm text-secondary">store</span>
                                        <span>{{ $vehicle->business->name }}@if ($vehicle->pick_up_location) &bull; {{ $vehicle->pick_up_location }}@endif</span>
                                    </div>
                                @endif

                                <div class="flex flex-wrap gap-1.5 pt-1">
                                    @if ($vehicle->mileage !== null)
                                        <span class="px-2 py-0.5 rounded-md bg-surface-container-low text-on-surface-variant text-body-sm flex items-center gap-1">
                                            <span class="material-symbols-outlined text-xs text-secondary">speed</span> {{ number_format($vehicle->mileage) }} km
                                        </span>
                                    @endif
                                    @if ($vehicle->condition_notes)
                                        <span class="px-2 py-0.5 rounded-md bg-surface-container-low text-on-surface-variant text-body-sm flex items-center gap-1">
                                            <span class="material-symbols-outlined text-xs text-secondary">check_circle</span> {{ $vehicle->condition_notes }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="pt-3 border-t border-surface-container flex items-center justify-between gap-3">
                                <div class="flex flex-col">
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-headline-lg text-on-surface font-extrabold">${{ number_format($vehicle->price_per_day, 0) }}</span>
                                        <span class="text-body-sm text-on-surface-variant">/ day</span>
                                    </div>
                                </div>
                                <a class="px-4 py-2.5 rounded-xl bg-primary text-on-primary hover:bg-primary/90 transition-all text-label-md flex items-center gap-1.5 shadow-sm"
                                   href="{{ $vehicle->business ? route('vehicles.show', ['business' => $vehicle->business, 'vehicle' => $vehicle] + $carry) : '#' }}">
                                    <span>View Details</span>
                                    <span class="material-symbols-outlined text-base">arrow_forward</span>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($vehicles->hasPages())
            <div class="bg-surface-container-lowest p-4 rounded-2xl shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                <span class="text-body-sm text-on-surface-variant">
                    Showing <span class="font-bold text-on-surface">{{ $vehicles->firstItem() }} - {{ $vehicles->lastItem() }}</span> of {{ $vehicles->total() }} vehicles
                </span>
                <div class="flex items-center gap-2">
                    <a href="{{ $vehicles->previousPageUrl() ?? '#' }}"
                       class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors {{ $vehicles->onFirstPage() ? 'bg-surface-container text-on-surface-variant pointer-events-none' : 'bg-surface-container-low text-on-surface hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-lg">chevron_left</span>
                    </a>
                    @for ($page = 1; $page <= $vehicles->lastPage(); $page++)
                        <a href="{{ $vehicles->url($page) }}"
                           class="w-10 h-10 rounded-xl text-label-md flex items-center justify-center transition-colors {{ $page === $vehicles->currentPage() ? 'bg-secondary text-on-secondary shadow-sm' : 'bg-surface-container-low text-on-surface hover:bg-surface-container-high' }}">
                            {{ $page }}
                        </a>
                    @endfor
                    <a href="{{ $vehicles->nextPageUrl() ?? '#' }}"
                       class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors {{ !$vehicles->hasMorePages() ? 'bg-surface-container text-on-surface-variant pointer-events-none' : 'bg-surface-container-low text-on-surface hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-lg">chevron_right</span>
                    </a>
                </div>
            </div>
        @endif

    </main>
</div>
</div>

</div>

<script>
    const priceRange = document.getElementById('priceRange');
    const priceDisplay = document.getElementById('priceDisplay');
    if (priceRange && priceDisplay) {
        priceRange.addEventListener('input', (e) => {
            priceDisplay.textContent = `$6 – $${e.target.value} / day`;
        });
    }

    if (priceRange) {
        priceRange.addEventListener('change', () => document.getElementById('searchForm').submit());
    }

    // Return date can never be before pickup date
    const pd = document.querySelector('input[name="pickup_date"]');
    const rd = document.querySelector('input[name="return_date"]');
    if (pd && rd) {
        pd.addEventListener('change', () => {
            rd.min = pd.value;
            if (rd.value && rd.value < pd.value) rd.value = pd.value;
        });
    }
</script>

@include('partials.site-footer')

</body>
</html>