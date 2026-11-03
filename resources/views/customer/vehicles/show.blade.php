@extends('layouts.customer')

@section('title', trim($vehicle->brand . ' ' . $vehicle->model) ?: 'Vehicle')

@php
    $tz = 'Asia/Phnom_Penh';
    $today = now($tz)->toDateString();
    $tomorrow = now($tz)->addDay()->toDateString();
    $pickupDate = old('pickup_date', request('pickup_date', $today));
    $returnDate = old('return_date', request('return_date', $tomorrow));
    $pickupTime = old('pickup_time', request('pickup_time', '09:00'));
    $returnTime = old('return_time', request('return_time', '09:00'));

    $title = trim($vehicle->brand . ' ' . $vehicle->model);
    $type = $vehicle->vehicleType;
    $pickupPlace = $vehicle->pick_up_location ?: ($business->address ?: $business->city);
    $deposit = (float) $vehicle->deposit_amount;
    $freeCancel = \App\Services\BookingWorkflow::FREE_CANCEL_HOURS;
    $holdHours = intdiv(\App\Services\BookingService::HOLD_MINUTES, 60);

    $gallery = $vehicle->galleryUrls();
    $photoCount = count($gallery);

    $box = 'bg-surface-container-lowest p-3 rounded-xl';
    $input = 'mt-1 w-full bg-transparent text-label-md font-semibold text-on-surface focus:outline-none focus:ring-2 focus:ring-secondary/40 rounded';

    // Spec tiles: only facts we really store
    $specs = array_values(array_filter([
        ['icon' => 'two_wheeler', 'label' => 'Category', 'value' => $type->name ?? '—', 'note' => null],
        ['icon' => 'palette', 'label' => 'Colour', 'value' => $vehicle->color ?: '—', 'note' => null],
        ['icon' => 'event', 'label' => 'Model year', 'value' => $vehicle->year ?: '—', 'note' => null],
        ['icon' => 'speed', 'label' => 'Mileage', 'value' => $vehicle->mileage !== null ? number_format($vehicle->mileage) . ' km' : '—', 'note' => null],
        ['icon' => 'group', 'label' => 'Seats', 'value' => $type ? $type->passenger_capacity . ' ' . ($type->passenger_capacity === 1 ? 'person' : 'people') : '—', 'note' => null],
        ['icon' => 'badge', 'label' => 'Plate', 'value' => $vehicle->plate_number, 'note' => null],
    ]));
@endphp

