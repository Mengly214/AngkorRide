{{-- Vehicle card for the landing page. Needs $vehicle (business + vehicleType loaded), $saved, $next. --}}
@php
    $title = trim($vehicle->brand . ' ' . $vehicle->model) ?: $vehicle->plate_number;
    $meta = collect([$vehicle->color, $vehicle->year])->filter()->implode(' • ');
    $chips = collect([
        $vehicle->mileage !== null ? number_format($vehicle->mileage) . ' km' : null,
        $vehicle->condition_notes ? \Illuminate\Support\Str::limit($vehicle->condition_notes, 22) : null,
    ])->filter();
@endphp
<article class="group relative flex flex-col justify-between overflow-hidden rounded-2xl bg-surface-container-lowest shadow-sm transition-all duration-300 hover:shadow-xl">
    <div>
        <div class="relative aspect-[16/11] overflow-hidden bg-surface-container">
            @if ($vehicle->photo_url)
                <img src="{{ $vehicle->photo_url }}" alt="{{ $title }}" loading="lazy" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
            @else
                <div class="flex h-full w-full items-center justify-center text-on-surface-variant/40"><span class="material-symbols-outlined text-5xl">two_wheeler</span></div>
            @endif
            @if ($vehicle->vehicleType)
                <span class="absolute bottom-3 left-3 max-w-[80%] truncate rounded-md bg-primary/80 px-2.5 py-1 text-label-sm text-on-primary backdrop-blur-sm">{{ $vehicle->vehicleType->name }}</span>
            @endif
            <div class="absolute right-3 top-3 z-10">
                @include('partials.heart', ['vehicle' => $vehicle, 'saved' => $saved, 'next' => $next, 'size' => 'h-9 w-9'])
            </div>
        </div>
        <div class="flex flex-col gap-1 p-3.5 sm:p-4">
            <div class="flex items-center justify-between gap-2">
                <span class="truncate text-label-sm font-bold uppercase text-secondary">{{ $vehicle->business?->name ?: 'Local shop' }}</span>
                <span class="flex shrink-0 items-center gap-0.5 text-label-sm text-on-surface-variant"><span class="material-symbols-outlined text-sm">location_on</span>{{ $vehicle->business?->city ?: 'Cambodia' }}</span>
            </div>
            <h3 class="text-label-lg leading-snug text-on-surface transition-colors group-hover:text-secondary sm:text-headline-sm">
                @if ($vehicle->business)
                    <a href="{{ route('vehicles.show', ['business' => $vehicle->business, 'vehicle' => $vehicle]) }}" class="after:absolute after:inset-0 after:content-['']">{{ $title }}</a>
                @else
                    {{ $title }}
                @endif
            </h3>
            @if ($meta)<p class="text-body-sm text-on-surface-variant">{{ $meta }}</p>@endif
            @if ($chips->isNotEmpty())
                <div class="hidden flex-wrap gap-1.5 pt-1 sm:flex">
                    @foreach ($chips as $chip)<span class="rounded bg-surface-container px-2 py-0.5 text-[10px] font-bold text-on-surface">{{ $chip }}</span>@endforeach
                </div>
            @endif
        </div>
    </div>
    <div class="flex items-center justify-between gap-2 bg-surface-container-low px-3.5 py-3 sm:px-4">
        <span class="text-headline-sm font-extrabold leading-tight text-on-surface sm:text-headline-md">${{ number_format($vehicle->price_per_day, 0) }} <span class="text-body-sm font-normal text-on-surface-variant">/ day</span></span>
        <span class="rounded-xl bg-secondary px-3 py-2 text-label-md text-on-secondary shadow-sm transition-colors group-hover:bg-on-secondary-container sm:px-4">Book Ride</span>
    </div>
</article>