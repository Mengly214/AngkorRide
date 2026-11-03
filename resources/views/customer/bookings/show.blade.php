@extends('layouts.customer')

@section('title', 'Booking ' . $booking->reference)

@php
    $vehicle = $booking->vehicle;
    $business = $booking->business;
    $type = $vehicle->vehicleType;
    $title = trim($vehicle->brand . ' ' . $vehicle->model);
    $label = $booking->displayStatus();
    $money = fn ($n) => '$' . number_format((float) $n, 2);
    $tz = 'Asia/Phnom_Penh';
    $first = \Illuminate\Support\Str::before(trim($booking->customer->full_name), ' ');
    $days = max(1, (int) ceil($booking->start_datetime->diffInMinutes($booking->end_datetime) / 1440));
    $hours = (int) round($booking->start_datetime->diffInMinutes($booking->end_datetime) / 60);
    $paidOnline = $booking->payment_status === 'paid' && $booking->payment_method === 'online';
    $payLabel = match (true) {
        $paidOnline => 'PAID ONLINE',
        $booking->payment_status === 'paid' => 'PAID',
        $booking->payment_status === 'refunded' => 'REFUNDED',
        default => 'PAY AT PICKUP',
    };
    $failed = in_array($label, ['Rejected', 'Expired', 'Cancelled'], true);
    $expiresAt = $booking->expires_at ? $booking->expires_at->copy()->setTimezone($tz) : null;

    // badge text, icon and colours for each status
    [$icon, $badgeClass, $badgeText] = match ($label) {
        'Pending' => ['hourglass_top', 'bg-secondary-container/70 text-on-secondary-container', 'Request sent · waiting for the shop'],
        'Confirmed' => ['check_circle', 'bg-secondary-container/70 text-on-secondary-container', 'Booking confirmed by the shop'],
        'Ongoing' => ['two_wheeler', 'bg-secondary text-on-secondary', 'Rental in progress'],
        'Completed' => ['task_alt', 'bg-surface-container text-on-surface', 'Rental completed'],
        'Rejected' => ['cancel', 'bg-error-container text-on-error-container', 'The shop could not accept this booking'],
        'Expired' => ['timer_off', 'bg-error-container text-on-error-container', 'This request expired'],
        default => ['event_busy', 'bg-error-container text-on-error-container', 'Booking cancelled'],
    };

    $headline = match (true) {
        $label === 'Pending' && $justBooked => "Request sent, {$first}!",
        $label === 'Pending' => 'Waiting for ' . $business->name,
        $label === 'Confirmed' => "You're all set, {$first}!",
        $label === 'Ongoing' => 'Enjoy the ride, ' . $first . '!',
        $label === 'Completed' => 'Thanks for riding with us, ' . $first . '.',
        $label === 'Rejected' => 'The shop could not take this booking',
        $label === 'Expired' => 'This request expired',
        default => 'This booking was cancelled',
    };

    // Step 4 of the stepper changes with the status
    [$s4Icon, $s4Label, $s4Class] = match (true) {
        $label === 'Pending' => ['hourglass_top', 'Awaiting shop', 'bg-surface-container-lowest ring-2 ring-secondary/30'],
        $failed => ['close', $label, 'bg-error-container/60'],
        default => ['verified', $label === 'Confirmed' ? 'Booking Confirmed' : $label, 'bg-secondary-container/40'],
    };

    $specs = array_values(array_filter([
        $type?->name,
        $vehicle->color,
        $type ? $type->passenger_capacity . ' ' . ($type->passenger_capacity === 1 ? 'seat' : 'seats') : null,
        $vehicle->year ? 'Model ' . $vehicle->year : null,
    ]));
@endphp

