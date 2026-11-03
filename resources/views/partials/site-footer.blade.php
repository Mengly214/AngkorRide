@php
    $footTypes = \App\Models\VehicleType::where('is_active', true)->orderBy('name')->limit(5)->get();
    $freeHours = \App\Services\BookingWorkflow::FREE_CANCEL_HOURS;
@endphp
<footer class="w-full bg-surface-container-lowest shadow-[0_-4px_20px_0_rgba(15,23,42,0.03)]">
    <div class="mx-auto max-w-7xl px-4 pb-8 pt-14 sm:px-6 lg:px-12">
        <div class="grid grid-cols-2 gap-x-6 gap-y-10 lg:grid-cols-4">
            <div class="col-span-2 flex flex-col gap-space-sm lg:col-span-1">
                <span class="text-headline-sm text-on-surface">AngkorRide</span>
                <p class="max-w-xs text-body-sm text-on-surface-variant">Bicycles, scooters and motorbikes from local rental shops. Find a ride, book it online and pay when you pick it up.</p>
            </div>
            <div class="flex flex-col gap-space-sm">
                <span class="text-label-lg text-on-surface">Popular fleets</span>
                <a class="text-body-sm text-on-surface-variant hover:text-secondary" href="{{ route('explore') }}">All vehicles</a>
                @foreach ($footTypes as $t)
                    <a class="text-body-sm text-on-surface-variant hover:text-secondary" href="{{ route('explore', ['type' => $t->id]) }}">{{ $t->name }}</a>
                @endforeach
            </div>
            <div class="flex flex-col gap-space-sm">
                <span class="text-label-lg text-on-surface">For shops</span>
                <a class="text-body-sm text-on-surface-variant hover:text-secondary" href="{{ route('portal.register') }}">Become a partner</a>
                <a class="text-body-sm text-on-surface-variant hover:text-secondary" href="{{ route('portal.login') }}">Partner sign in</a>
                <a class="text-body-sm text-on-surface-variant hover:text-secondary" href="{{ route('bookings.index') }}">My bookings</a>
            </div>
            <div class="flex flex-col gap-space-sm">
                <span class="text-label-lg text-on-surface">Booking basics</span>
                <span class="flex items-center gap-space-xs text-body-sm text-on-surface-variant"><span class="material-symbols-outlined text-sm text-secondary">payments</span>Pay at pickup, no card online</span>
                <span class="flex items-center gap-space-xs text-body-sm text-on-surface-variant"><span class="material-symbols-outlined text-sm text-secondary">event_available</span>Free cancellation up to {{ $freeHours }}h before</span>
                <a class="flex items-center gap-space-xs text-body-sm text-on-surface-variant hover:text-secondary" href="tel:+85512345678"><span class="material-symbols-outlined text-sm text-secondary">support_agent</span>+855 12 345 678</a>
            </div>
        </div>
        <div class="mt-10 flex flex-col items-center justify-between gap-2 rounded-xl bg-surface-container-low px-6 py-4 text-body-sm text-on-surface-variant sm:flex-row">
            <span>&copy; {{ date('Y') }} AngkorRide. All rights reserved.</span>
            <span>Made in Siem Reap, Cambodia</span>
        </div>
    </div>
</footer>