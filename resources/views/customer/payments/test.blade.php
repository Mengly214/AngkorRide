@extends('layouts.customer')

@section('title', 'Test payment')

@section('content')
@php $money = fn ($n) => '$' . number_format((float) $n, 2); @endphp
<div class="mx-auto w-full max-w-lg px-6 pb-16 pt-10">
    <div class="rounded-3xl border border-surface-container bg-surface-container-lowest p-6 shadow-lg sm:p-8">
        <div class="mb-5 flex items-start gap-3 rounded-xl bg-secondary-container/60 px-4 py-3 text-body-sm text-on-secondary-container">
            <span class="material-symbols-outlined mt-0.5 text-lg">science</span>
            <p><strong>Test payment page.</strong> No real money is taken. This stands in for ABA PayWay while the app is being built.</p>
        </div>

        <span class="text-label-sm uppercase tracking-wider text-on-surface-variant">Paying AngkorRide for</span>
        <h1 class="text-headline-md text-on-surface">{{ trim($booking->vehicle->brand . ' ' . $booking->vehicle->model) }}</h1>
        <p class="text-body-md text-on-surface-variant">{{ $booking->business->name }} · Booking {{ $booking->reference }}</p>

        <div class="my-6 flex items-baseline justify-between border-y border-surface-container py-4">
            <span class="text-label-md text-on-surface-variant">Amount</span>
            <span class="text-display-lg font-extrabold text-on-surface">{{ $money($payment->amount) }} <span class="text-label-sm text-on-surface-variant">{{ $payment->currency }}</span></span>
        </div>

        @if ($payment->status !== 'pending')
            <p class="mb-4 rounded-xl bg-surface-container-low px-4 py-3 text-body-md text-on-surface-variant">This payment is already closed ({{ strtolower($payment->label()) }}).</p>
            <a href="{{ route('bookings.show', $booking) }}" class="flex h-12 items-center justify-center rounded-xl bg-primary text-label-lg text-on-primary">Back to my booking</a>
        @else
            <div class="flex flex-col gap-3">
                <form method="POST" action="{{ route('payments.test.answer', [$payment, 'paid']) }}">@csrf
                    <button class="flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-secondary text-label-lg text-on-secondary shadow-md hover:opacity-90"><span class="material-symbols-outlined text-lg">check_circle</span> Pretend to pay {{ $money($payment->amount) }}</button>
                </form>
                <form method="POST" action="{{ route('payments.test.answer', [$payment, 'failed']) }}">@csrf
                    <button class="flex h-11 w-full items-center justify-center rounded-xl border border-error text-label-md text-error hover:bg-error-container/40">Pretend the payment failed</button>
                </form>
                <a href="{{ route('bookings.show', $booking) }}" class="pt-1 text-center text-label-md text-on-surface-variant hover:text-on-surface">Cancel and go back</a>
            </div>
        @endif
    </div>
</div>
@endsection