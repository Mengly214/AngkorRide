@extends('layouts.customer')

@section('title', 'Checkout')

@php
    $title = trim($vehicle->brand . ' ' . $vehicle->model);
    $field = 'w-full h-12 rounded-xl border border-transparent bg-surface-container-low px-4 text-body-md text-on-surface focus:border-secondary focus:bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-secondary/30';
    $voucherValue = old('voucher_code', request('voucher_code', ''));
    $money = fn ($n) => '$' . number_format((float) $n, 2);
    $pickupPlace = $vehicle->pick_up_location ?: ($business->address ?: $business->city);
    $minutes = $start->diffInMinutes($end);
    $durationText = $quote['days'] . ($quote['days'] === 1 ? ' day' : ' days') . ' billed · ' . (intdiv($minutes, 1440) ? intdiv($minutes, 1440) . 'd ' : '') . intdiv($minutes % 1440, 60) . 'h total';
@endphp

@section('content')
{{-- Title band + stepper --}}
<div class="w-full bg-surface-container-low py-8">
    <div class="mx-auto max-w-7xl px-6 lg:px-12">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <span class="mb-1 inline-flex items-center gap-1 rounded-full bg-secondary-container/40 px-2.5 py-0.5 text-label-sm text-secondary">
                    <span class="material-symbols-outlined text-xs">edit_document</span> BOOKING REQUEST
                </span>
                <h1 class="text-display-lg tracking-tight text-on-surface">Complete Your Reservation</h1>
            </div>
            <div class="flex items-center gap-3 self-start rounded-xl bg-surface-container-lowest px-4 py-2.5 md:self-auto">
                <span class="material-symbols-outlined text-xl text-secondary">timer</span>
                <div class="flex flex-col">
                    <span class="text-label-sm uppercase tracking-wider text-on-surface-variant">Shop replies within</span>
                    <span class="text-headline-sm text-on-surface">{{ $holdHours }} hours</span>
                </div>
            </div>
        </div>

        <div class="mt-8 rounded-2xl bg-surface-container-lowest p-4 md:p-6">
            <ol class="grid grid-cols-2 gap-4 md:grid-cols-4" aria-label="Booking steps">
                <li class="flex items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-secondary text-on-secondary"><span class="material-symbols-outlined text-sm">check</span></span>
                    <span class="min-w-0"><span class="block text-label-sm font-bold uppercase tracking-wider text-secondary">Step 1</span><span class="block truncate text-label-md font-semibold text-on-surface">Vehicle &amp; Dates</span></span>
                </li>
                <li class="flex items-center gap-3" aria-current="step">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-container text-surface ring-4 ring-secondary/20 text-label-md font-bold">2</span>
                    <span class="min-w-0"><span class="block text-label-sm font-bold uppercase tracking-wider text-secondary">Step 2 · In Progress</span><span class="block truncate text-label-md font-bold text-on-surface">Your Details</span></span>
                </li>
                <li class="flex items-center gap-3 opacity-60">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-surface-container-high text-on-surface-variant text-label-md">3</span>
                    <span class="min-w-0"><span class="block text-label-sm uppercase tracking-wider text-on-surface-variant">Step 3</span><span class="block truncate text-label-md text-on-surface">Payment Method</span></span>
                </li>
                <li class="flex items-center gap-3 opacity-40">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-surface-container-high text-on-surface-variant text-label-md">4</span>
                    <span class="min-w-0"><span class="block text-label-sm uppercase tracking-wider text-on-surface-variant">Step 4</span><span class="block truncate text-label-md text-on-surface">Confirmed by Shop</span></span>
                </li>
            </ol>
        </div>
    </div>
</div>

