@extends('admin.layout')

@section('title', 'Bookings')

@php
    $money = fn ($n) => '$' . number_format((float) $n, 2);
    $tabs = ['' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'ongoing' => 'Ongoing', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
    $keep = fn (array $o = []) => array_filter(array_merge(['status' => $status, 'payment' => $payment, 'q' => $q !== '' ? $q : null], $o), fn ($v) => $v !== null && $v !== '');
@endphp

@section('header-actions')
    <form method="GET" action="{{ route('admin.bookings.index') }}" class="flex items-center gap-2">
        @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
        @if ($payment)<input type="hidden" name="payment" value="{{ $payment }}">@endif
        <input type="search" name="q" value="{{ $q }}" maxlength="60" placeholder="Reference, customer or shop" class="w-64 rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2 text-body-md focus:border-secondary focus:outline-none">
        <button class="rounded-lg bg-primary px-3 py-2 text-label-md text-on-primary">Search</button>
    </form>
@endsection

@section('content')
<div class="flex flex-col gap-space-md">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <nav class="flex flex-wrap gap-2" aria-label="Status">
            @foreach ($tabs as $key => $name)
                @php $cnt = $key === '' ? $counts['all'] : $counts[$key]; $on = ($status ?? '') === $key; @endphp
                <a href="{{ route('admin.bookings.index', $keep(['status' => $key])) }}" @if ($on) aria-current="page" @endif
                   class="rounded-full px-4 py-1.5 text-label-md {{ $on ? 'bg-primary text-on-primary' : 'bg-surface-container-lowest text-on-surface-variant hover:text-on-surface' }}">{{ $name }} <span class="opacity-70">{{ $cnt }}</span></a>
            @endforeach
        </nav>
        <div class="flex items-center gap-1 text-body-sm text-on-surface-variant">
            <span class="mr-1">Payment</span>
            @foreach ([null => 'Any', 'paid' => 'Paid', 'unpaid' => 'Not paid'] as $key => $name)
                <a href="{{ route('admin.bookings.index', $keep(['payment' => $key])) }}" class="rounded-lg px-2.5 py-1 {{ (string) $payment === (string) $key ? 'bg-secondary-container font-semibold text-on-secondary-container' : 'hover:bg-surface-container-high' }}">{{ $name }}</a>
            @endforeach
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-sm">
        @if ($rows->isEmpty())
            <p class="p-10 text-center text-body-md text-on-surface-variant">No bookings match.</p>
        @else
            <div class="relative overflow-x-auto">
                <table class="w-full text-left text-body-md">
                    <thead class="bg-surface-container-low text-label-sm uppercase text-on-surface-variant">
                        <tr><th class="px-5 py-3">Booking</th><th class="px-3 py-3">Customer</th><th class="px-3 py-3">Vehicle &amp; shop</th><th class="px-3 py-3">Pickup → return</th><th class="px-3 py-3 text-right">Total</th><th class="px-3 py-3">Payment</th><th class="px-5 py-3 text-right">Status</th></tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container">
                        @foreach ($rows as $b)
                            <tr>
                                <td class="px-5 py-3"><div class="font-semibold text-on-surface">{{ $b->reference ?? '—' }}</div><div class="text-body-sm text-on-surface-variant">{{ $b->created_at->setTimezone('Asia/Phnom_Penh')->format('M j, Y') }}</div></td>
                                <td class="px-3 py-3"><div>{{ $b->customer->full_name ?? '—' }}</div><div class="text-body-sm text-on-surface-variant">{{ $b->customer->phone ?? '' }}</div></td>
                                <td class="px-3 py-3"><div>{{ trim(($b->vehicle->brand ?? '') . ' ' . ($b->vehicle->model ?? '')) ?: '—' }}</div><div class="text-body-sm text-on-surface-variant">@if ($b->business)<a class="hover:text-secondary hover:underline" href="{{ route('admin.businesses.show', $b->business) }}">{{ $b->business->name }}</a>@endif</div></td>
                                <td class="whitespace-nowrap px-3 py-3 text-on-surface-variant">{{ $b->start_datetime->format('M j') }} → {{ $b->end_datetime->format('M j, Y') }}</td>
                                <td class="px-3 py-3 text-right font-semibold">{{ $money($b->total_price) }}@if ((float) $b->discount_amount > 0)<div class="text-body-sm font-normal text-secondary">{{ $b->voucher_code }}</div>@endif</td>
                                <td class="px-3 py-3">@include('admin.partials.badge', ['status' => $b->payment_status === 'paid' ? 'paid' : 'unpaid', 'text' => $b->payment_status === 'paid' ? 'Paid' : 'Not paid'])</td>
                                <td class="px-5 py-3 text-right">@include('admin.partials.badge', ['status' => $b->displayStatus()])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @include('admin.partials.pager', ['p' => $rows])
        @endif
    </div>
</div>
@endsection