@section('content')
<style>@media print { header, footer, .no-print { display: none !important; } body { background: #fff; } }</style>

<div class="relative mx-auto w-full max-w-7xl px-6 pb-16 pt-6 lg:px-12">
    <div class="pointer-events-none absolute -top-10 left-1/2 -z-10 h-72 w-3/4 -translate-x-1/2 rounded-full bg-gradient-to-b from-secondary/10 via-secondary-container/20 to-transparent blur-3xl"></div>

    @if ($errors->any())
        <div role="alert" class="mb-6 rounded-xl bg-error-container px-4 py-3 text-body-md text-on-error-container">{{ $errors->first() }}</div>
    @endif
    @if (session('payment_notice'))
        <div role="status" class="mb-6 rounded-xl bg-secondary-container px-4 py-3 text-body-md text-on-secondary-container">{{ session('payment_notice') }}</div>
    @endif

    {{-- Progress --}}
    <div class="no-print mb-8 w-full">
        <ol class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-surface-container bg-surface-container-lowest p-4 md:p-5" aria-label="Booking steps">
            @foreach ([['Step 1', 'Vehicle & Dates'], ['Step 2', 'Your Details'], ['Step 3', 'Payment Method']] as [$n, $t])
                <li class="flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-secondary text-on-secondary shadow-sm"><span class="material-symbols-outlined text-base font-bold">check</span></span>
                    <span class="flex flex-col"><span class="text-label-sm uppercase tracking-wider text-secondary">{{ $n }}</span><span class="text-label-md font-semibold text-on-surface">{{ $t }}</span></span>
                </li>
                <li class="hidden h-0.5 max-w-[72px] flex-1 bg-secondary-container sm:block" aria-hidden="true"></li>
            @endforeach
            <li class="flex items-center gap-3 rounded-xl px-4 py-2 {{ $s4Class }}" aria-current="step">
                <span class="flex h-8 w-8 items-center justify-center rounded-full {{ $failed ? 'bg-error text-on-error' : 'bg-secondary text-on-secondary' }} shadow-md"><span class="material-symbols-outlined text-base font-bold">{{ $s4Icon }}</span></span>
                <span class="flex flex-col"><span class="text-label-sm font-bold uppercase tracking-wider {{ $failed ? 'text-on-error-container' : 'text-on-secondary-container' }}">Step 4</span><span class="text-label-md font-bold {{ $failed ? 'text-on-error-container' : 'text-secondary' }}" id="statusBadge">{{ $s4Label }}</span></span>
            </li>
        </ol>
    </div>

    {{-- Hero --}}
    <div class="relative mb-10 overflow-hidden rounded-3xl border border-surface-container bg-surface-container-lowest p-6 sm:p-8 lg:p-10">
        <div class="pointer-events-none absolute -right-16 -top-16 h-80 w-80 rounded-full bg-secondary-container/30 blur-2xl"></div>
        <div class="relative z-10 flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
            <div class="flex max-w-3xl flex-col gap-3">
                <div class="inline-flex w-fit items-center gap-2 rounded-full px-3.5 py-1.5 {{ $badgeClass }}">
                    <span class="material-symbols-outlined text-base" style="font-variation-settings: 'FILL' 1;">{{ $icon }}</span>
                    <span class="text-label-sm uppercase tracking-wide">{{ $badgeText }}</span>
                </div>
                <h1 class="text-display-lg text-on-surface">{{ $headline }}</h1>
                <p class="text-body-lg text-on-surface-variant" id="nextStep">
                    @switch($label)
                        @case('Pending')
                            {{ $business->name }} will review your request and contact you on <strong class="text-on-surface">{{ $booking->customer->phone }}</strong>.
                            If nobody answers by <strong class="text-on-surface">{{ $expiresAt ? $expiresAt->format('g:i A, M j') : 'soon' }}</strong> (Cambodia time), the request expires and the vehicle is released.
                            @break
                        @case('Confirmed')
                            Go to <strong class="text-on-surface">{{ $booking->pickup_location }}</strong> on
                            <strong class="text-on-surface">{{ $booking->start_datetime->format('D, M j · g:i A') }}</strong>, bring your ID, and quote
                            <strong class="text-on-surface">{{ $booking->reference }}</strong>.
                            @break
                        @case('Ongoing')
                            Please return the vehicle by <strong class="text-on-surface">{{ $booking->end_datetime->format('D, M j · g:i A') }}</strong>.
                            @break
                        @case('Completed')
                            Your rental from {{ $business->name }} is complete. You can book the same vehicle again any time.
                            @break
                        @default
                            @if ($booking->cancellation_reason) {{ $booking->cancellation_reason }} @endif
                            You can book another vehicle any time.
                    @endswitch
                </p>
            </div>

            <div class="flex min-w-[260px] flex-col gap-2 rounded-2xl bg-surface-container-low p-4 sm:p-5">
                <span class="text-label-sm uppercase tracking-wider text-on-surface-variant">Booking reference</span>
                <div class="flex items-center justify-between gap-3 rounded-xl bg-surface-container-lowest px-3.5 py-2.5">
                    <span class="text-headline-sm font-extrabold tracking-tight text-on-surface" id="bookingRef">{{ $booking->reference }}</span>
                    <button type="button" id="copyBtn" aria-label="Copy booking reference" data-ref="{{ $booking->reference }}"
                            class="no-print flex items-center gap-1 rounded-lg px-2.5 py-1 text-label-md text-secondary transition-all hover:bg-secondary-container/50">
                        <span class="material-symbols-outlined text-sm">content_copy</span><span id="copyText">Copy</span>
                    </button>
                </div>
                <span class="flex items-center gap-1.5 text-body-sm text-on-surface-variant"><span class="material-symbols-outlined text-xs text-secondary">lock</span> Only you can see this booking</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12">

        {{-- LEFT --}}
        <div class="flex flex-col gap-8 lg:col-span-7">

            <div class="flex flex-col gap-6 rounded-3xl border border-surface-container bg-surface-container-lowest p-6 sm:p-8">
                {{-- Shop --}}
                <div class="-mx-6 -mt-6 flex flex-wrap items-center justify-between gap-4 rounded-t-3xl bg-surface-container-low/40 p-6 sm:-mx-8 sm:-mt-8 sm:p-8">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-secondary text-on-secondary shadow-md"><span class="material-symbols-outlined text-2xl">storefront</span></div>
                        <div class="flex flex-col">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-headline-sm text-on-surface">{{ $business->name }}</span>
                                @if ($business->is_verified)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-secondary-container px-2 py-0.5 text-label-sm text-on-secondary-container"><span class="material-symbols-outlined text-xs font-bold">verified</span> Verified shop</span>
                                @endif
                            </div>
                            <span class="text-body-sm text-on-surface-variant">{{ $business->address ?: $business->city }}</span>
                        </div>
                    </div>
                    @if ($business->phone)
                        <a href="tel:{{ $business->phone }}" class="no-print flex items-center gap-1.5 rounded-xl bg-secondary-container/60 px-3.5 py-2 text-label-md text-on-secondary-container transition-colors hover:bg-secondary-container"><span class="material-symbols-outlined text-base">call</span><span>Call {{ $business->phone }}</span></a>
                    @endif
                </div>

                {{-- Vehicle --}}
                <div class="grid grid-cols-1 items-center gap-5 rounded-2xl bg-surface-container-low p-4 md:grid-cols-12">
                    <div class="relative h-44 overflow-hidden rounded-xl bg-surface-container md:col-span-5">
                        @if ($vehicle->photo_url)<img class="h-full w-full object-cover" src="{{ $vehicle->photo_url }}" alt="{{ $title }}">@else<div class="flex h-full w-full items-center justify-center text-outline"><span class="material-symbols-outlined text-6xl">two_wheeler</span></div>@endif
                        <div class="absolute left-2.5 top-2.5 rounded-md bg-primary-container px-2 py-1 text-label-sm uppercase text-on-primary">Plate {{ $vehicle->plate_number }}</div>
                    </div>
                    <div class="flex flex-col gap-2 md:col-span-7">
                        <span class="text-label-sm font-bold uppercase tracking-wider text-secondary">Reserved vehicle</span>
                        <h2 class="text-headline-md text-on-surface">{{ $title }}</h2>
                        @if ($specs)
                            <div class="flex flex-wrap gap-2 pt-1">
                                @foreach ($specs as $s)<span class="rounded-full bg-surface-container-lowest px-2.5 py-1 text-label-sm text-on-surface-variant">{{ $s }}</span>@endforeach
                            </div>
                        @endif
                        <a href="{{ route('vehicles.show', [$business, $vehicle]) }}" class="no-print mt-1 w-fit text-label-md text-secondary hover:underline">View vehicle page</a>
                    </div>
                </div>

                {{-- Handover pass --}}
                @if (in_array($label, ['Confirmed', 'Ongoing'], true))
                    <div class="flex flex-col items-center gap-5 rounded-2xl bg-surface-container-low/70 p-5 sm:flex-row">
                        <div class="flex h-28 w-28 shrink-0 items-center justify-center rounded-xl bg-surface-container-lowest shadow-sm"><span class="material-symbols-outlined text-6xl text-secondary">confirmation_number</span></div>
                        <div class="flex flex-col gap-1.5 text-center sm:text-left">
                            <span class="text-label-sm font-bold uppercase tracking-wider text-secondary">At the shop</span>
                            <span class="text-headline-sm text-on-surface">Show this booking reference</span>
                            <p class="text-body-sm text-on-surface-variant">Tell the staff <strong class="text-on-surface">{{ $booking->reference }}</strong> and show your ID. Keep this page open on your phone, or print it.</p>
                        </div>
                    </div>
                @endif

                {{-- Schedule --}}
                <div class="flex flex-col gap-4">
                    <span class="text-label-lg text-on-surface">Rental schedule</span>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="flex flex-col gap-2 rounded-2xl bg-surface-container-low p-4">
                            <div class="flex items-center gap-2 text-secondary"><span class="material-symbols-outlined text-lg">storefront</span><span class="text-label-md font-bold uppercase">Pickup</span></div>
                            <span class="text-headline-sm text-on-surface">{{ $booking->start_datetime->format('D, M j, Y') }}</span>
                            <span class="text-label-md font-bold text-secondary">{{ $booking->start_datetime->format('g:i A') }}</span>
                            <p class="pt-1 text-body-sm text-on-surface-variant"><strong>Location:</strong> {{ $booking->pickup_location }}</p>
                        </div>
                        <div class="flex flex-col gap-2 rounded-2xl bg-surface-container-low p-4">
                            <div class="flex items-center gap-2 text-secondary"><span class="material-symbols-outlined text-lg">assignment_return</span><span class="text-label-md font-bold uppercase">Return</span></div>
                            <span class="text-headline-sm text-on-surface">{{ $booking->end_datetime->format('D, M j, Y') }}</span>
                            <span class="text-label-md font-semibold text-on-surface">{{ $booking->end_datetime->format('g:i A') }} ({{ $days }} {{ $days === 1 ? 'day' : 'days' }} billed · {{ $hours }}h)</span>
                            <p class="pt-1 text-body-sm text-on-surface-variant"><strong>Location:</strong> {{ $booking->return_location ?: $booking->pickup_location }}</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 rounded-2xl bg-surface-container-low p-4">
                        <span class="flex items-center gap-1.5 text-label-md font-semibold text-on-surface"><span class="material-symbols-outlined text-base text-secondary">person</span> Renter</span>
                        <div class="grid grid-cols-1 gap-2 text-body-sm sm:grid-cols-2">
                            <div><span class="text-on-surface-variant">Name</span><div class="font-semibold text-on-surface">{{ $booking->customer->full_name }}</div></div>
                            <div><span class="text-on-surface-variant">Phone</span><div class="font-semibold text-on-surface">{{ $booking->customer->phone }}</div></div>
                            @if ($booking->customer_email)<div><span class="text-on-surface-variant">Email</span><div class="break-all font-semibold text-on-surface">{{ $booking->customer_email }}</div></div>@endif
                            @if ($booking->customer_notes)<div class="sm:col-span-2"><span class="text-on-surface-variant">Your requests</span><div class="break-words font-semibold text-on-surface">{{ $booking->customer_notes }}</div></div>@endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- What happens next --}}
            @unless ($failed || $label === 'Completed')
                <div class="flex flex-col gap-6 rounded-3xl border border-surface-container bg-surface-container-lowest p-6 sm:p-8">
                    <div class="flex items-center justify-between">
                        <h3 class="text-headline-md text-on-surface">What happens next?</h3>
                        <span class="text-label-sm font-bold uppercase text-secondary">3 simple steps</span>
                    </div>
                    @php
                        $steps = [
                            ['The shop answers your request', $label === 'Pending' ? 'Within ' . $holdHours . ' hours, ' . $business->name . ' accepts or declines. You\'ll see the result on this page and they\'ll call you on ' . $booking->customer->phone . '.' : 'Done. ' . $business->name . ' accepted your booking.', $label === 'Pending' ? 'In progress' : 'Done', $label === 'Pending'],
                            ['Pickup at the shop · ' . $booking->start_datetime->format('D, M j, g:i A'), 'Show your passport or national ID' . ($type && $type->requires_license ? ' and a valid driving licence' : '') . '.' . ((float) $booking->deposit_amount > 0 ? ($paidOnline ? ' The rental total is already paid online. Pay the refundable deposit of ' . $money($booking->deposit_amount) . ' to the shop.' : ' Pay the refundable deposit of ' . $money($booking->deposit_amount) . ' and the rental total of ' . $money($booking->total_price) . ' to the shop.') : ($paidOnline ? ' Your rental total is already paid online.' : ' Pay the rental total of ' . $money($booking->total_price) . ' to the shop.')), null, false],
                            ['Ride and return · ' . $booking->end_datetime->format('D, M j, g:i A'), 'Bring the vehicle back with the same fuel level and in the same condition' . ((float) $booking->deposit_amount > 0 ? ' to get your deposit back.' : '.'), null, false],
                        ];
                    @endphp
                    <ol class="relative ml-3 flex flex-col gap-6 border-l-2 border-secondary-container pl-6">
                        @foreach ($steps as $i => [$t, $d, $tag, $active])
                            <li class="relative flex flex-col gap-1">
                                <span class="absolute -left-[41px] top-0 flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold shadow-sm {{ $i === 0 && $label !== 'Pending' ? 'bg-secondary text-on-secondary' : ($active ? 'bg-secondary text-on-secondary' : 'bg-secondary-container text-on-secondary-container') }}">
                                    @if ($i === 0 && $label !== 'Pending')<span class="material-symbols-outlined text-sm">check</span>@else{{ $i + 1 }}@endif
                                </span>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-headline-sm text-on-surface">{{ $t }}</span>
                                    @if ($tag)<span class="rounded-full bg-secondary-container/60 px-2 py-0.5 text-label-sm text-on-secondary-container">{{ $tag }}</span>@endif
                                </div>
                                <p class="text-body-md text-on-surface-variant">{{ $d }}</p>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endunless
        </div>

        {{-- RIGHT --}}
        <div class="flex flex-col gap-6 lg:col-span-5">
            <div class="flex flex-col gap-6 rounded-3xl border border-surface-container bg-surface-container-lowest p-6 shadow-lg sm:p-8">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex flex-col">
                        <span class="text-label-sm uppercase tracking-wider text-on-surface-variant">Booking summary</span>
                        <span class="text-headline-md text-on-surface">Price details</span>
                    </div>
                    <span class="flex items-center gap-1 rounded-full bg-secondary-container px-3 py-1 text-label-sm font-bold text-on-secondary-container"><span class="material-symbols-outlined text-xs">payments</span> {{ $payLabel }}</span>
                </div>

                <div class="flex flex-col gap-3.5 pt-1 text-body-md text-on-surface">
                    <div class="flex items-center justify-between"><span>Vehicle rental ({{ $days }} {{ $days === 1 ? 'day' : 'days' }} × {{ $money($booking->rate_amount) }})</span><span class="font-semibold">{{ $money($booking->subtotal()) }}</span></div>
                    @if ((float) $booking->discount_amount > 0)
                        <div class="flex items-center justify-between text-secondary"><span>Voucher {{ $booking->voucher_code }}</span><span class="font-semibold">−{{ $money($booking->discount_amount) }}</span></div>
                    @endif
                    <div class="my-1 h-px w-full bg-surface-container"></div>
                    <div class="flex items-baseline justify-between">
                        <div class="flex flex-col"><span class="text-label-md text-on-surface-variant">Total {{ $booking->payment_status === 'paid' ? 'paid' : ($booking->payment_status === 'refunded' ? 'refunded' : 'to pay at pickup') }}</span></div>
                        <div class="flex items-baseline gap-1"><span class="text-display-lg font-extrabold text-on-surface">{{ $money($booking->total_price) }}</span><span class="text-label-sm font-bold text-on-surface-variant">USD</span></div>
                    </div>
                </div>

                @if ((float) $booking->deposit_amount > 0)
                    <div class="flex items-start gap-3 rounded-2xl bg-surface-container-low p-4">
                        <span class="material-symbols-outlined mt-0.5 shrink-0 text-xl text-secondary">account_balance_wallet</span>
                        <div class="flex flex-col gap-0.5">
                            <span class="text-label-md font-bold text-on-surface">Refundable deposit: {{ $money($booking->deposit_amount) }}</span>
                            <p class="text-body-sm leading-relaxed text-on-surface-variant">Paid to the shop at pickup and returned when the vehicle comes back undamaged.</p>
                        </div>
                    </div>
                @endif

                {{-- Online payment (optional) --}}
                @if ($lastPayment && in_array($lastPayment->status, ['refund_due', 'refunded'], true))
                    <div class="flex items-start gap-3 rounded-2xl bg-surface-container-low p-4">
                        <span class="material-symbols-outlined mt-0.5 shrink-0 text-xl text-secondary">undo</span>
                        <div class="flex flex-col gap-0.5">
                            <span class="text-label-md font-bold text-on-surface">{{ $lastPayment->status === 'refunded' ? 'Your payment was refunded' : 'Refund on its way' }}</span>
                            <p class="text-body-sm leading-relaxed text-on-surface-variant">
                                @if ($lastPayment->status === 'refunded')
                                    {{ $money($lastPayment->amount) }} was sent back to you.
                                @else
                                    You paid {{ $money($lastPayment->amount) }} online. AngkorRide will send it back to you. Call support if it takes more than a few days.
                                @endif
                            </p>
                        </div>
                    </div>
                @elseif ($paidOnline)
                    <div class="flex items-start gap-3 rounded-2xl bg-tertiary-fixed/60 p-4">
                        <span class="material-symbols-outlined mt-0.5 shrink-0 text-xl text-on-tertiary-fixed">verified</span>
                        <p class="text-body-sm leading-relaxed text-on-tertiary-fixed"><strong>Paid online.</strong> Nothing more to pay for the rental. Payment ref {{ $lastPayment?->tran_id }}.</p>
                    </div>
                @elseif ($canPayOnline)
                    <form method="POST" action="{{ $payUrl }}" class="no-print">@csrf
                        <button type="submit" class="flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-secondary text-label-lg text-on-secondary shadow-md transition-all hover:opacity-90"><span class="material-symbols-outlined text-lg">qr_code_2</span> Pay {{ $money($booking->total_price) }} online now</button>
                        <p class="pt-2 text-center text-body-sm text-on-surface-variant">Optional. You can still pay at pickup instead.</p>
                    </form>
                @elseif ($gatewayOn && $label === 'Pending')
                    <p class="rounded-xl bg-surface-container-low px-4 py-3 text-center text-body-sm text-on-surface-variant">You will be able to pay online once the shop confirms your booking.</p>
                @endif

                <div class="no-print flex flex-col gap-3 pt-1">
                    <button type="button" onclick="window.print()" class="flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-primary text-label-lg text-on-primary shadow-md transition-all hover:bg-on-surface">
                        <span class="material-symbols-outlined text-lg">print</span><span>Print or save as PDF</span>
                    </button>

                    @if ($business->phone)
                        <a href="tel:{{ $business->phone }}" class="flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-secondary-container/50 text-label-md text-on-secondary-container transition-all hover:bg-secondary-container"><span class="material-symbols-outlined text-base">call</span><span>Contact {{ $business->name }} ({{ $business->phone }})</span></a>
                    @endif

                    @if (in_array($label, ['Pending', 'Confirmed'], true))
                        @if ($cancelProblem)
                            <p class="rounded-xl bg-surface-container-low px-4 py-3 text-center text-body-sm text-on-surface-variant">{{ $cancelProblem }}</p>
                        @else
                            <form method="POST" action="{{ $cancelUrl }}" onsubmit="return confirm('Cancel booking {{ $booking->reference }}?');">
                                @csrf
                                <button type="submit" class="h-11 w-full rounded-xl border border-error text-label-md text-error transition-colors hover:bg-error-container/40">Cancel booking</button>
                            </form>
                        @endif
                    @endif

                    <div class="flex items-center justify-between px-1 pt-2 text-label-md">
                        <a class="flex items-center gap-1 text-secondary hover:underline" href="{{ route('bookings.index') }}"><span class="material-symbols-outlined text-sm">visibility</span> All my bookings</a>
                        <a class="flex items-center gap-1 text-on-surface-variant hover:text-on-surface" href="{{ route('home') }}"><span>Return to Home</span><span class="material-symbols-outlined text-sm">arrow_forward</span></a>
                    </div>
                </div>
            </div>

            <div class="no-print flex flex-col gap-4 rounded-3xl border border-surface-container bg-surface-container-lowest p-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-secondary text-on-secondary"><span class="material-symbols-outlined text-xl">support_agent</span></div>
                    <div class="flex flex-col"><span class="text-headline-sm text-on-surface">Need help?</span><span class="text-body-sm text-on-surface-variant">Call the shop or AngkorRide support</span></div>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-surface-container-low p-3.5">
                    <div class="flex flex-col"><span class="text-label-sm text-on-surface-variant">AngkorRide support</span><span class="text-headline-sm font-bold text-on-surface">+855 12 345 678</span></div>
                    <a href="tel:+85512345678" class="flex items-center gap-1 rounded-lg bg-secondary px-4 py-2 text-label-md text-on-secondary shadow-sm hover:opacity-90"><span class="material-symbols-outlined text-sm">phone_in_talk</span><span>Call</span></a>
                </div>
                <p class="text-body-sm text-on-surface-variant">Free cancellation until {{ \App\Services\BookingWorkflow::FREE_CANCEL_HOURS }} hours before pickup.</p>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var btn = document.getElementById('copyBtn'), txt = document.getElementById('copyText');
    if (!btn) return;
    btn.addEventListener('click', function () {
        var ref = btn.dataset.ref;
        function done() { txt.textContent = 'Copied!'; setTimeout(function () { txt.textContent = 'Copy'; }, 2000); }
        if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(ref).then(done); return; }
        var t = document.createElement('textarea'); t.value = ref; document.body.appendChild(t); t.select();
        try { document.execCommand('copy'); done(); } catch (e) {} document.body.removeChild(t);
    });
})();
</script>
@endsection