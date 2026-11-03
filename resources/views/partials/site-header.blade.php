{{-- Shared customer header. Pass $active = 'home' | 'explore' to highlight a link. --}}
@php
    $active = $active ?? null;
    $me = auth()->user();
    $isCustomer = $me && $me->role === 'customer';
    $link = 'px-3 py-2 rounded-lg text-label-lg text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface transition-colors';
    $on = 'px-3 py-2 rounded-lg text-label-lg bg-secondary-container text-on-secondary-container';
@endphp
<header class="fixed top-0 z-50 w-full bg-surface/90 shadow-[0_1px_8px_rgba(0,0,0,0.04)] backdrop-blur-xl">
    <div class="mx-auto flex h-20 max-w-7xl items-center justify-between gap-space-md px-4 sm:px-6 lg:px-12">
        <div class="flex items-center gap-space-lg">
            <a class="flex flex-col" href="{{ route('home') }}" aria-label="AngkorRide home">
                <span class="text-headline-sm tracking-tight text-on-surface">AngkorRide</span>
                <span class="text-label-sm uppercase text-secondary">Cambodia</span>
            </a>
            <nav class="ml-space-sm hidden items-center gap-1 lg:flex" aria-label="Main">
                <a href="{{ route('explore') }}" class="{{ $active === 'explore' ? $on : $link }}" @if ($active === 'explore') aria-current="page" @endif>Explore vehicles</a>
                <a href="{{ route('partners.index') }}" class="{{ $active === 'partners' ? $on : $link }}" @if ($active === 'partners') aria-current="page" @endif>Partners</a>
                <a href="{{ route('home') }}#how-it-works" class="{{ $link }}">How it works</a>
                <a href="{{ route('home') }}#partners" class="{{ $link }}">Become a partner</a>
            </nav>
        </div>

        <div class="flex items-center gap-2 sm:gap-space-md">
            <a class="hidden items-center gap-space-xs rounded-lg bg-surface-container-low px-3 py-1.5 text-on-surface-variant transition-colors hover:bg-surface-container-high hover:text-on-surface xl:flex" href="tel:+85512345678">
                <span class="material-symbols-outlined text-base text-secondary">support_agent</span>
                <span class="text-label-sm">+855 12 345 678</span>
            </a>
            <a href="{{ route('portal.register') }}" class="hidden items-center gap-1 rounded-full border border-secondary/40 px-3.5 py-1.5 text-label-md text-secondary transition-colors hover:bg-secondary-container/50 md:inline-flex">
                List your vehicle
            </a>
            <a class="flex items-center gap-space-xs rounded-full bg-surface-container-lowest py-1 pl-2 pr-1 shadow-[0_1px_4px_rgba(0,0,0,0.06)] transition-colors hover:bg-surface-container-high" href="{{ route('bookings.index') }}">
                <span class="hidden whitespace-nowrap px-2 text-label-sm text-on-surface sm:inline">My Bookings</span>
                <span class="material-symbols-outlined inline-flex h-8 w-8 items-center justify-center rounded-full bg-surface-container-high text-base text-on-surface-variant">person</span>
            </a>
            @if ($isCustomer)
                <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">@csrf
                    <button type="submit" class="whitespace-nowrap rounded-lg bg-surface-container-low px-3 py-1.5 text-label-sm text-on-surface-variant hover:bg-surface-container-high" title="Signed in as {{ $me->full_name }}">Sign out</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="whitespace-nowrap rounded-lg bg-primary px-3.5 py-1.5 text-label-sm text-on-primary hover:bg-primary/90">Sign in</a>
            @endif
            <button type="button" id="menuBtn" class="rounded-lg p-2 text-on-surface-variant hover:bg-surface-container-high lg:hidden" aria-label="Open menu" aria-expanded="false" aria-controls="mobileMenu">
                <span class="material-symbols-outlined text-[24px]">menu</span>
            </button>
        </div>
    </div>

    <div id="mobileMenu" class="hidden border-t border-surface-container bg-surface-container-lowest px-4 pb-4 pt-2 shadow-lg lg:hidden">
        <nav class="flex flex-col" aria-label="Mobile">
            <a href="{{ route('explore') }}" class="rounded-lg px-3 py-3 text-label-lg text-on-surface hover:bg-surface-container-low">Explore vehicles</a>
            <a href="{{ route('partners.index') }}" class="rounded-lg px-3 py-3 text-label-lg text-on-surface hover:bg-surface-container-low">Partners</a>
            <a href="{{ route('home') }}#how-it-works" class="rounded-lg px-3 py-3 text-label-lg text-on-surface hover:bg-surface-container-low">How it works</a>
            <a href="{{ route('home') }}#partners" class="rounded-lg px-3 py-3 text-label-lg text-on-surface hover:bg-surface-container-low">Become a partner</a>
            <a href="{{ route('portal.register') }}" class="rounded-lg px-3 py-3 text-label-lg text-secondary hover:bg-surface-container-low">List your vehicle</a>
            <a href="tel:+85512345678" class="rounded-lg px-3 py-3 text-label-lg text-on-surface-variant hover:bg-surface-container-low">Call +855 12 345 678</a>
            @if ($isCustomer)
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button type="submit" class="w-full rounded-lg px-3 py-3 text-left text-label-lg text-on-surface-variant hover:bg-surface-container-low">Sign out</button>
                </form>
            @endif
        </nav>
    </div>
</header>
<script>
    (function () {
        var b = document.getElementById('menuBtn'), m = document.getElementById('mobileMenu');
        if (!b || !m) return;
        b.addEventListener('click', function () {
            var open = m.classList.toggle('hidden') === false;
            b.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        m.addEventListener('click', function (e) { if (e.target.closest('a')) m.classList.add('hidden'); });
    })();
</script>