<div class="mx-auto w-full max-w-7xl px-6 py-10 lg:px-12">
    <form method="POST" action="{{ route('bookings.store', [$business, $vehicle]) }}" id="checkoutForm" novalidate
          data-quote-url="{{ route('vehicles.quote', [$business, $vehicle]) }}"
          data-pickup-date="{{ $start->toDateString() }}" data-pickup-time="{{ $start->format('H:i') }}"
          data-return-date="{{ $end->toDateString() }}" data-return-time="{{ $end->format('H:i') }}"
          class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12">
        @csrf
        <input type="hidden" name="pickup_date" value="{{ $start->toDateString() }}">
        <input type="hidden" name="pickup_time" value="{{ $start->format('H:i') }}">
        <input type="hidden" name="return_date" value="{{ $end->toDateString() }}">
        <input type="hidden" name="return_time" value="{{ $end->format('H:i') }}">

        <div class="flex flex-col gap-8 lg:col-span-8">

            @if ($errors->any())
                <div role="alert" class="rounded-xl bg-error-container px-4 py-3 text-body-md text-on-error-container">
                    @if ($errors->has('pickup_date') || $errors->has('return_date') || $errors->has('vehicle'))
                        {{ $errors->first('pickup_date') ?: ($errors->first('return_date') ?: $errors->first('vehicle')) }}
                        <a href="{{ $detailsUrl }}" class="ml-1 underline">Change dates</a>
                    @else
                        Please fix the highlighted fields and try again.
                    @endif
                </div>
            @endif

            <section class="rounded-2xl border border-surface-container bg-surface-container-lowest p-6 md:p-8">
                <div class="flex items-center justify-between gap-4 pb-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-surface-container text-on-surface"><span class="material-symbols-outlined text-lg">calendar_month</span></span>
                        <div>
                            <span class="text-label-sm font-bold uppercase tracking-wider text-secondary">Section 1</span>
                            <h2 class="text-headline-md text-on-surface">Rental Period</h2>
                        </div>
                    </div>
                    <a href="{{ $detailsUrl }}" class="flex items-center gap-1.5 rounded-lg bg-surface-container-low px-3.5 py-1.5 text-label-md text-on-surface transition-colors hover:bg-surface-container">
                        <span class="material-symbols-outlined text-base text-secondary">tune</span> Change dates
                    </a>
                </div>
                <div class="mt-4 flex flex-col justify-between gap-4 rounded-xl bg-surface-container-low/70 p-4 md:flex-row md:items-center">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-xl bg-surface-container-lowest text-center">
                            <span class="text-label-sm font-bold uppercase text-secondary">{{ $start->format('M') }}</span>
                            <span class="text-headline-sm font-bold leading-none text-on-surface">{{ $start->format('j') }}</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-label-sm text-on-surface-variant">Pickup</span>
                            <span class="text-headline-sm text-on-surface">{{ $start->format('D, M j, Y · g:i A') }}</span>
                        </div>
                    </div>
                    <span class="material-symbols-outlined hidden text-base text-on-surface-variant md:block">arrow_forward</span>
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-xl bg-surface-container-lowest text-center">
                            <span class="text-label-sm font-bold uppercase text-secondary">{{ $end->format('M') }}</span>
                            <span class="text-headline-sm font-bold leading-none text-on-surface">{{ $end->format('j') }}</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-label-sm text-on-surface-variant">Return</span>
                            <span class="text-headline-sm text-on-surface">{{ $end->format('D, M j, Y · g:i A') }}</span>
                            <span class="text-body-sm text-on-surface-variant">{{ $durationText }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-surface-container bg-surface-container-lowest p-6 md:p-8">
                <div class="flex items-center justify-between gap-4 pb-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-surface-container text-on-surface"><span class="material-symbols-outlined text-lg">storefront</span></span>
                        <div>
                            <span class="text-label-sm font-bold uppercase tracking-wider text-secondary">Section 2</span>
                            <h2 class="text-headline-md text-on-surface">How you receive your vehicle</h2>
                        </div>
                    </div>
                </div>
                <div class="mt-2 grid grid-cols-1 gap-4">
                    <div class="flex flex-col rounded-2xl bg-secondary-container/20 p-5 shadow-sm">
                        <div class="mb-3 flex items-start justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-secondary text-on-secondary"><span class="material-symbols-outlined text-lg">storefront</span></div>
                                <div>
                                    <span class="block text-headline-sm text-on-surface">Self Pick-up</span>
                                    <span class="text-label-sm font-semibold text-secondary">Selected</span>
                                </div>
                            </div>
                            <span class="rounded-full bg-secondary px-2.5 py-1 text-label-sm font-bold text-on-secondary">FREE</span>
                        </div>
                        <p class="mb-3 text-body-sm text-on-surface-variant">Collect the vehicle at {{ $business->name }}. Hotel delivery is not available yet.</p>
                        <div class="flex items-center gap-2 text-label-sm text-on-surface-variant"><span class="material-symbols-outlined text-sm text-secondary">pin_drop</span><span>{{ $pickupPlace }}</span></div>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-surface-container bg-surface-container-lowest p-6 md:p-8">
                <div class="flex items-center justify-between gap-4 pb-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-surface-container text-on-surface"><span class="material-symbols-outlined text-lg">badge</span></span>
                        <div>
                            <span class="text-label-sm font-bold uppercase tracking-wider text-secondary">Section 3</span>
                            <h2 class="text-headline-md text-on-surface">Renter Information</h2>
                        </div>
                    </div>
                </div>@if ($isOwnerLoggedIn)
                    <div class="mb-4 rounded-xl bg-error-container px-4 py-3 text-body-md text-on-error-container">You are signed in with a business account. Sign out of it to book as a customer.</div>
                @endif

                @if ($account)
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="flex flex-col gap-1.5">
                            <span class="text-label-sm text-on-surface-variant">Full name</span>
                            <div class="flex h-12 items-center rounded-xl bg-surface-container-low px-4 text-body-md text-on-surface">{{ $account->full_name }}</div>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <span class="text-label-sm text-on-surface-variant">Phone / WhatsApp</span>
                            <div class="flex h-12 items-center rounded-xl bg-surface-container-low px-4 text-body-md text-on-surface">{{ $account->phone }}</div>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="email" class="text-label-sm text-on-surface-variant">Email <span>(optional)</span></label>
                            <input type="email" name="email" id="email" value="{{ old('email', $account->email) }}" maxlength="255" autocomplete="email" class="{{ $field }}">
                            @error('email') <p class="text-body-sm text-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <p class="mt-3 flex items-center gap-2 text-body-sm text-on-surface-variant"><span class="material-symbols-outlined text-base text-secondary">check_circle</span> Signed in &mdash; these details come from your account.</p>
                @else
                    <p class="mb-4 rounded-xl bg-surface-container-low px-4 py-3 text-body-md text-on-surface-variant">
                        Already have an account? <a href="{{ $loginUrl }}" class="font-semibold text-secondary underline">Sign in</a>
                        &mdash; otherwise fill in your details and we'll create one so you can follow this booking.
                    </p>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="flex flex-col gap-1.5">
                            <label for="name" class="text-label-sm text-on-surface-variant">Full name</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required maxlength="100" autocomplete="name" class="{{ $field }}">
                            @error('name') <p class="text-body-sm text-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="phone" class="text-label-sm text-on-surface-variant">Phone / WhatsApp</label>
                            <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" required maxlength="25" autocomplete="tel" placeholder="e.g. 012 345 678" class="{{ $field }}">
                            @error('phone') <p class="text-body-sm text-error">{{ $message }} @if (str_contains($message, 'sign in'))<a href="{{ $loginUrl }}" class="underline">Sign in</a>@endif</p> @enderror
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="email" class="text-label-sm text-on-surface-variant">Email <span>(optional)</span></label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" class="{{ $field }}">
                            @error('email') <p class="text-body-sm text-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="mt-4 flex flex-col gap-1.5">
                        <label for="password" class="text-label-sm text-on-surface-variant">Choose a password</label>
                        <input type="password" name="password" id="password" required minlength="8" autocomplete="new-password" class="{{ $field }} md:max-w-sm">
                        <p class="text-body-sm text-on-surface-variant">At least 8 characters. You'll use your phone number and this password to see your bookings.</p>
                        @error('password') <p class="text-body-sm text-error">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="mt-4 flex flex-col gap-1.5">
                    <label for="notes" class="text-label-sm text-on-surface-variant">Special requests <span>(optional)</span></label>
                    <textarea name="notes" id="notes" rows="2" maxlength="500" placeholder="e.g. two helmets, child seat, hotel name" class="w-full rounded-xl border border-transparent bg-surface-container-low px-4 py-3 text-body-md text-on-surface focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/30">{{ old('notes') }}</textarea>
                </div>

                <div class="mt-5 flex items-start gap-3 rounded-xl bg-surface-container-low p-4">
                    <span class="material-symbols-outlined mt-0.5 shrink-0 text-xl text-secondary">verified_user</span>
                    <div>
                        <span class="block text-label-md font-semibold text-on-surface">ID check at handover</span>
                        <span class="text-body-sm text-on-surface-variant">Bring your passport or national ID {{ ($vehicle->vehicleType && $vehicle->vehicleType->requires_license) ? 'and a valid driving licence' : '' }}.
                            @if ((float) $quote['deposit'] > 0) A refundable deposit of {{ $money($quote['deposit']) }} is paid to the shop at pickup. @endif</span>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-surface-container bg-surface-container-lowest p-6 md:p-8">
                <div class="flex items-center justify-between gap-4 pb-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-surface-container text-on-surface"><span class="material-symbols-outlined text-lg">sell</span></span>
                        <div>
                            <span class="text-label-sm font-bold uppercase tracking-wider text-secondary">Section 4</span>
                            <h2 class="text-headline-md text-on-surface">Voucher</h2>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <input type="text" name="voucher_code" id="voucher_code" value="{{ $voucherValue }}" maxlength="40" placeholder="Enter code" autocapitalize="characters" class="{{ $field }} uppercase">
                    <button type="button" id="applyVoucher" class="shrink-0 rounded-xl bg-surface-container-low px-6 text-label-md text-on-surface hover:bg-surface-container-high">Apply</button>
                </div>
                <p id="voucherMsg" role="status" class="mt-2 text-body-sm
                    @if ($quote['voucher_ok'] === true) text-secondary @elseif ($quote['voucher_ok'] === false) text-error @else text-on-surface-variant @endif">
                    @if ($quote['voucher_ok'] === true) Voucher {{ $quote['voucher'] }} applied. @elseif ($quote['voucher_ok'] === false) {{ $quote['voucher_message'] }} @endif
                </p>
                @error('voucher_code') <p class="text-body-sm text-error">{{ $message }}</p> @enderror
            </section>

            <section class="rounded-2xl border border-surface-container bg-surface-container-lowest p-6 md:p-8">
                <div class="flex items-center justify-between gap-4 pb-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-surface-container text-on-surface"><span class="material-symbols-outlined text-lg">account_balance_wallet</span></span>
                        <div>
                            <span class="text-label-sm font-bold uppercase tracking-wider text-secondary">Section 5</span>
                            <h2 class="text-headline-md text-on-surface">Choose Payment Method</h2>
                        </div>
                    </div>
                </div>
                <div class="mt-2 flex flex-col gap-3.5">
                    <label class="flex cursor-pointer items-center justify-between gap-4 rounded-xl bg-secondary-container/20 p-4 shadow-sm">
                        <div class="flex items-center gap-4">
                            <input type="radio" name="payment_method" value="pay_at_pickup" checked class="h-5 w-5 accent-secondary">
                            <div class="flex flex-col">
                                <span class="text-label-lg font-bold text-on-surface">Pay at Pickup <span class="ml-1 rounded-full bg-secondary px-2 py-0.5 text-label-sm text-on-secondary">Zero risk</span></span>
                                <span class="text-body-sm text-on-surface-variant">Pay the shop in cash when you collect the vehicle. Nothing is charged online.</span>
                            </div>
                        </div>
                        <span class="material-symbols-outlined shrink-0 text-2xl text-secondary">payments</span>
                    </label>
                    <div class="flex cursor-not-allowed items-center justify-between gap-4 rounded-xl bg-surface-container-low/60 p-4 opacity-60">
                        <div class="flex items-center gap-4">
                            <input type="radio" disabled class="h-5 w-5">
                            <div class="flex flex-col">
                                <span class="text-label-lg font-bold text-on-surface">KHQR &amp; Card <span class="ml-1 rounded-full bg-surface-container px-2 py-0.5 text-label-sm font-normal">Coming soon</span></span>
                                <span class="text-body-sm text-on-surface-variant">Online payments are not available yet.</span>
                            </div>
                        </div>
                        <span class="material-symbols-outlined shrink-0 text-2xl text-on-surface-variant">credit_card</span>
                    </div>
                    @error('payment_method') <p class="text-body-sm text-error">{{ $message }}</p> @enderror
                </div>
            </section>

            <section class="rounded-2xl border border-surface-container bg-surface-container-lowest p-6 md:p-8">
                <div class="flex items-center justify-between gap-4 pb-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-surface-container text-on-surface"><span class="material-symbols-outlined text-lg">gavel</span></span>
                        <div>
                            <span class="text-label-sm font-bold uppercase tracking-wider text-secondary">Section 6</span>
                            <h2 class="text-headline-md text-on-surface">Terms</h2>
                        </div>
                    </div>
                </div>
                <ul class="list-disc pl-5 text-body-sm text-on-surface-variant">
                    <li>The shop must confirm your request. If it doesn't answer within {{ $holdHours }} hours, the request expires and the vehicle is released.</li>
                    <li>Free cancellation until {{ $freeCancelHours }} hours before pickup.</li>
                    <li>@if ((float) $quote['deposit'] > 0) A refundable deposit of {{ $money($quote['deposit']) }} is paid at pickup and returned when the vehicle comes back undamaged. @else No deposit is required. @endif</li>
                    <li>Bring a valid ID or driving licence. Return the vehicle with the same fuel level and in the same condition.</li>
                </ul>
                <label class="mt-3 flex cursor-pointer items-start gap-2 text-body-md text-on-surface">
                    <input type="checkbox" name="terms" value="1" @checked(old('terms')) class="mt-1 h-4 w-4 accent-secondary">
                    <span>I have read and accept the rental terms.</span>
                </label>
                @error('terms') <p class="mt-1 text-body-sm text-error">{{ $message }}</p> @enderror
            </section>
        </div>

        {{-- Summary --}}
        <aside class="lg:sticky lg:top-28 lg:col-span-4">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-5 rounded-2xl border border-surface-container bg-surface-container-lowest p-6 shadow-lg">
                    <div class="flex items-center justify-between pb-1">
                        <h2 class="text-headline-md text-on-surface">Booking Summary</h2>
                        <span class="rounded bg-surface-container px-2 py-0.5 text-label-sm text-on-surface">Pay at pickup</span>
                    </div>

                    <div class="flex items-start gap-4 rounded-xl bg-surface-container-low p-4">
                        <div class="h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-surface-container">
                            @if ($vehicle->photo_url)<img src="{{ $vehicle->photo_url }}" alt="{{ $title }}" class="h-full w-full object-cover">@else<div class="flex h-full w-full items-center justify-center text-outline"><span class="material-symbols-outlined text-3xl">two_wheeler</span></div>@endif
                        </div>
                        <div class="flex min-w-0 flex-col">
                            <span class="truncate text-headline-sm font-bold text-on-surface">{{ $title }}</span>
                            <span class="text-body-sm text-on-surface-variant">{{ $vehicle->vehicleType->name ?? '' }}@if ($vehicle->color) · {{ $vehicle->color }}@endif</span>
                            <a href="{{ $detailsUrl }}" class="mt-1 text-body-sm text-secondary hover:underline">Change vehicle or dates</a>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 text-body-sm">
                        <div class="flex items-start gap-2.5">
                            <span class="material-symbols-outlined mt-0.5 shrink-0 text-base text-secondary">store</span>
                            <div class="flex flex-col"><span class="text-label-sm text-on-surface-variant">Partner shop</span><span class="font-semibold text-on-surface">{{ $business->name }}</span><span class="text-on-surface-variant">{{ $business->address ?: $business->city }}</span></div>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <span class="material-symbols-outlined mt-0.5 shrink-0 text-base text-secondary">date_range</span>
                            <div class="flex flex-col"><span class="text-label-sm text-on-surface-variant">Dates &amp; time</span><span class="text-on-surface">{{ $start->format('M j, g:i A') }} → {{ $end->format('M j, g:i A') }}</span><span class="text-label-sm text-secondary">{{ $quote['days'] }} {{ $quote['days'] === 1 ? 'day' : 'days' }} billed</span></div>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <span class="material-symbols-outlined mt-0.5 shrink-0 text-base text-secondary">pin_drop</span>
                            <div class="flex flex-col"><span class="text-label-sm text-on-surface-variant">Handover</span><span class="font-medium text-on-surface">Self pick-up at the shop</span></div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2.5 rounded-xl bg-surface-container-low p-4">
                        <span class="text-label-sm font-bold uppercase tracking-wider text-on-surface-variant">Pricing breakdown</span>
                        <dl class="flex flex-col gap-2 text-body-sm text-on-surface" id="breakdown">
                            <div class="flex justify-between gap-3"><dt id="rowRentalLabel">{{ $quote['days'] }} {{ $quote['days'] === 1 ? 'day' : 'days' }} × {{ $money($quote['rate']) }}</dt><dd class="font-semibold" id="rowSubtotal">{{ $money($quote['subtotal']) }}</dd></div>
                            <div class="flex justify-between gap-3 text-secondary @if ($quote['discount'] <= 0) hidden @endif" id="rowDiscountWrap"><dt id="rowDiscountLabel">Voucher {{ $quote['voucher'] }}</dt><dd class="font-semibold" id="rowDiscount">−{{ $money($quote['discount']) }}</dd></div>
                            <div class="flex justify-between gap-3 pt-2 text-on-surface-variant @if ((float) $quote['deposit'] <= 0) hidden @endif" id="rowDepositWrap"><dt>Security deposit <span class="text-xs">(refundable, at pickup)</span></dt><dd id="rowDeposit">{{ $money($quote['deposit']) }}</dd></div>
                        </dl>
                        <div class="mt-2 flex items-baseline justify-between rounded-lg bg-surface-container-lowest p-3">
                            <div class="flex flex-col"><span class="text-headline-sm font-bold text-on-surface">Total at pickup</span><span class="text-body-sm text-on-surface-variant">Paid to the shop</span></div>
                            <span class="text-display-lg font-black text-on-surface" id="rowTotal">{{ $money($quote['total']) }}</span>
                        </div>
                    </div>

                    <button type="submit" id="confirmBtn" class="group flex h-14 w-full items-center justify-center gap-2 rounded-xl bg-primary text-headline-sm text-on-primary shadow-md transition-all hover:bg-on-surface hover:shadow-lg disabled:opacity-50">
                        <span>Confirm booking (<span id="ctaTotal">{{ $money($quote['total']) }}</span>)</span>
                        <span class="material-symbols-outlined text-lg transition-transform group-hover:translate-x-1">arrow_forward</span>
                    </button>

                    <div class="flex flex-col gap-2 pt-1 text-body-sm text-on-surface-variant">
                        <div class="flex items-center gap-2"><span class="material-symbols-outlined text-sm text-secondary">verified</span><span>Free cancellation up to {{ $freeCancelHours }}h before pickup</span></div>
                        <div class="flex items-center gap-2"><span class="material-symbols-outlined text-sm text-secondary">schedule</span><span>The shop confirms within {{ $holdHours }} hours</span></div>
                        <div class="flex items-center gap-2"><span class="material-symbols-outlined text-sm text-secondary">credit_card_off</span><span>You're not charged online</span></div>
                    </div>
                </div>
            </div>
        </aside>
    </form>
</div>

<script>
(function () {
    var form = document.getElementById('checkoutForm');
    var input = document.getElementById('voucher_code');
    var msg = document.getElementById('voucherMsg');
    var btn = document.getElementById('applyVoucher');
    var seq = 0;

    function money(n) { return '$' + Number(n).toFixed(2); }
    function setMsg(text, cls) { msg.textContent = text; msg.className = 'text-body-sm ' + cls; }
    function toggle(id, show) { document.getElementById(id).classList.toggle('hidden', !show); }

    function apply() {
        var code = input.value.trim();
        var mine = ++seq;
        var p = new URLSearchParams({
            pickup_date: form.dataset.pickupDate, pickup_time: form.dataset.pickupTime,
            return_date: form.dataset.returnDate, return_time: form.dataset.returnTime, voucher_code: code
        });
        btn.disabled = true;
        fetch(form.dataset.quoteUrl + '?' + p.toString(), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (q) {
                if (mine !== seq) return;
                if (!q.ok) { setMsg(q.message || 'Could not check the voucher.', 'text-error'); return; }
                document.getElementById('rowSubtotal').textContent = money(q.subtotal);
                document.getElementById('rowTotal').textContent = money(q.total);
                document.getElementById('ctaTotal').textContent = money(q.total);
                document.getElementById('rowDiscount').textContent = '−' + money(q.discount);
                document.getElementById('rowDiscountLabel').textContent = 'Voucher ' + (q.voucher || '');
                toggle('rowDiscountWrap', q.discount > 0);
                if (code === '') setMsg('', 'text-on-surface-variant');
                else if (q.voucher_ok) setMsg('Voucher ' + q.voucher + ' applied: you save ' + money(q.discount) + '.', 'text-secondary');
                else setMsg(q.voucher_message || "That voucher isn't valid.", 'text-error');
            })
            .catch(function () { if (mine === seq) setMsg('Could not check the voucher. Please try again.', 'text-error'); })
            .then(function () { btn.disabled = false; });
    }

    btn.addEventListener('click', apply);
    input.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); apply(); } });

    // Stop double submits
    form.addEventListener('submit', function () {
        var b = document.getElementById('confirmBtn');
        setTimeout(function () { b.disabled = true; }, 0);
    });
})();
</script>
@endsection