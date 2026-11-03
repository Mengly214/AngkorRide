@extends('admin.layout')

@section('title', 'Overview')

@php
    $money = fn ($n) => '$' . number_format((float) $n, 2);
    $money0 = fn ($n) => '$' . number_format((float) $n, 0);
    $num = fn ($n) => number_format((float) $n, 0);
    $ranges = ['7d' => '7 days', '30d' => '30 days', '12m' => '12 months'];
    $cards = [
        ['Customers', $kpi['customers'], 'group', 'registered accounts'],
        ['Approved businesses', $kpi['businesses'], 'storefront', $kpi['pending_shops'] . ' waiting for review'],
        ['Vehicles for rent', $kpi['vehicles'], 'two_wheeler', 'available and visible to customers'],
        ['Bookings', $kpi['bookings'], 'calendar_today', $kpi['pending_bookings'] . ' pending a shop reply'],
    ];
@endphp

@section('content')
<div class="flex flex-col gap-space-lg">

    @if ($kpi['pending_shops'] > 0)
        <a href="{{ route('admin.businesses.index', ['tab' => 'pending']) }}" class="flex items-center justify-between gap-3 rounded-2xl bg-secondary-container px-5 py-4 text-on-secondary-container hover:brightness-95">
            <span class="flex items-center gap-2 text-label-lg font-semibold"><span class="material-symbols-outlined">pending_actions</span> {{ $kpi['pending_shops'] }} {{ $kpi['pending_shops'] === 1 ? 'shop is' : 'shops are' }} waiting for your review</span>
            <span class="flex items-center gap-1 text-label-md">Review <span class="material-symbols-outlined text-[18px]">arrow_forward</span></span>
        </a>
    @endif

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
        @foreach ($cards as [$label, $value, $icon, $note])
            <div class="rounded-2xl bg-surface-container-lowest p-4 shadow-sm sm:p-5">
                <span class="flex items-center gap-1.5 text-label-sm uppercase text-on-surface-variant"><span class="material-symbols-outlined text-base text-secondary">{{ $icon }}</span> {{ $label }}</span>
                <div class="mt-2 text-display-lg font-extrabold text-on-surface">{{ $num($value) }}</div>
                <p class="mt-1 text-body-sm text-on-surface-variant">{{ $note }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 lg:gap-4">
        <div class="rounded-2xl bg-surface-container-lowest p-4 shadow-sm sm:p-5">
            <span class="text-label-sm uppercase text-on-surface-variant">Gross booking value</span>
            <div class="mt-2 text-headline-lg font-extrabold text-on-surface">{{ $money($kpi['gross']) }}</div>
            <p class="mt-1 text-body-sm text-on-surface-variant">confirmed, ongoing and completed rentals, all time</p>
        </div>
        <div class="rounded-2xl bg-surface-container-lowest p-4 shadow-sm sm:p-5">
            <span class="text-label-sm uppercase text-on-surface-variant">Marked as paid by shops</span>
            <div class="mt-2 text-headline-lg font-extrabold text-secondary">{{ $money($kpi['collected']) }}</div>
            <p class="mt-1 text-body-sm text-on-surface-variant">{{ $kpi['gross'] > 0 ? round($kpi['collected'] / $kpi['gross'] * 100) : 0 }}% of gross value</p>
        </div>
        <div class="rounded-2xl bg-surface-container-lowest p-4 shadow-sm sm:p-5">
            <span class="text-label-sm uppercase text-on-surface-variant">AngkorRide revenue</span>
            <div class="mt-2 text-headline-lg font-extrabold text-on-surface-variant">Not tracked</div>
            <p class="mt-1 text-body-sm text-on-surface-variant">Customers pay shops directly. No commission is recorded yet.</p>
        </div>
    </div>

    {{-- Trends --}}
    <section class="rounded-2xl bg-surface-container-lowest p-5 shadow-sm sm:p-6" aria-labelledby="trendHeading">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <h2 id="trendHeading" class="text-headline-sm text-on-surface">Trends</h2>
            <div class="flex gap-1 rounded-xl bg-surface-container-high/60 p-1">
                @foreach ($ranges as $k => $name)
                    <a href="{{ route('admin.dashboard', ['range' => $k]) }}" @if ($range === $k) aria-current="true" @endif
                       class="rounded-lg px-3 py-1.5 text-label-md {{ $range === $k ? 'bg-primary text-on-primary shadow-sm' : 'bg-surface-container-lowest text-on-surface-variant hover:text-on-surface' }}">{{ $name }}</a>
                @endforeach
            </div>
        </div>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div>
                <div class="mb-2 flex items-baseline justify-between"><h3 class="text-label-lg font-semibold text-on-surface">Bookings made</h3><span class="text-body-sm text-on-surface-variant">{{ $num($charts['total_count']) }} in this period</span></div>
                @include('admin.partials.bars', ['buckets' => $charts['buckets'], 'key' => 'count', 'max' => $charts['max_count'], 'fmt' => fn ($n) => $num($n), 'label' => 'Bookings made'])
            </div>
            <div>
                <div class="mb-2 flex items-baseline justify-between"><h3 class="text-label-lg font-semibold text-on-surface">Booking value</h3><span class="text-body-sm text-on-surface-variant">{{ $money0($charts['total_value']) }} in this period</span></div>
                @include('admin.partials.bars', ['buckets' => $charts['buckets'], 'key' => 'value', 'max' => $charts['max_value'], 'fmt' => fn ($n) => $money0($n), 'label' => 'Booking value', 'tone' => 'bg-primary-container'])
            </div>
        </div>
        <p class="mt-4 text-body-sm text-on-surface-variant">Counted by the day the booking was made. Value excludes pending and cancelled bookings.</p>
    </section>

    <div class="grid grid-cols-1 gap-space-lg xl:grid-cols-2">
        {{-- Pending shops --}}
        <section class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-sm" aria-labelledby="pendHeading">
            <div class="flex items-center justify-between border-b border-surface-container px-5 py-4">
                <h2 id="pendHeading" class="text-headline-sm text-on-surface">Shops to review</h2>
                <a href="{{ route('admin.businesses.index', ['tab' => 'pending']) }}" class="text-label-md font-semibold text-secondary hover:underline">See all</a>
            </div>
            @if ($pending->isEmpty())
                <p class="p-6 text-body-md text-on-surface-variant">Nothing waiting. New shop applications will appear here.</p>
            @else
                <ul class="divide-y divide-surface-container">
                    @foreach ($pending as $b)
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <div class="min-w-0">
                                <div class="truncate font-semibold text-on-surface">{{ $b->name }}</div>
                                <div class="truncate text-body-sm text-on-surface-variant">{{ $b->city }} &bull; {{ $b->owner->full_name ?? '—' }} &bull; {{ $b->vehicles_count }} {{ $b->vehicles_count === 1 ? 'vehicle' : 'vehicles' }} &bull; applied {{ $b->created_at->diffForHumans() }}</div>
                            </div>
                            <a href="{{ route('admin.businesses.show', $b) }}" class="shrink-0 rounded-lg bg-primary px-3 py-1.5 text-label-md text-on-primary hover:opacity-90">Review</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Admin activity --}}
        <section class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-sm" aria-labelledby="actHeading">
            <div class="border-b border-surface-container px-5 py-4"><h2 id="actHeading" class="text-headline-sm text-on-surface">Recent admin activity</h2></div>
            @if ($activity->isEmpty())
                <p class="p-6 text-body-md text-on-surface-variant">Approvals, rejections and suspensions you make will be listed here.</p>
            @else
                <ul class="divide-y divide-surface-container">
                    @foreach ($activity as $a)
                        <li class="px-5 py-3">
                            <div class="text-on-surface">{{ $a->summary }}</div>
                            <div class="text-body-sm text-on-surface-variant">{{ $a->admin->full_name ?? 'Admin' }} &bull; {{ $a->created_at->diffForHumans() }}@if ($a->note) &bull; “{{ \Illuminate\Support\Str::limit($a->note, 70) }}”@endif</div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    {{-- Recent bookings --}}
    <section class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-sm" aria-labelledby="recHeading">
        <div class="flex items-center justify-between border-b border-surface-container px-5 py-4">
            <h2 id="recHeading" class="text-headline-sm text-on-surface">Recent bookings</h2>
            <a href="{{ route('admin.bookings.index') }}" class="text-label-md font-semibold text-secondary hover:underline">See all</a>
        </div>
        @if ($recent->isEmpty())
            <p class="p-6 text-body-md text-on-surface-variant">No bookings yet.</p>
        @else
            <div class="relative overflow-x-auto">
                <table class="w-full text-left text-body-md">
                    <thead class="bg-surface-container-low text-label-sm uppercase text-on-surface-variant">
                        <tr><th class="px-5 py-3">Booking</th><th class="px-3 py-3">Customer</th><th class="px-3 py-3">Vehicle &amp; shop</th><th class="px-3 py-3 text-right">Total</th><th class="px-5 py-3 text-right">Status</th></tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container">
                        @foreach ($recent as $b)
                            <tr>
                                <td class="px-5 py-3"><div class="font-semibold text-on-surface">{{ $b->reference ?? '—' }}</div><div class="text-body-sm text-on-surface-variant">{{ $b->created_at->setTimezone('Asia/Phnom_Penh')->format('M j, H:i') }}</div></td>
                                <td class="px-3 py-3">{{ $b->customer->full_name ?? '—' }}</td>
                                <td class="px-3 py-3"><div>{{ trim(($b->vehicle->brand ?? '') . ' ' . ($b->vehicle->model ?? '')) ?: '—' }}</div><div class="text-body-sm text-on-surface-variant">{{ $b->business->name ?? '' }}</div></td>
                                <td class="px-3 py-3 text-right font-semibold">{{ $money($b->total_price) }}</td>
                                <td class="px-5 py-3 text-right">@include('admin.partials.badge', ['status' => $b->displayStatus()])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection