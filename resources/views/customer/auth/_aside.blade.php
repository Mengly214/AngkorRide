{{-- Benefits panel shown beside the sign-in / register forms (desktop). --}}
<aside class="relative hidden overflow-hidden rounded-3xl border border-surface-container bg-surface-container-lowest p-8 lg:flex lg:flex-col lg:justify-between lg:gap-8 xl:p-10">
    <div class="pointer-events-none absolute -right-16 -top-16 h-72 w-72 rounded-full bg-secondary-container/40 blur-2xl"></div>
    <div class="pointer-events-none absolute -bottom-20 -left-16 h-64 w-64 rounded-full bg-surface-container-high/70 blur-2xl"></div>

    <div class="relative z-10 flex flex-col gap-3">
        <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-secondary-container/60 px-3 py-1 text-label-sm uppercase tracking-wide text-on-secondary-container">
            <span class="material-symbols-outlined text-sm">two_wheeler</span> Siem Reap vehicle rental
        </span>
        <h2 class="text-display-lg text-on-surface">Ride Angkor your way.</h2>
        <p class="text-body-lg text-on-surface-variant">Scooters, bikes and more from local partner shops. Book in a minute, pay the shop when you pick up.</p>
    </div>

    <ul class="relative z-10 flex flex-col gap-4">
        @foreach ([
            ['calendar_month', 'Track every booking', 'See if the shop has confirmed, when to pick up, and your past rentals.'],
            ['event_available', 'Free cancellation', 'Cancel for free until ' . \App\Services\BookingWorkflow::FREE_CANCEL_HOURS . ' hours before pickup.'],
            ['payments', 'Pay at pickup', 'Nothing is charged online. You pay the shop in cash when you collect the vehicle.'],
            ['lock', 'Your bookings stay private', 'Only you can see your bookings once you are signed in.'],
        ] as [$icon, $title, $text])
            <li class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-surface-container-low text-secondary"><span class="material-symbols-outlined text-xl">{{ $icon }}</span></span>
                <span class="flex flex-col"><span class="text-label-lg text-on-surface">{{ $title }}</span><span class="text-body-sm text-on-surface-variant">{{ $text }}</span></span>
            </li>
        @endforeach
    </ul>
</aside>