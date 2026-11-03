{{-- Rental shop card. Needs $business (from PartnerCards: available_count, from_price, types, cover). --}}
<article class="group relative flex h-full flex-col overflow-hidden rounded-2xl bg-surface-container-lowest shadow-sm transition-all duration-300 hover:shadow-xl">
    <div class="relative h-36 overflow-hidden bg-gradient-to-br from-primary-container to-secondary sm:h-40">
        @if ($business->cover)
            <img src="{{ $business->cover }}" alt="" loading="lazy" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
            <div class="absolute inset-0 bg-gradient-to-t from-primary/40 to-transparent"></div>
        @else
            <div class="flex h-full w-full items-center justify-center text-white/30"><span class="material-symbols-outlined text-6xl">storefront</span></div>
        @endif
        @if ($business->is_verified)
            <span class="absolute right-3 top-3 inline-flex items-center gap-1 rounded-full bg-surface-container-lowest/95 px-2.5 py-1 text-label-sm font-semibold text-secondary shadow-sm"><span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1">verified</span> Verified</span>
        @endif
    </div>
    <div class="relative flex flex-1 flex-col p-4 pt-9 sm:p-5 sm:pt-10">
        <div class="absolute -top-7 left-4 h-14 w-14 overflow-hidden rounded-xl border-2 border-surface-container-lowest bg-secondary-container shadow-md sm:left-5">
            @if ($business->logo_url)
                <img src="{{ $business->logo_url }}" alt="" class="h-full w-full object-cover">
            @else
                <span class="flex h-full w-full items-center justify-center text-headline-sm text-on-secondary-container">{{ \Illuminate\Support\Str::upper(mb_substr($business->name, 0, 1)) }}</span>
            @endif
        </div>
        <h3 class="text-headline-sm text-on-surface transition-colors group-hover:text-secondary">
            <a href="{{ route('business.show', $business) }}" class="after:absolute after:inset-0 after:content-['']">{{ $business->name }}</a>
        </h3>
        <p class="mt-0.5 flex items-center gap-1 text-body-sm text-on-surface-variant"><span class="material-symbols-outlined text-sm">location_on</span>{{ $business->city ?: 'Cambodia' }}</p>
        @if ($business->description)
            <p class="mt-2 line-clamp-2 text-body-sm text-on-surface-variant">{{ $business->description }}</p>
        @endif
        @if (! empty($business->types))
            <div class="mt-3 flex flex-wrap gap-1.5">
                @foreach ($business->types as $t)<span class="rounded bg-surface-container px-2 py-0.5 text-[11px] font-bold text-on-surface">{{ $t }}</span>@endforeach
            </div>
        @endif
        <div class="mt-auto flex items-center justify-between gap-2 pt-4 text-label-md">
            <span class="text-on-surface-variant">{{ $business->available_count }} {{ $business->available_count == 1 ? 'vehicle' : 'vehicles' }} &bull; from <strong class="text-on-surface">${{ rtrim(rtrim(number_format((float) $business->from_price, 2), '0'), '.') }}</strong>/day</span>
            <span class="flex items-center gap-0.5 font-bold text-secondary">View <span class="material-symbols-outlined text-sm transition-transform group-hover:translate-x-1">arrow_forward</span></span>
        </div>
    </div>
</article>