@extends('portal.layout')

@section('title', 'Bookings')

@php
    $tabs = ['all' => 'All', 'pending' => 'Needs action', 'confirmed' => 'Confirmed', 'ongoing' => 'Ongoing', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
    $money = fn ($n) => '$' . number_format((float) $n, 2);
    $badge = fn ($label) => match ($label) {
        'Pending' => 'bg-secondary-container text-on-secondary-container',
        'Confirmed', 'Ongoing' => 'bg-tertiary-fixed text-on-tertiary-fixed',
        'Completed' => 'bg-surface-container text-on-surface-variant',
        default => 'bg-error-container text-on-error-container',
    };
    $btn = 'rounded-lg px-3 py-1.5 text-label-md font-semibold transition-colors';
@endphp

@section('header-actions')
    <form method="GET" action="{{ route('portal.bookings.index') }}" class="flex items-center gap-2">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="search" name="q" value="{{ $search }}" maxlength="60" placeholder="Search ID, name or phone"
               class="w-56 rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2 text-body-md focus:border-secondary focus:outline-none">
        <button class="rounded-lg bg-primary px-3 py-2 text-label-md text-on-primary">Search</button>
    </form>
@endsection

@section('content')
    <nav class="flex flex-wrap gap-2" aria-label="Booking status">
        @foreach ($tabs as $key => $name)
            <a href="{{ route('portal.bookings.index', ['tab' => $key] + ($search !== '' ? ['q' => $search] : [])) }}"
               @if ($tab === $key) aria-current="page" @endif
               class="flex items-center gap-1.5 rounded-full px-3 py-1.5 text-label-md transition-colors {{ $tab === $key ? 'bg-primary-container text-surface-container-lowest' : 'bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container-high' }}">
                {{ $name }}
                <span class="rounded-full px-1.5 text-label-sm {{ $tab === $key ? 'bg-inverse-surface' : 'bg-surface-container' }}">{{ $counts[$key] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-sm">
        @if ($bookings->isEmpty())
            <div class="p-10 text-center text-body-md text-on-surface-variant">
                @if ($search !== '' || $tab !== 'all') No bookings match this view. @else No bookings yet. They appear here as soon as a customer books one of your vehicles. @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-left text-body-md">
                    <thead class="bg-surface-container-low text-label-sm uppercase tracking-wider text-on-surface-variant">
                        <tr>
                            <th class="px-4 py-3">Booking</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Vehicle &amp; dates</th>
                            <th class="px-4 py-3">Amount</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container align-top">
                        @foreach ($bookings as $b)
                            @php $label = $b->displayStatus(); @endphp
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-on-surface">{{ $b->reference ?? '—' }}</div>
                                    <div class="text-body-sm text-on-surface-variant">{{ $b->created_at->copy()->setTimezone('Asia/Phnom_Penh')->format('M j, g:i A') }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-on-surface">{{ $b->customer->full_name ?? '—' }}</div>
                                    <a href="tel:{{ $b->customer->phone ?? '' }}" class="text-body-sm text-secondary hover:underline">{{ $b->customer->phone ?? '' }}</a>
                                    @if ($b->customer_notes)
                                        <div class="mt-1 max-w-[220px] text-body-sm text-on-surface-variant">“{{ $b->customer_notes }}”</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-on-surface">{{ $b->vehicle->brand ?? '' }} {{ $b->vehicle->model ?? '' }}</div>
                                    <div class="text-body-sm text-on-surface-variant">{{ $b->vehicle->plate_number ?? '' }}</div>
                                    <div class="mt-1 text-body-sm text-on-surface-variant">{{ $b->start_datetime->format('M j, g:i A') }} → {{ $b->end_datetime->format('M j, g:i A') }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-on-surface">{{ $money($b->total_price) }}</div>
                                    @if ((float) $b->discount_amount > 0)<div class="text-body-sm text-secondary">Voucher {{ $b->voucher_code }} −{{ $money($b->discount_amount) }}</div>@endif
                                    @if ((float) $b->deposit_amount > 0)<div class="text-body-sm text-on-surface-variant">Deposit {{ $money($b->deposit_amount) }}</div>@endif
                                    <div class="text-body-sm {{ $b->payment_status === 'paid' ? 'text-secondary' : 'text-on-surface-variant' }}">{{ $b->payment_status === 'paid' ? ($b->payment_method === 'online' ? 'Paid online · AngkorRide holds it' : 'Paid') : 'Unpaid · pay at pickup' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-block rounded-full px-2.5 py-0.5 text-label-sm {{ $badge($label) }}">{{ $label }}</span>
                                    @if ($label === 'Pending' && $b->expires_at)
                                        <div class="mt-1 text-body-sm text-on-surface-variant">Answer by {{ $b->expires_at->copy()->setTimezone('Asia/Phnom_Penh')->format('g:i A') }}</div>
                                    @endif
                                    @if ($b->status === 'cancelled' && $b->cancellation_reason)
                                        <div class="mt-1 max-w-[180px] text-body-sm text-on-surface-variant">{{ $b->cancellation_reason }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-start gap-2">
                                        @if ($b->status === 'pending')
                                            <form method="POST" action="{{ route('portal.bookings.action', [$b, 'confirm']) }}">@csrf
                                                <button class="{{ $btn }} bg-primary text-on-primary hover:bg-primary/90">Confirm</button>
                                            </form>
                                            <details class="relative">
                                                <summary class="{{ $btn }} cursor-pointer list-none bg-error-container text-on-error-container [&::-webkit-details-marker]:hidden">Reject</summary>
                                                <form method="POST" action="{{ route('portal.bookings.action', [$b, 'reject']) }}" class="absolute right-0 z-10 mt-1 flex w-60 flex-col gap-2 rounded-xl bg-surface-container-lowest p-3 shadow-lg ring-1 ring-surface-container">@csrf
                                                    <input type="text" name="reason" maxlength="200" placeholder="Reason (optional)" class="rounded-lg border border-outline-variant px-2 py-1.5 text-body-sm">
                                                    <button class="{{ $btn }} bg-error text-on-error">Reject booking</button>
                                                </form>
                                            </details>
                                        @endif

                                        @if ($b->status === 'confirmed')
                                            <form method="POST" action="{{ route('portal.bookings.action', [$b, 'pickup']) }}">@csrf
                                                <button class="{{ $btn }} bg-primary text-on-primary hover:bg-primary/90">Hand over</button>
                                            </form>
                                            <details class="relative">
                                                <summary class="{{ $btn }} cursor-pointer list-none bg-surface-container text-on-surface-variant [&::-webkit-details-marker]:hidden">Cancel</summary>
                                                <form method="POST" action="{{ route('portal.bookings.action', [$b, 'cancel']) }}" class="absolute right-0 z-10 mt-1 flex w-60 flex-col gap-2 rounded-xl bg-surface-container-lowest p-3 shadow-lg ring-1 ring-surface-container">@csrf
                                                    <input type="text" name="reason" maxlength="200" placeholder="Reason (optional)" class="rounded-lg border border-outline-variant px-2 py-1.5 text-body-sm">
                                                    <button class="{{ $btn }} bg-error text-on-error">Cancel booking</button>
                                                </form>
                                            </details>
                                        @endif

                                        @if ($b->status === 'ongoing')
                                            <form method="POST" action="{{ route('portal.bookings.action', [$b, 'return']) }}">@csrf
                                                <button class="{{ $btn }} bg-primary text-on-primary hover:bg-primary/90">Mark returned</button>
                                            </form>
                                        @endif

                                        @if (in_array($b->status, ['confirmed', 'ongoing', 'completed'], true) && $b->payment_status !== 'paid')
                                            <form method="POST" action="{{ route('portal.bookings.action', [$b, 'paid']) }}">@csrf
                                                <button class="{{ $btn }} bg-tertiary-fixed text-on-tertiary-fixed">Mark paid</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($bookings->hasPages())
                <div class="flex items-center justify-between gap-3 border-t border-surface-container px-4 py-3 text-body-sm text-on-surface-variant">
                    <span>{{ $bookings->firstItem() }}–{{ $bookings->lastItem() }} of {{ $bookings->total() }}</span>
                    <div class="flex gap-2">
                        @if ($bookings->onFirstPage()) <span class="rounded-lg bg-surface-container px-3 py-1.5 opacity-50">Previous</span> @else <a href="{{ $bookings->previousPageUrl() }}" class="rounded-lg bg-surface-container-low px-3 py-1.5 hover:bg-surface-container-high">Previous</a> @endif
                        @if ($bookings->hasMorePages()) <a href="{{ $bookings->nextPageUrl() }}" class="rounded-lg bg-surface-container-low px-3 py-1.5 hover:bg-surface-container-high">Next</a> @else <span class="rounded-lg bg-surface-container px-3 py-1.5 opacity-50">Next</span> @endif
                    </div>
                </div>
            @endif
        @endif
    </div>
@endsection