@section('content')
<section class="max-w-7xl mx-auto w-full px-6 lg:px-12 py-8">

    {{-- Breadcrumb & utility --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <nav class="flex flex-wrap items-center gap-2 text-on-surface-variant text-label-md" aria-label="Breadcrumb">
            <a class="hover:text-secondary transition-colors" href="{{ route('home') }}">Home</a>
            <span class="material-symbols-outlined text-sm text-outline">chevron_right</span>
            <a class="hover:text-secondary transition-colors" href="{{ route('explore', $type ? ['type' => $type->id] : []) }}">{{ $type->name ?? 'Explore Fleets' }}</a>
            <span class="material-symbols-outlined text-sm text-outline">chevron_right</span>
            <span class="text-on-surface font-semibold">{{ $title }}</span>
        </nav>
        <div class="flex items-center gap-2">
            <button type="button" id="shareBtn" class="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-surface-container-low text-on-surface hover:bg-surface-container transition-colors text-label-md">
                <span class="material-symbols-outlined text-base">share</span><span id="shareLabel">Share</span>
            </button>
            @auth
                <form method="POST" action="{{ route('vehicles.favorite', [$business, $vehicle]) }}">
                    @csrf
                    <button type="submit" aria-pressed="{{ $isSaved ? 'true' : 'false' }}" class="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-surface-container-low text-on-surface hover:bg-surface-container transition-colors text-label-md">
                        <span class="material-symbols-outlined text-base text-error" style="font-variation-settings: 'FILL' {{ $isSaved ? 1 : 0 }}">favorite</span><span>{{ $isSaved ? 'Saved' : 'Save' }}</span>
                    </button>
                </form>
            @else
                <a href="{{ route('login', ['next' => request()->getRequestUri()]) }}" title="Sign in to save vehicles" class="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-surface-container-low text-on-surface hover:bg-surface-container transition-colors text-label-md">
                    <span class="material-symbols-outlined text-base text-error">favorite</span><span>Save</span>
                </a>
            @endauth
        </div>
    </div>

    {{-- Title & badges --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
        <div>
            <div class="flex flex-wrap items-center gap-2 mb-2">
                @if ($type)
                    <span class="px-2.5 py-1 rounded-full bg-secondary-container text-on-secondary-container text-label-sm uppercase tracking-wider">{{ $type->name }}</span>
                @endif
                @if ($business->is_verified)
                    <span class="px-2.5 py-1 rounded-full bg-surface-container-high text-on-surface text-label-sm flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs text-secondary">verified</span> Verified shop
                    </span>
                @endif

            </div>
            <h1 class="text-display-lg text-on-surface tracking-tight">{{ $title }}@if ($vehicle->year) ({{ $vehicle->year }})@endif</h1>
            <p class="text-body-md text-on-surface-variant mt-1">
                {{ collect([$type->name ?? null, $vehicle->color, $type ? $type->passenger_capacity . ' seats' : null])->filter()->implode(' · ') }}
                &middot; by {{ $business->name }}
            </p>
        </div>
        <div class="lg:hidden flex items-center gap-3 bg-surface-container-lowest px-4 py-2.5 rounded-2xl border border-surface-container">
            <div class="flex items-baseline gap-1">
                <span class="text-headline-lg font-extrabold text-on-surface">${{ number_format($vehicle->price_per_day, 0) }}</span>
                <span class="text-body-md text-on-surface-variant">/ day</span>
            </div>
            <a href="#bookingForm" class="ml-2 rounded-xl bg-primary px-4 py-2 text-label-md font-bold text-on-primary">Book now</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        {{-- LEFT --}}
        <div class="lg:col-span-7 flex flex-col gap-8">

            {{-- Photo gallery --}}
            <div class="flex flex-col gap-4">
                <div class="relative w-full aspect-[16/10] rounded-2xl overflow-hidden bg-surface-container-low group">
                    @if ($photoCount)
                        <img id="mainImage" class="w-full h-full object-cover" src="{{ $gallery[0] }}" alt="{{ $title }}">
                        @if ($photoCount > 1)
                            <button type="button" id="prevPhoto" aria-label="Previous photo" class="absolute left-3 top-1/2 -translate-y-1/2 flex h-10 w-10 items-center justify-center rounded-full bg-surface/90 text-on-surface shadow-sm backdrop-blur hover:bg-surface">
                                <span class="material-symbols-outlined">chevron_left</span>
                            </button>
                            <button type="button" id="nextPhoto" aria-label="Next photo" class="absolute right-3 top-1/2 -translate-y-1/2 flex h-10 w-10 items-center justify-center rounded-full bg-surface/90 text-on-surface shadow-sm backdrop-blur hover:bg-surface">
                                <span class="material-symbols-outlined">chevron_right</span>
                            </button>
                            <div class="absolute bottom-4 right-4 flex items-center gap-1.5 rounded-xl bg-surface/90 px-3 py-1.5 text-label-sm text-on-surface shadow-sm backdrop-blur">
                                <span class="material-symbols-outlined text-sm text-secondary">photo_library</span>
                                <span id="photoCounter">1 / {{ $photoCount }}</span>
                            </div>
                        @endif
                    @else
                        <div class="flex h-full w-full flex-col items-center justify-center gap-2 text-outline">
                            <span class="material-symbols-outlined text-[72px]">two_wheeler</span>
                            <span class="text-body-sm">No photo yet</span>
                        </div>
                    @endif
                </div>

                @if ($photoCount > 1)
                    <div class="grid grid-cols-4 gap-3" id="thumbs">
                        @foreach ($gallery as $i => $url)
                            <button type="button" data-index="{{ $i }}" aria-label="Show photo {{ $i + 1 }}" aria-pressed="{{ $i === 0 ? 'true' : 'false' }}"
                                    class="thumb-btn rounded-xl overflow-hidden aspect-[4/3] bg-surface-container-low transition-all ring-secondary {{ $i === 0 ? 'ring-2' : 'ring-0 opacity-80 hover:opacity-100 hover:ring-2 hover:ring-secondary/50' }}">
                                <img class="w-full h-full object-cover" src="{{ $url }}" alt="{{ $title }} photo {{ $i + 1 }}" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Specifications --}}
            <div class="bg-surface-container-lowest rounded-2xl p-6 border border-surface-container">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-headline-md text-on-surface">Vehicle details</h2>
                    @if ($vehicle->year)
                        <span class="text-label-sm text-secondary flex items-center gap-1"><span class="material-symbols-outlined text-sm">tune</span> {{ $vehicle->year }} model</span>
                    @endif
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    @foreach ($specs as $spec)
                        <div class="p-4 rounded-xl bg-surface-container-low flex flex-col gap-1">
                            <div class="flex items-center gap-2 text-secondary">
                                <span class="material-symbols-outlined text-xl">{{ $spec['icon'] }}</span>
                                <span class="text-label-sm text-on-surface-variant uppercase">{{ $spec['label'] }}</span>
                            </div>
                            <span class="text-label-lg text-on-surface mt-1 break-words">{{ $spec['value'] }}</span>
                        </div>
                    @endforeach
                </div>
                @if ($vehicle->condition_notes)
                    <div class="mt-4 flex items-start gap-3 rounded-xl bg-surface-container-low p-4">
                        <span class="material-symbols-outlined text-secondary">fact_check</span>
                        <div>
                            <p class="text-label-lg text-on-surface">Condition</p>
                            <p class="text-body-sm text-on-surface-variant">{{ $vehicle->condition_notes }}</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Shop --}}
            <div class="bg-surface-container-lowest rounded-2xl p-6 border border-surface-container">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-surface-container">
                    <div class="flex items-center gap-4 min-w-0">
                        <div class="w-16 h-16 rounded-2xl bg-secondary-container text-on-secondary-container flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-3xl">storefront</span>
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-headline-sm text-on-surface">{{ $business->name }}</h3>
                                @if ($business->is_verified)
                                    <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full bg-secondary-container text-on-secondary-container text-label-sm">
                                        <span class="material-symbols-outlined text-xs">verified</span> Verified shop
                                    </span>
                                @endif
                            </div>
                            <p class="text-body-sm text-on-surface-variant flex items-center gap-1 mt-0.5">
                                <span class="material-symbols-outlined text-sm text-secondary">location_on</span>
                                <span class="truncate">{{ $business->address ?: $business->city }}</span>
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('business.show', $business) }}" class="shrink-0 rounded-xl bg-surface-container-low px-4 py-2.5 text-label-md text-on-surface hover:bg-surface-container-high text-center">See all vehicles</a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-5">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-secondary">timer</span>
                        <div><p class="text-label-sm text-on-surface-variant">Shop answers within</p><p class="text-label-md text-on-surface">{{ $holdHours }} hours</p></div>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-secondary">two_wheeler</span>
                        <div><p class="text-label-sm text-on-surface-variant">Vehicles listed</p><p class="text-label-md text-on-surface">{{ $business->vehicles()->where('status', '!=', 'retired')->count() }}</p></div>
                    </div>
                    @if ($business->phone)
                        <a href="tel:{{ $business->phone }}" class="flex items-center gap-2.5 hover:text-secondary">
                            <span class="material-symbols-outlined text-secondary">call</span>
                            <div><p class="text-label-sm text-on-surface-variant">Call the shop</p><p class="text-label-md text-on-surface">{{ $business->phone }}</p></div>
                        </a>
                    @endif
                </div>
            </div>

            {{-- Policies --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-surface-container-lowest rounded-2xl p-6 border border-surface-container flex flex-col gap-2">
                    <div class="flex items-center gap-2 text-secondary"><span class="material-symbols-outlined">badge</span><h3 class="text-headline-sm text-on-surface">Licence &amp; ID</h3></div>
                    <p class="text-body-sm text-on-surface-variant leading-relaxed">
                        Bring your passport or national ID to the shop.
                        @if ($type && $type->requires_license) A valid driving licence is required for this vehicle. @else No driving licence is required for this category, but a valid ID is. @endif
                    </p>
                </div>
                <div class="bg-surface-container-lowest rounded-2xl p-6 border border-surface-container flex flex-col gap-2">
                    <div class="flex items-center gap-2 text-secondary"><span class="material-symbols-outlined">shield</span><h3 class="text-headline-sm text-on-surface">Deposit</h3></div>
                    <p class="text-body-sm text-on-surface-variant leading-relaxed">
                        @if ($deposit > 0) A refundable deposit of ${{ number_format($deposit, 2) }} is paid to the shop at pickup and returned when the vehicle comes back undamaged. @else No deposit is required for this vehicle. @endif
                    </p>
                </div>
                <div class="bg-surface-container-lowest rounded-2xl p-6 border border-surface-container flex flex-col gap-2">
                    <div class="flex items-center gap-2 text-secondary"><span class="material-symbols-outlined">event_busy</span><h3 class="text-headline-sm text-on-surface">Cancellation</h3></div>
                    <p class="text-body-sm text-on-surface-variant leading-relaxed">
                        Free cancellation until {{ $freeCancel }} hours before pickup. The shop confirms your request; if it doesn't answer within {{ $holdHours }} hours, the request expires and nothing is charged.
                    </p>
                </div>
            </div>
        </div>

        {{-- RIGHT: sticky booking panel --}}
        <aside class="lg:col-span-5 lg:sticky lg:top-24">
            <form method="GET" action="{{ route('bookings.checkout', [$business, $vehicle]) }}" id="bookingForm" novalidate
                  data-quote-url="{{ route('vehicles.quote', [$business, $vehicle]) }}"
                  data-rate="{{ (float) $vehicle->price_per_day }}"
                  class="bg-surface-container-lowest rounded-3xl p-6 sm:p-7 border border-surface-container shadow-lg flex flex-col gap-6">

                <div class="flex items-baseline justify-between">
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-display-lg font-extrabold text-on-surface">${{ number_format($vehicle->price_per_day, 0) }}</span>
                        <span class="text-body-md text-on-surface-variant">/ day</span>
                    </div>
                    <span class="text-label-sm text-secondary font-bold uppercase tracking-wider">Pay at pickup</span>
                </div>

                @unless ($bookable)
                    <div class="rounded-xl bg-error-container px-4 py-3 text-body-md text-on-error-container">This vehicle is not available for booking right now.</div>
                @endunless

                @if ($errors->any())
                    <div role="alert" class="rounded-xl bg-error-container px-4 py-3 text-body-sm text-on-error-container">{{ $errors->first() }}</div>
                @endif

                <div class="bg-surface-container-low p-3 rounded-2xl flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-secondary text-xl">storefront</span>
                    <p class="text-body-sm text-on-surface"><strong class="font-semibold">Pickup at the shop:</strong> {{ $pickupPlace }}</p>
                </div>

                <fieldset @disabled(! $bookable) class="contents">
                    <div class="bg-surface-container-low p-4 rounded-2xl flex flex-col gap-3">
                        <div class="flex items-center justify-between">
                            <span class="text-label-sm uppercase font-bold text-on-surface-variant">Rental window</span>
                            <span class="px-2 py-0.5 rounded-full bg-secondary-container text-on-secondary-container text-label-sm font-bold" id="rentalDaysBadge">&mdash;</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="{{ $box }}">
                                <span class="block text-label-sm text-on-surface-variant">Pick-up date</span>
                                <input type="date" name="pickup_date" id="pickup_date" value="{{ $pickupDate }}" min="{{ $today }}" required class="{{ $input }}">
                            </label>
                            <label class="{{ $box }}">
                                <span class="block text-label-sm text-on-surface-variant">Pick-up time</span>
                                <input type="time" name="pickup_time" id="pickup_time" value="{{ $pickupTime }}" class="{{ $input }}">
                            </label>
                            <label class="{{ $box }}">
                                <span class="block text-label-sm text-on-surface-variant">Return date</span>
                                <input type="date" name="return_date" id="return_date" value="{{ $returnDate }}" min="{{ $today }}" required class="{{ $input }}">
                            </label>
                            <label class="{{ $box }}">
                                <span class="block text-label-sm text-on-surface-variant">Return time</span>
                                <input type="time" name="return_time" id="return_time" value="{{ $returnTime }}" class="{{ $input }}">
                            </label>
                        </div>
                    </div>

                    {{-- Price breakdown (filled by the live quote) --}}
                    <div class="bg-surface-container-low/60 rounded-2xl p-4 flex flex-col gap-2.5" aria-live="polite">
                        <div id="quoteMsg" class="text-body-sm text-on-surface-variant">Choose your dates to see the price.</div>
                        <div id="quoteRows" class="hidden flex-col gap-2.5">
                            <div class="flex items-center justify-between text-body-sm text-on-surface-variant">
                                <span id="rowRentalLabel">Rental</span><span class="text-on-surface font-medium" id="rowRental">&mdash;</span>
                            </div>
                            <div class="flex items-center justify-between text-body-sm text-on-surface-variant">
                                <span>Deposit (refundable, at pickup)</span>
                                <span class="text-on-surface font-medium" id="rowDeposit">@if ($deposit > 0) ${{ number_format($deposit, 2) }} @else None @endif</span>
                            </div>
                            <div class="text-body-sm text-on-surface-variant" id="rowDates"></div>
                            <div class="h-px bg-surface-container-high my-1"></div>
                            <div class="flex items-baseline justify-between">
                                <div>
                                    <span class="text-headline-sm text-on-surface font-bold">Total</span>
                                    <span class="block text-label-sm text-on-surface-variant">Pay the shop at pickup</span>
                                </div>
                                <span class="text-headline-lg text-secondary font-extrabold" id="totalAmount">&mdash;</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3">
                        <button type="submit" id="submitBtn"
                                class="w-full h-14 rounded-2xl bg-primary text-on-primary text-headline-sm font-bold flex items-center justify-center gap-2 hover:bg-primary/90 active:scale-[0.99] transition-all shadow-md disabled:cursor-not-allowed disabled:opacity-50">
                            <span>Proceed to book</span><span class="material-symbols-outlined text-xl">arrow_forward</span>
                        </button>
                        <div class="flex items-center justify-center gap-2 text-on-surface-variant text-label-sm text-center">
                            <span class="material-symbols-outlined text-sm text-secondary">verified_user</span>
                            <span>Free cancellation up to {{ $freeCancel }} hrs before pickup</span>
                        </div>
                    </div>
                </fieldset>

                <div class="grid grid-cols-2 gap-2 pt-2 border-t border-surface-container">
                    <div class="flex items-center gap-2 text-on-surface-variant text-body-sm"><span class="material-symbols-outlined text-base text-secondary">payments</span><span>Pay cash on arrival</span></div>
                    <div class="flex items-center gap-2 text-outline text-body-sm"><span class="material-symbols-outlined text-base">credit_card</span><span>Online pay &middot; soon</span></div>
                </div>
            </form>
        </aside>
    </div>
</section>

{{-- Mobile bottom booking bar (hidden while the booking panel is on screen) --}}
<div id="mobileBar" class="lg:hidden fixed inset-x-0 bottom-0 z-40 border-t border-surface-container bg-surface-container-lowest/95 backdrop-blur px-4 py-3 flex items-center justify-between gap-4 transition-transform">
    <div>
        <span class="text-headline-sm font-extrabold text-on-surface">${{ number_format($vehicle->price_per_day, 0) }}</span>
        <span class="text-body-sm text-on-surface-variant">/ day</span>
    </div>
    <a href="#bookingForm" class="rounded-xl bg-primary px-6 py-2.5 text-label-lg font-bold text-on-primary">{{ $bookable ? 'Book now' : 'Unavailable' }}</a>
</div>
<div class="h-20 lg:hidden"></div>

<script>
(function () {

    // Photo gallery
    var gallery = @json($gallery), cur = 0;
    var main = document.getElementById('mainImage'), counter = document.getElementById('photoCounter');
    var thumbs = Array.prototype.slice.call(document.querySelectorAll('.thumb-btn'));
    function show(i) {
        if (!main || !gallery.length) return;
        cur = (i + gallery.length) % gallery.length;
        main.src = gallery[cur];
        if (counter) counter.textContent = (cur + 1) + ' / ' + gallery.length;
        thumbs.forEach(function (b, n) {
            var on = n === cur;
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
            b.classList.toggle('ring-2', on); b.classList.toggle('ring-0', !on);
            b.classList.toggle('opacity-80', !on);
        });
    }
    thumbs.forEach(function (b) { b.addEventListener('click', function () { show(parseInt(b.dataset.index, 10)); }); });
    var pv = document.getElementById('prevPhoto'), nx = document.getElementById('nextPhoto');
    if (pv) pv.addEventListener('click', function () { show(cur - 1); });
    if (nx) nx.addEventListener('click', function () { show(cur + 1); });
    if (main) {
        var x0 = null;
        main.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
        main.addEventListener('touchend', function (e) {
            if (x0 === null) return; var dx = e.changedTouches[0].clientX - x0; x0 = null;
            if (Math.abs(dx) > 40) show(cur + (dx < 0 ? 1 : -1));
        });
    }

    // Share / Save
    var share = document.getElementById('shareBtn'), label = document.getElementById('shareLabel');
    if (share) share.addEventListener('click', function () {
        var data = { title: document.title, url: location.href.split('?')[0] };
        if (navigator.share) { navigator.share(data).catch(function () {}); return; }
        if (navigator.clipboard) navigator.clipboard.writeText(data.url).then(function () {
            label.textContent = 'Link copied'; setTimeout(function () { label.textContent = 'Share'; }, 1800);
        });
    });

    // Hide the bottom bar while the booking panel is visible
    var bar = document.getElementById('mobileBar'), panel = document.getElementById('bookingForm');
    if (bar && panel && 'IntersectionObserver' in window) {
        new IntersectionObserver(function (e) {
            bar.style.transform = e[0].isIntersecting ? 'translateY(110%)' : 'translateY(0)';
        }, { threshold: 0.15 }).observe(panel);
    }

    // Live price
    var form = document.getElementById('bookingForm');
    if (!form) return;
    var ids = ['pickup_date', 'pickup_time', 'return_date', 'return_time'];
    var msg = document.getElementById('quoteMsg'), rows = document.getElementById('quoteRows');
    var badge = document.getElementById('rentalDaysBadge'), btn = document.getElementById('submitBtn');
    var timer = null, seq = 0;

    function money(n) { return '$' + Number(n).toFixed(2); }
    function note(text) { rows.classList.add('hidden'); rows.classList.remove('flex'); msg.classList.remove('hidden'); msg.textContent = text; badge.textContent = '—'; }

    function update() {
        var p = new URLSearchParams();
        ids.forEach(function (id) { p.set(id, document.getElementById(id).value); });
        if (!p.get('pickup_date') || !p.get('return_date')) { note('Choose your dates to see the price.'); return; }
        var mine = ++seq;
        fetch(form.dataset.quoteUrl + '?' + p.toString(), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (q) {
                if (mine !== seq) return;
                if (!q.ok) { note(q.message || 'Those dates are not available.'); btn.disabled = true; return; }
                btn.disabled = false;
                msg.classList.add('hidden'); rows.classList.remove('hidden'); rows.classList.add('flex');
                badge.textContent = q.days + (q.days === 1 ? ' day' : ' days');
                document.getElementById('rowRentalLabel').textContent = 'Rental ($' + Number(q.rate).toFixed(0) + ' × ' + q.days + (q.days === 1 ? ' day)' : ' days)');
                document.getElementById('rowRental').textContent = money(q.subtotal);
                document.getElementById('rowDeposit').textContent = Number(q.deposit) > 0 ? money(q.deposit) : 'None';
                document.getElementById('rowDates').textContent = q.pickup + ' → ' + q.return;
                document.getElementById('totalAmount').textContent = money(q.total);
            })
            .catch(function () { if (mine === seq) note('Could not check the price. You can still continue.'); });
    }
    function schedule() { clearTimeout(timer); timer = setTimeout(update, 250); }
    ids.forEach(function (id) { document.getElementById(id).addEventListener('change', schedule); });
    document.getElementById('pickup_date').addEventListener('change', function () {
        var r = document.getElementById('return_date'); r.min = this.value;
        if (r.value && r.value < this.value) r.value = this.value;
    });
    update();
})();
</script>
@endsection