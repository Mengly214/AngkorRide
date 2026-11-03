@extends('portal.layout')

@section('title', $customer->name)

@php
    $money = fn ($n) => '$' . number_format((float) $n, 2);
    $badge = fn ($label) => match ($label) {
        'Pending' => 'bg-secondary-container text-on-secondary-container',
        'Confirmed', 'Ongoing' => 'bg-tertiary-fixed text-on-tertiary-fixed',
        'Completed' => 'bg-surface-container text-on-surface-variant',
        default => 'bg-error-container text-on-error-container',
    };
    $initials = \Illuminate\Support\Str::upper(collect(preg_split('/\s+/u', trim($customer->name)))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->join('')) ?: '?';
@endphp

@section('header-actions')
    <a href="{{ route('portal.customers.index') }}" class="text-label-lg text-secondary hover:underline">&larr; All customers</a>
@endsection

@section('content')
<div class="flex flex-col gap-space-lg">
    <section class="flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-surface-container-lowest p-5 shadow-sm sm:p-6">
        <div class="flex items-center gap-4">
            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-secondary-container text-headline-sm text-on-secondary-container">{{ $initials }}</span>
            <div>
                <h2 class="text-headline-md text-on-surface">{{ $customer->name }}
                    @if ($customer->returning)<span class="ml-1 rounded-full bg-tertiary-fixed px-2.5 py-0.5 align-middle text-label-sm text-on-tertiary-fixed">Returning customer</span>@endif
                </h2>
                <p class="text-body-md text-on-surface-variant">Customer since {{ $customer->first_booking_at->format('M j, Y') }}</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($customer->phone)
                <a href="tel:{{ $customer->phone }}" class="flex items-center gap-1.5 rounded-lg bg-secondary px-4 py-2 text-label-lg text-on-secondary hover:bg-on-secondary-container"><span class="material-symbols-outlined text-[18px]">call</span> {{ $customer->phone }}</a>
            @endif
            @if ($customer->email)
                <a href="mailto:{{ $customer->email }}" class="flex items-center gap-1.5 rounded-lg bg-surface-container px-4 py-2 text-label-lg text-on-surface hover:bg-surface-container-high"><span class="material-symbols-outlined text-[18px]">mail</span> Email</a>
            @endif
        </div>
    </section>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
        @foreach ([
            ['Rentals', $customer->rentals, $customer->bookings . ' ' . ($customer->bookings === 1 ? 'booking' : 'bookings') . ' in total'],
            ['Spent', $money($customer->spent), $money($customer->paid) . ' paid'],
            ['To collect', $money($customer->unpaid), 'rentals not marked paid'],
            ['Cancelled', $customer->cancelled, 'bookings that did not happen'],
        ] as [$label, $value, $note])
            <div class="rounded-2xl bg-surface-container-lowest p-4 shadow-sm sm:p-5">
                <span class="text-label-sm uppercase text-on-surface-variant">{{ $label }}</span>
                <div class="mt-2 text-display-lg font-extrabold text-on-surface">{{ $value }}</div>
                <p class="mt-1 text-body-sm text-on-surface-variant">{{ $note }}</p>
            </div>
        @endforeach
    </div>

    <section class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-sm">
        <div class="border-b border-surface-container px-5 py-4 sm:px-6"><h2 class="text-headline-sm text-on-surface">Booking history</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-body-md">
                <thead class="bg-surface-container-low text-label-sm uppercase text-on-surface-variant">
                    <tr><th class="px-5 py-3 sm:px-6">Booking</th><th class="px-3 py-3">Vehicle</th><th class="px-3 py-3">Dates</th><th class="px-3 py-3 text-right">Total</th><th class="px-5 py-3 text-right sm:px-6">Status</th></tr>
                </thead>
                <tbody class="divide-y divide-surface-container">
                    @foreach ($bookings as $b)
                        @php $label = $b->displayStatus(); @endphp
                        <tr>
                            <td class="px-5 py-3 font-semibold text-on-surface sm:px-6">{{ $b->reference ?? '—' }}</td>
                            <td class="px-3 py-3"><div class="text-on-surface">{{ trim(($b->vehicle->brand ?? '') . ' ' . ($b->vehicle->model ?? '')) ?: '—' }}</div><div class="text-body-sm text-on-surface-variant">{{ $b->vehicle->plate_number ?? '' }}</div></td>
                            <td class="px-3 py-3 text-on-surface-variant">{{ $b->start_datetime->format('M j') }} → {{ $b->end_datetime->format('M j, Y') }}</td>
                            <td class="px-3 py-3 text-right"><div class="font-semibold text-on-surface">{{ $money($b->total_price) }}</div><div class="text-body-sm {{ $b->payment_status === 'paid' ? 'text-secondary' : 'text-on-surface-variant' }}">{{ $b->payment_status === 'paid' ? 'Paid' : 'Unpaid' }}</div></td>
                            <td class="px-5 py-3 text-right sm:px-6"><span class="inline-block rounded-full px-2.5 py-0.5 text-label-sm {{ $badge($label) }}">{{ $label }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection