@extends('layouts.customer')

@section('title', $business->name)

@php
    $mapUrl = ($business->latitude && $business->longitude)
        ? 'https://www.google.com/maps?q=' . $business->latitude . ',' . $business->longitude
        : (($business->address || $business->city) ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode(trim(($business->name . ' ' . $business->address . ' ' . $business->city))) : null);
    $price = fn ($n) => '$' . rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $freeCancel = \App\Services\BookingWorkflow::FREE_CANCEL_HOURS;
    $holdHours = \App\Services\BookingService::HOLD_MINUTES / 60;
@endphp

@section('content')
<div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-12">
    <nav aria-label="Breadcrumb" class="mb-4 text-body-sm text-on-surface-variant">
        <a href="{{ route('home') }}" class="hover:text-secondary">Home</a> <span class="mx-1">/</span>
        <a href="{{ route('partners.index') }}" class="hover:text-secondary">Partners</a> <span class="mx-1">/</span>
        <span class="text-on-surface">{{ $business->name }}</span>
    </nav>

    {{-- Banner + identity --}}
    <section class="overflow-hidden rounded-3xl bg-surface-container-lowest shadow-sm">
        <div class="relative h-40 overflow-hidden bg-gradient-to-br from-primary-container to-secondary sm:h-56">
            @if ($cover)
                <img src="{{ $cover }}" alt="" class="h-full w-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-primary/50 to-transparent"></div>
            @endif
        </div>
        <div class="relative px-5 pb-6 sm:px-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-start gap-4">
                    <div class="-mt-10 h-20 w-20 shrink-0 overflow-hidden rounded-2xl border-4 border-surface-container-lowest bg-secondary-container shadow-md sm:-mt-12 sm:h-24 sm:w-24">
                        @if ($business->logo_url)
                            <img src="{{ $business->logo_url }}" alt="{{ $business->name }} logo" class="h-full w-full object-cover">
                        @else
                            <span class="flex h-full w-full items-center justify-center text-display-lg font-extrabold text-on-secondary-container">{{ \Illuminate\Support\Str::upper(mb_substr($business->name, 0, 1)) }}</span>
                        @endif
                    </div>
                    <div class="min-w-0 pt-3">
                        <h1 class="text-headline-lg font-extrabold leading-tight text-on-surface sm:text-display-lg">{{ $business->name }}</h1>
                        <p class="flex flex-wrap items-center gap-x-3 gap-y-1 text-body-md text-on-surface-variant">
                            <span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-base">location_on</span>{{ $business->city ?: 'Cambodia' }}</span>
                            @if ($business->is_verified)<span class="inline-flex items-center gap-1 font-semibold text-secondary"><span class="material-symbols-outlined text-base" style="font-variation-settings:'FILL' 1">verified</span> Verified partner</span>@endif
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 sm:pt-3">
                    @if ($business->phone)<a href="tel:{{ $business->phone }}" class="inline-flex items-center gap-1.5 rounded-xl bg-secondary px-4 py-2.5 text-label-lg text-on-secondary hover:bg-on-secondary-container"><span class="material-symbols-outlined text-lg">call</span> Call</a>@endif
                    @if ($mapUrl)<a href="{{ $mapUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-xl bg-surface-container px-4 py-2.5 text-label-lg text-on-surface hover:bg-surface-container-high"><span class="material-symbols-outlined text-lg">map</span> Directions</a>@endif
                </div>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                <div class="rounded-xl bg-surface-container-low px-4 py-3"><div class="text-label-sm uppercase text-on-surface-variant">Vehicles</div><div class="text-headline-sm text-on-surface">{{ $vehicles->count() }} available</div></div>
                <div class="rounded-xl bg-surface-container-low px-4 py-3"><div class="text-label-sm uppercase text-on-surface-variant">From</div><div class="text-headline-sm text-on-surface">{{ $fromPrice !== null ? $price($fromPrice) . ' / day' : '—' }}</div></div>
                <div class="col-span-2 rounded-xl bg-surface-container-low px-4 py-3 sm:col-span-1"><div class="text-label-sm uppercase text-on-surface-variant">Opening hours</div><div class="text-headline-sm text-on-surface">{{ $business->opening_time && $business->closing_time ? $business->opening_time->format('g:i A') . ' – ' . $business->closing_time->format('g:i A') : 'Ask the shop' }}</div></div>
            </div>
        </div>
    </section>

    <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2">
            @if ($business->description)
                <section aria-labelledby="aboutHeading" class="mb-10">
                    <h2 id="aboutHeading" class="text-headline-md text-on-surface">About this shop</h2>
                    <p class="mt-2 whitespace-pre-line text-body-lg leading-relaxed text-on-surface-variant">{{ $business->description }}</p>
                </section>
            @endif

            {{-- Vehicles --}}
            <section aria-labelledby="vehHeading">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="vehHeading" class="text-headline-md text-on-surface">Available vehicles</h2>
                    @if ($types->count() > 1)
                        <div class="flex flex-wrap gap-2" id="typeFilter" role="group" aria-label="Filter by type">
                            <button type="button" data-type="" aria-pressed="true" class="rounded-full bg-primary px-3.5 py-1.5 text-label-md text-on-primary">All {{ $vehicles->count() }}</button>
                            @foreach ($types as $name => $count)
                                <button type="button" data-type="{{ $name }}" aria-pressed="false" class="rounded-full bg-surface-container-lowest px-3.5 py-1.5 text-label-md text-on-surface-variant shadow-sm hover:text-on-surface">{{ $name }} {{ $count }}</button>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($vehicles->isEmpty())
                    <div class="mt-5 rounded-2xl bg-surface-container-lowest p-10 text-center text-on-surface-variant shadow-sm">No vehicles are available right now. Please check back soon.</div>
                @else
                    <div class="mt-5 grid grid-cols-2 gap-3 sm:gap-5 xl:grid-cols-3" id="vehicleGrid">
                        @foreach ($vehicles as $vehicle)
                            <div data-type="{{ $vehicle->vehicleType->name ?? '' }}">
                                @include('partials.vehicle-card', ['vehicle' => $vehicle, 'saved' => in_array($vehicle->id, $savedIds, true), 'next' => $next])
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <aside class="flex flex-col gap-6">
            <section class="rounded-2xl bg-surface-container-lowest p-5 shadow-sm" aria-labelledby="locHeading">
                <h2 id="locHeading" class="text-headline-sm text-on-surface">Location &amp; contact</h2>
                <ul class="mt-3 space-y-3 text-body-md">
                    <li class="flex gap-2"><span class="material-symbols-outlined text-lg text-secondary">location_on</span><span>{{ $business->address ? $business->address . ', ' : '' }}{{ $business->city }}</span></li>
                    @if ($business->phone)<li class="flex gap-2"><span class="material-symbols-outlined text-lg text-secondary">call</span><a href="tel:{{ $business->phone }}" class="hover:underline">{{ $business->phone }}</a></li>@endif
                    @if ($business->email)<li class="flex gap-2"><span class="material-symbols-outlined text-lg text-secondary">mail</span><a href="mailto:{{ $business->email }}" class="break-all hover:underline">{{ $business->email }}</a></li>@endif
                </ul>
                @if ($pickups->isNotEmpty())
                    <div class="mt-4 border-t border-surface-container pt-3"><div class="text-label-sm uppercase text-on-surface-variant">Pickup points</div><ul class="mt-1 list-inside list-disc text-body-md">@foreach ($pickups as $p)<li>{{ $p }}</li>@endforeach</ul></div>
                @endif
                @if ($mapUrl)<a href="{{ $mapUrl }}" target="_blank" rel="noopener" class="mt-4 inline-flex items-center gap-1 text-label-md font-semibold text-secondary hover:underline">Open in Google Maps <span class="material-symbols-outlined text-base">open_in_new</span></a>@endif
            </section>

            <section class="rounded-2xl bg-surface-container-lowest p-5 shadow-sm" aria-labelledby="polHeading">
                <h2 id="polHeading" class="text-headline-sm text-on-surface">How booking works here</h2>
                <ul class="mt-3 space-y-3 text-body-md text-on-surface-variant">
                    <li class="flex gap-2"><span class="material-symbols-outlined text-lg text-secondary">schedule</span><span>Send a request and the shop confirms it. Requests nobody answers expire after {{ $holdHours == (int) $holdHours ? (int) $holdHours : $holdHours }} {{ $holdHours == 1 ? 'hour' : 'hours' }}.</span></li>
                    <li class="flex gap-2"><span class="material-symbols-outlined text-lg text-secondary">payments</span><span>You pay the shop when you pick up the vehicle. No online payment.</span></li>
                    <li class="flex gap-2"><span class="material-symbols-outlined text-lg text-secondary">event_busy</span><span>Cancel free of charge until {{ $freeCancel }} hours before pickup.</span></li>
                    @if ($depositMax)
                        <li class="flex gap-2"><span class="material-symbols-outlined text-lg text-secondary">lock</span><span>Deposit at pickup: {{ $depositMin == $depositMax ? $price($depositMax) : $price($depositMin) . ' – ' . $price($depositMax) }} depending on the vehicle.</span></li>
                    @endif
                </ul>
            </section>
        </aside>
    </div>
</div>

@if ($types->count() > 1)
<script>
(function () {
    var bar = document.getElementById('typeFilter'), grid = document.getElementById('vehicleGrid');
    if (!bar || !grid) return;
    bar.addEventListener('click', function (e) {
        var b = e.target.closest('button[data-type]'); if (!b) return;
        bar.querySelectorAll('button').forEach(function (x) {
            var on = x === b; x.setAttribute('aria-pressed', on ? 'true' : 'false');
            x.className = 'rounded-full px-3.5 py-1.5 text-label-md ' + (on ? 'bg-primary text-on-primary' : 'bg-surface-container-lowest text-on-surface-variant shadow-sm hover:text-on-surface');
        });
        grid.querySelectorAll(':scope > div').forEach(function (d) {
            d.hidden = b.dataset.type !== '' && d.dataset.type !== b.dataset.type;
        });
    });
})();
</script>
@endif
@endsection