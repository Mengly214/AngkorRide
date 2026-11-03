@extends('layouts.customer')

@section('title', 'My Bookings')

@php
    $money = fn ($n) => '$' . number_format((float) $n, 2);
    $tz = 'Asia/Phnom_Penh';
    $badge = fn ($label) => match ($label) {
        'Pending' => 'bg-secondary-container text-on-secondary-container',
        'Confirmed' => 'bg-surface-container-high text-on-surface',
        'Ongoing' => 'bg-secondary text-on-secondary',
        'Completed' => 'bg-surface-container text-on-surface',
        default => 'bg-error-container text-on-error-container',
    };
    $statusText = fn ($label) => match ($label) {
        'Pending' => 'AWAITING SHOP',
        'Confirmed' => 'CONFIRMED',
        'Ongoing' => 'ON THE ROAD',
        'Completed' => 'COMPLETED',
        'Rejected' => 'DECLINED',
        'Expired' => 'EXPIRED',
        default => 'CANCELLED',
    };
    $tabs = ['all' => 'All Bookings', 'active' => 'Active', 'upcoming' => 'Upcoming', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
    $days = fn ($b) => max(1, (int) ceil($b->start_datetime->diffInMinutes($b->end_datetime) / 1440));
    $name = fn ($b) => trim($b->vehicle->brand . ' ' . $b->vehicle->model);
    $isCurrent = fn ($label) => in_array($label, ['Pending', 'Confirmed', 'Ongoing'], true);
    $current = $bookings->getCollection()->filter(fn ($b) => $isCurrent($b->displayStatus()));
    $past = $bookings->getCollection()->reject(fn ($b) => $isCurrent($b->displayStatus()));
    $nowLocal = now($tz);
@endphp

@section('content')
<div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-6 py-8 lg:px-12">

    {{-- Title --}}
    <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
        <div class="flex flex-col gap-1">
            <nav class="flex items-center gap-2 text-label-sm uppercase tracking-wider text-on-surface-variant" aria-label="Breadcrumb">
                <a class="transition-colors hover:text-secondary" href="{{ route('home') }}">Home</a>
                <span class="material-symbols-outlined text-xs">chevron_right</span>
                <span class="font-bold text-on-surface">My Bookings</span>
            </nav>
            <h1 class="text-display-lg tracking-tight text-on-surface">My Bookings &amp; Rentals</h1>
            <p class="max-w-2xl text-body-lg text-on-surface-variant">Follow your requests, see what the shop has confirmed, and review your past rentals.</p>
        </div>
        <a href="{{ route('explore') }}" class="flex items-center gap-2 self-start rounded-xl bg-primary px-5 py-3 text-label-lg text-on-primary shadow-md transition-colors hover:bg-on-surface md:self-auto">
            <span class="material-symbols-outlined text-base">add_circle</span><span>Book Another Vehicle</span>
        </a>
    </div>

    {{-- Metrics --}}
    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        @foreach ([
            ['two_wheeler', $counts['active'], 'Active rental', 'bg-secondary-container/40 text-on-secondary-container'],
            ['calendar_month', $counts['upcoming'], 'Upcoming', 'bg-surface-container text-on-surface'],
            ['history', $counts['completed'], 'Completed', 'bg-surface-container text-on-surface'],
            ['event_busy', $counts['cancelled'], 'Cancelled / expired', 'bg-surface-container text-on-surface'],
        ] as [$icon, $n, $text, $tone])
            <div class="flex items-center gap-4 rounded-xl border border-surface-container bg-surface-container-lowest p-5">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $tone }}"><span class="material-symbols-outlined text-2xl">{{ $icon }}</span></div>
                <div class="flex min-w-0 flex-col">
                    <span class="text-headline-lg text-on-surface">{{ $n }}</span>
                    <span class="truncate text-body-sm text-on-surface-variant">{{ $text }}</span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Ongoing rental hero --}}
    @if ($ongoing)
        @php
            $o = $ongoing;
            $startAt = $o->picked_up_at ? $o->picked_up_at->copy()->setTimezone($tz) : \Illuminate\Support\Carbon::parse($o->start_datetime->toDateTimeString(), $tz);
            $endAt = \Illuminate\Support\Carbon::parse($o->end_datetime->toDateTimeString(), $tz);
            $total = max(1, $startAt->diffInMinutes($endAt));
            $pct = (int) max(0, min(100, round($startAt->diffInMinutes($nowLocal, false) / $total * 100)));
            $leftMin = $nowLocal->diffInMinutes($endAt, false);
            $left = $leftMin <= 0 ? 'Return is due now' : ($leftMin >= 1440 ? floor($leftMin / 1440) . ' d ' . floor(($leftMin % 1440) / 60) . ' hrs remaining' : floor($leftMin / 60) . ' hrs ' . ($leftMin % 60) . ' min remaining');
        @endphp
        <section class="flex flex-col gap-3">
            <div class="flex items-center gap-2">
                <span class="relative flex h-3 w-3"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-secondary opacity-75"></span><span class="relative inline-flex h-3 w-3 rounded-full bg-secondary"></span></span>
                <h2 class="text-headline-md tracking-tight text-on-surface">Active Ongoing Rental</h2>
            </div>
            <div class="grid grid-cols-1 overflow-hidden rounded-2xl border border-surface-container bg-surface-container-lowest shadow-md lg:grid-cols-12">
                <div class="relative flex flex-col justify-between gap-4 bg-surface-container-low/40 p-6 lg:col-span-5">
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1 rounded-full bg-surface-container-lowest px-3 py-1 text-label-sm font-bold text-secondary shadow-sm"><span class="material-symbols-outlined text-xs">key</span> IN YOUR HANDS</span>
                        <span class="rounded-lg bg-surface-container-lowest px-2.5 py-1 font-mono text-label-sm text-on-surface shadow-sm">Plate: {{ $o->vehicle->plate_number }}</span>
                    </div>
                    <div class="h-56 w-full overflow-hidden rounded-xl bg-surface-container">
                        @if ($o->vehicle->photo_url)<img class="h-full w-full object-cover" src="{{ $o->vehicle->photo_url }}" alt="{{ $name($o) }}">@else<div class="flex h-full w-full items-center justify-center text-outline"><span class="material-symbols-outlined text-6xl">two_wheeler</span></div>@endif
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-surface-container text-on-surface"><span class="material-symbols-outlined text-base">storefront</span></div>
                        <div class="flex min-w-0 flex-col"><span class="truncate text-label-md text-on-surface">{{ $o->business->name }}</span><span class="truncate text-body-sm text-on-surface-variant">{{ $o->business->address ?: $o->business->city }}</span></div>
                    </div>
                </div>
                <div class="flex flex-col justify-between gap-6 p-6 lg:col-span-7 lg:p-8">
                    <div class="flex flex-col gap-4">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <span class="font-mono text-label-sm uppercase text-on-surface-variant">Ref: {{ $o->reference }}</span>
                                <h3 class="mt-1 text-headline-lg text-on-surface">{{ $name($o) }}</h3>
                            </div>
                            <div class="text-right">
                                <span class="block text-label-sm text-on-surface-variant">Total ({{ $days($o) }} {{ $days($o) === 1 ? 'day' : 'days' }})</span>
                                <span class="text-headline-md font-bold text-on-surface">{{ $money($o->total_price) }}</span>
                                <span class="block text-body-sm text-on-surface-variant">{{ $o->payment_status === 'paid' ? 'Paid' : 'Pay at return' }}</span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-3 rounded-xl bg-surface-container-low p-4">
                            <div class="flex items-center justify-between text-on-surface">
                                <div class="flex items-center gap-2"><span class="material-symbols-outlined text-xl text-secondary">schedule</span><span class="text-label-lg">Return window</span></div>
                                <span class="rounded-full bg-secondary-container/30 px-2.5 py-0.5 text-label-md text-secondary">{{ $left }}</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-surface-container-high"><div class="h-full rounded-full bg-secondary" style="width: {{ $pct }}%"></div></div>
                            <div class="grid grid-cols-2 pt-1 text-body-sm text-on-surface-variant">
                                <div><span class="block font-semibold text-on-surface">Picked up</span><span>{{ $o->start_datetime->format('D, M j · g:i A') }}</span></div>
                                <div class="text-right"><span class="block font-semibold text-on-surface">Due back</span><span>{{ $o->end_datetime->format('D, M j · g:i A') }}</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col items-center gap-3 sm:flex-row">
                        <a href="{{ route('bookings.show', $o) }}" class="flex w-full flex-1 items-center justify-center gap-2 rounded-xl bg-secondary px-4 py-3.5 text-label-lg text-on-secondary shadow-sm hover:opacity-95 sm:w-auto"><span class="material-symbols-outlined text-lg">receipt_long</span><span>View booking details</span></a>
                        @if ($o->business->phone)
                            <a href="tel:{{ $o->business->phone }}" class="flex w-full items-center justify-center gap-2 rounded-xl bg-surface-container px-4 py-3.5 text-label-lg text-on-surface hover:bg-surface-container-high sm:w-auto"><span class="material-symbols-outlined text-lg">call</span><span>Call the shop</span></a>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Tabs + search --}}
    <div class="flex flex-col justify-between gap-4 pt-2 md:flex-row md:items-center">
        <div class="flex items-center gap-2 overflow-x-auto pb-1" role="tablist" aria-label="Filter bookings">
            @foreach ($tabs as $key => $text)
                <a href="{{ route('bookings.index', array_filter(['tab' => $key === 'all' ? null : $key, 'q' => $search ?: null])) }}"
                   class="whitespace-nowrap rounded-full px-4 py-2 text-label-md transition-colors {{ $tab === $key ? 'bg-secondary text-on-secondary shadow-sm' : 'bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container' }}"
                   @if ($tab === $key) aria-current="page" @endif>{{ $text }} ({{ $counts[$key] }})</a>
            @endforeach
        </div>
        <form method="GET" action="{{ route('bookings.index') }}" class="relative md:w-72">
            @if ($tab !== 'all') <input type="hidden" name="tab" value="{{ $tab }}"> @endif
            <span class="material-symbols-outlined absolute left-3 top-3 text-lg text-on-surface-variant">search</span>
            <input type="search" name="q" value="{{ $search }}" placeholder="Search vehicle or AR-000123"
                   class="h-11 w-full rounded-xl bg-surface-container-lowest pl-10 pr-3 text-body-md text-on-surface shadow-sm placeholder:text-on-surface-variant/60 focus:outline-none focus:ring-2 focus:ring-secondary/30">
        </form>
    </div>

    @if (! $ongoing && $current->isEmpty() && $past->isEmpty())
        <div class="rounded-2xl border border-surface-container bg-surface-container-lowest p-10 text-center">
            <span class="material-symbols-outlined text-5xl text-outline">luggage</span>
            <p class="mt-2 text-headline-sm text-on-surface">{{ $search !== '' || $tab !== 'all' ? 'No bookings match this view.' : "You haven't booked anything yet." }}</p>
            <p class="mt-1 text-body-md text-on-surface-variant">{{ $search !== '' || $tab !== 'all' ? 'Try another tab or clear the search.' : 'Find a vehicle and your reservations will show up here.' }}</p>
            <a href="{{ $search !== '' || $tab !== 'all' ? route('bookings.index') : route('explore') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-3 text-label-lg text-on-primary">{{ $search !== '' || $tab !== 'all' ? 'Show all bookings' : 'Explore vehicles' }}</a>
        </div>
    @endif

    {{-- Upcoming / waiting for the shop --}}
    @if ($current->isNotEmpty())
        <section class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <h2 class="text-headline-md tracking-tight text-on-surface">Upcoming &amp; Pending</h2>
                <span class="flex items-center gap-1 text-label-sm text-on-surface-variant"><span class="material-symbols-outlined text-xs text-secondary">event_available</span> Free cancellation until {{ $freeCancelHours }}h before pickup</span>
            </div>
            @foreach ($current as $b)
                @php $label = $b->displayStatus(); @endphp
                <div class="flex flex-col items-start justify-between gap-6 rounded-2xl border border-surface-container bg-surface-container-lowest p-6 lg:flex-row lg:items-center">
                    <div class="flex flex-col items-start gap-5 sm:flex-row sm:items-center">
                        <div class="h-24 w-24 shrink-0 overflow-hidden rounded-xl bg-surface-container sm:h-28 sm:w-28">
                            @if ($b->vehicle->photo_url)<img class="h-full w-full object-cover" src="{{ $b->vehicle->photo_url }}" alt="{{ $name($b) }}">@else<div class="flex h-full w-full items-center justify-center text-outline"><span class="material-symbols-outlined text-4xl">two_wheeler</span></div>@endif
                        </div>
                        <div class="flex min-w-0 flex-col gap-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full px-2.5 py-0.5 text-label-sm font-bold {{ $badge($label) }}">{{ $statusText($label) }}</span>
                                <span class="font-mono text-label-sm text-on-surface-variant">{{ $b->reference }}</span>
                            </div>
                            <h3 class="text-headline-sm text-on-surface">{{ $name($b) }}</h3>
                            <div class="flex flex-col gap-y-1 pt-1 text-body-sm text-on-surface-variant">
                                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm text-secondary">event</span>{{ $b->start_datetime->format('D, M j · g:i A') }} → {{ $b->end_datetime->format('D, M j · g:i A') }} ({{ $days($b) }} {{ $days($b) === 1 ? 'day' : 'days' }})</span>
                                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm text-secondary">pin_drop</span>{{ $b->business->name }} · {{ $b->pickup_location }}</span>
                            </div>
                            @if ($label === 'Pending' && $b->expires_at)
                                <span class="mt-1 text-label-sm text-on-surface-variant">Shop should reply by {{ $b->expires_at->copy()->setTimezone($tz)->format('g:i A, M j') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex w-full flex-row items-center justify-between gap-4 pt-3 lg:w-auto lg:flex-col lg:items-end lg:pt-0">
                        <div class="text-left lg:text-right">
                            <span class="text-headline-md font-bold text-on-surface">{{ $money($b->total_price) }}</span>
                            <span class="flex items-center gap-1 text-label-sm text-secondary lg:justify-end"><span class="material-symbols-outlined text-xs">payments</span> {{ $b->payment_status === 'paid' ? 'Paid' : 'Pay at pickup' }}</span>
                        </div>
                        <a href="{{ route('bookings.show', $b) }}" class="flex items-center gap-1.5 rounded-xl bg-primary px-4 py-2 text-label-md text-on-primary shadow-sm transition-colors hover:bg-on-surface">
                            <span class="material-symbols-outlined text-base">confirmation_number</span><span>View booking</span>
                        </a>
                    </div>
                </div>
            @endforeach
        </section>
    @endif

    {{-- Past --}}
    @if ($past->isNotEmpty())
        <section class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <h2 class="text-headline-md tracking-tight text-on-surface">Past Journeys</h2>
                <span class="text-body-sm text-on-surface-variant">Completed, cancelled and expired</span>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($past as $b)
                    @php $label = $b->displayStatus(); $canRebook = $b->vehicle->status !== 'retired'; @endphp
                    <div class="flex flex-col justify-between gap-4 rounded-xl border border-surface-container bg-surface-container-lowest p-5">
                        <div class="flex flex-col gap-3">
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-label-sm text-on-surface-variant">{{ $b->reference }}</span>
                                <span class="rounded-full px-2 py-0.5 text-label-sm {{ $badge($label) }}">{{ $label }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-surface-container">
                                    @if ($b->vehicle->photo_url)<img class="h-full w-full object-cover" src="{{ $b->vehicle->photo_url }}" alt="{{ $name($b) }}">@else<div class="flex h-full w-full items-center justify-center text-outline"><span class="material-symbols-outlined text-3xl">two_wheeler</span></div>@endif
                                </div>
                                <div class="flex min-w-0 flex-col">
                                    <span class="truncate text-headline-sm text-on-surface">{{ $name($b) }}</span>
                                    <span class="text-body-sm text-on-surface-variant">{{ $b->start_datetime->format('M j') }} – {{ $b->end_datetime->format('M j, Y') }} ({{ $days($b) }} {{ $days($b) === 1 ? 'day' : 'days' }})</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between pt-1">
                                <span class="text-body-sm text-on-surface-variant">{{ $label === 'Completed' ? 'Total paid:' : 'Booking total:' }}</span>
                                <span class="text-label-lg font-bold text-on-surface">{{ $money($b->total_price) }}</span>
                            </div>
                            <div class="flex items-center justify-between rounded-lg bg-surface-container-low p-2.5">
                                <span class="text-body-sm text-on-surface-variant">Shop</span>
                                <span class="truncate text-label-sm text-on-surface">{{ $b->business->name }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 pt-1">
                            <a href="{{ route('bookings.show', $b) }}" class="flex-1 rounded-lg bg-surface-container py-2 text-center text-label-sm text-on-surface transition-colors hover:bg-surface-container-high">View details</a>
                            @if ($canRebook)
                                <a href="{{ route('vehicles.show', [$b->business, $b->vehicle]) }}" class="flex flex-1 items-center justify-center gap-1 rounded-lg bg-secondary py-2 text-label-sm text-on-secondary transition-opacity hover:opacity-90"><span class="material-symbols-outlined text-sm">replay</span> Book again</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($bookings->hasPages())
        <div class="flex items-center justify-between text-body-sm text-on-surface-variant">
            <span>{{ $bookings->firstItem() }}–{{ $bookings->lastItem() }} of {{ $bookings->total() }}</span>
            <div class="flex gap-2">
                @if ($bookings->onFirstPage()) <span class="rounded-lg bg-surface-container px-3 py-1.5 opacity-50">Previous</span> @else <a href="{{ $bookings->previousPageUrl() }}" class="rounded-lg bg-surface-container-low px-3 py-1.5 hover:bg-surface-container-high">Previous</a> @endif
                @if ($bookings->hasMorePages()) <a href="{{ $bookings->nextPageUrl() }}" class="rounded-lg bg-surface-container-low px-3 py-1.5 hover:bg-surface-container-high">Next</a> @else <span class="rounded-lg bg-surface-container px-3 py-1.5 opacity-50">Next</span> @endif
            </div>
        </div>
    @endif

    <div class="flex flex-col items-start justify-between gap-4 rounded-2xl bg-surface-container p-6 md:flex-row md:items-center">
        <div class="flex flex-col gap-1">
            <span class="text-label-sm font-bold uppercase tracking-wider text-secondary">Need help with a booking?</span>
            <p class="text-body-md text-on-surface-variant">Open the booking to call the shop that holds your vehicle, or contact AngkorRide support.</p>
        </div>
        <a href="tel:+85512345678" class="flex items-center gap-2 rounded-xl bg-on-background px-5 py-3 text-label-md text-surface hover:bg-on-surface"><span class="material-symbols-outlined text-lg">support_agent</span><span>+855 12 345 678</span></a>
    </div>
</div>
@endsection