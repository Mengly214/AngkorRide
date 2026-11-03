@php
    $authUser = auth()->user();
    $pendingBookingsCount = $pendingBookingsCount ?? 0;

    // Sidebar: pages from the Business Dashboard spec.
    // Entries without a 'route' are planned but not built yet.
    $nav = [
        ['section' => 'Main Operations'],
        ['label' => 'Overview', 'icon' => 'dashboard', 'route' => 'portal.dashboard', 'match' => 'portal.dashboard'],
        ['label' => 'Bookings', 'icon' => 'calendar_today', 'route' => 'portal.bookings.index', 'match' => 'portal.bookings.*', 'badge' => $pendingBookingsCount],
        ['label' => 'My Vehicles', 'icon' => 'two_wheeler', 'route' => 'portal.vehicles.index', 'match' => 'portal.vehicles.*'],
        ['label' => 'Availability Calendar', 'icon' => 'date_range', 'route' => 'portal.availability.index', 'match' => 'portal.availability.*'],
        ['label' => 'Customers', 'icon' => 'group', 'route' => 'portal.customers.index', 'match' => 'portal.customers.*'],
        ['label' => 'Earnings & Transactions', 'icon' => 'payments', 'route' => 'portal.earnings.index', 'match' => 'portal.earnings.*'],
        ['section' => 'Growth'],
        ['label' => 'Vouchers & Promotions', 'icon' => 'sell', 'route' => 'portal.vouchers.index', 'match' => 'portal.vouchers.*'],
        ['label' => 'Reports & Analytics', 'icon' => 'monitoring'],
        ['section' => 'Management'],
        ['label' => 'Business Profile', 'icon' => 'storefront', 'route' => 'portal.profile.edit', 'match' => 'portal.profile.*'],
        ['label' => 'Settings', 'icon' => 'settings', 'route' => 'portal.settings.edit', 'match' => 'portal.settings.*'],
    ];

    $initials = $authUser
        ? \Illuminate\Support\Str::of($authUser->full_name)->explode(' ')->filter()->take(2)
            ->map(fn ($p) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($p, 0, 1)))->join('')
        : '';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Overview') — AngkorRide Business Portal</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface font-explore text-body-md text-on-surface antialiased">

    {{-- Mobile backdrop for the sidebar --}}
    <div id="sidebarBackdrop" class="fixed inset-0 z-40 hidden bg-on-surface/40 lg:hidden"></div>

    {{-- Sidebar --}}
    <aside id="sidebar"
           class="fixed left-0 top-0 z-50 flex h-screen w-60 -translate-x-full flex-col justify-between overflow-y-auto bg-primary-container text-surface-container-lowest transition-transform duration-200 lg:translate-x-0">
        <div class="flex flex-col">
            <div class="flex items-center justify-between p-space-md">
                <div class="flex flex-col">
                    <span class="text-headline-sm leading-none tracking-tight text-surface-container-lowest">AngkorRide</span>
                    <span class="mt-1 text-label-sm uppercase tracking-wider text-secondary-fixed">Business Portal</span>
                </div>
                <button type="button" id="sidebarClose" class="rounded-lg p-1 text-on-primary-container hover:bg-inverse-surface lg:hidden" aria-label="Close menu">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            @isset($business)
                <div class="mx-space-md mb-space-sm rounded-lg bg-inverse-surface p-space-sm">
                    <div class="flex min-w-0 flex-col">
                        <span class="truncate text-label-lg font-semibold text-surface-container-lowest">{{ $business->name }}</span>
                        <span class="flex items-center gap-1 text-label-sm text-secondary-fixed-dim">
                            @if ($business->is_verified)
                                <span class="material-symbols-outlined text-[14px]">verified</span> Verified &bull; {{ $business->city }}
                            @else
                                <span class="material-symbols-outlined text-[14px]">storefront</span> {{ $business->city }}
                            @endif
                        </span>
                    </div>
                </div>
            @endisset

            <nav class="flex flex-col gap-1 px-space-md pb-space-md" aria-label="Business portal">
                @foreach ($nav as $item)
                    @if (isset($item['section']))
                        <span class="mb-1 mt-space-md px-space-sm text-label-sm uppercase tracking-wider text-on-primary-container">{{ $item['section'] }}</span>
                    @elseif (isset($item['route']))
                        @php $isActive = request()->routeIs($item['match']); @endphp
                        <a href="{{ route($item['route']) }}"
                           @if ($isActive) aria-current="page" @endif
                           class="flex items-center justify-between rounded-lg px-space-sm py-2 transition-colors {{ $isActive ? 'bg-surface-container-lowest font-semibold text-on-surface shadow-sm' : 'text-inverse-on-surface hover:bg-inverse-surface hover:text-surface-container-lowest' }}">
                            <span class="flex min-w-0 items-center gap-space-xs">
                                <span class="material-symbols-outlined text-[20px]">{{ $item['icon'] }}</span>
                                <span class="truncate text-label-lg">{{ $item['label'] }}</span>
                            </span>
                            @if (($item['badge'] ?? 0) > 0)
                                <span class="rounded-full bg-secondary px-2 py-0.5 text-label-sm text-on-secondary">{{ $item['badge'] }}</span>
                            @endif
                        </a>
                    @else
                        <span title="Coming soon" aria-disabled="true"
                              class="flex cursor-not-allowed items-center justify-between rounded-lg px-space-sm py-2 text-inverse-on-surface/40">
                            <span class="flex min-w-0 items-center gap-space-xs">
                                <span class="material-symbols-outlined text-[20px]">{{ $item['icon'] }}</span>
                                <span class="truncate text-label-lg">{{ $item['label'] }}</span>
                            </span>
                            @if (($item['badge'] ?? 0) > 0)
                                <span class="rounded-full bg-secondary px-2 py-0.5 text-label-sm text-on-secondary">{{ $item['badge'] }}</span>
                            @endif
                        </span>
                    @endif
                @endforeach
            </nav>
        </div>

        <form method="POST" action="{{ route('portal.logout') }}" class="border-t border-inverse-surface p-space-md">
            @csrf
            <button type="submit" class="flex w-full items-center gap-space-xs rounded-lg px-space-sm py-2 text-label-lg text-inverse-on-surface transition-colors hover:bg-inverse-surface hover:text-surface-container-lowest">
                <span class="material-symbols-outlined text-[20px]">logout</span> Log out
            </button>
        </form>
    </aside>

    {{-- Top bar --}}
    <header class="fixed left-0 right-0 top-0 z-30 h-18 bg-surface-container-lowest/90 shadow-[0_1px_8px_rgba(0,0,0,0.04)] backdrop-blur-xl lg:left-60">
        <div class="flex h-full w-full items-center justify-between gap-space-md px-4 sm:px-6 lg:px-8">
            <div class="flex min-w-0 items-center gap-space-sm">
                <button type="button" id="sidebarOpen" class="rounded-lg p-2 text-on-surface-variant hover:bg-surface-container-low lg:hidden" aria-label="Open menu">
                    <span class="material-symbols-outlined text-[24px]">menu</span>
                </button>
                @isset($business)
                    <div class="flex min-w-0 items-center gap-space-xs text-on-surface-variant">
                        <span class="material-symbols-outlined text-[20px] text-secondary">location_on</span>
                        <span class="hidden text-label-lg font-semibold text-on-surface sm:inline">{{ $business->city }}</span>
                        @if ($business->address)
                            <span class="hidden text-outline sm:inline">&bull;</span>
                            <span class="hidden truncate text-body-md text-on-surface-variant md:inline">{{ $business->address }}</span>
                        @endif
                    </div>
                @endisset
            </div>

            <div class="flex items-center gap-space-sm sm:gap-space-md">
                <span title="Notifications are coming soon" class="hidden cursor-not-allowed rounded-lg p-2 text-outline sm:inline-flex">
                    <span class="material-symbols-outlined text-[22px]">notifications</span>
                </span>

                <div class="h-8 w-px bg-outline-variant"></div>

                @if ($authUser)
                    <details class="relative">
                        <summary class="flex cursor-pointer list-none items-center gap-space-xs [&::-webkit-details-marker]:hidden">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-secondary-container text-label-md text-on-secondary-container">{{ $initials }}</span>
                            <span class="hidden flex-col sm:flex">
                                <span class="text-label-md leading-tight text-on-surface">{{ $authUser->full_name }}</span>
                                <span class="text-label-sm leading-tight text-on-surface-variant">Business owner</span>
                            </span>
                            <span class="material-symbols-outlined text-[18px] text-on-surface-variant">expand_more</span>
                        </summary>
                        <div class="absolute right-0 top-full mt-2 w-48 rounded-xl bg-surface-container-lowest p-space-xs shadow-lg ring-1 ring-surface-container">
                            <form method="POST" action="{{ route('portal.logout') }}">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-space-xs rounded-lg px-space-sm py-2 text-label-lg text-on-surface hover:bg-surface-container-low">
                                    <span class="material-symbols-outlined text-[18px]">logout</span> Log out
                                </button>
                            </form>
                        </div>
                    </details>
                @endif
            </div>
        </div>
    </header>

    {{-- Page --}}
    <main class="pt-18 lg:pl-60">
        <div class="mx-auto flex max-w-[1400px] flex-col gap-space-lg px-4 py-space-lg sm:px-6 lg:px-8">

            @if (session('status'))
                <div role="status" class="flex items-center gap-space-xs rounded-xl bg-tertiary-fixed px-space-md py-3 text-label-lg text-on-tertiary-fixed">
                    <span class="material-symbols-outlined text-[20px]">check_circle</span>
                    {{ session('status') }}
                </div>
            @endif

            @isset($business)
                @if ($business->admin_status === 'pending')
                    <div role="status" class="flex items-start gap-space-xs rounded-xl bg-secondary-container px-space-md py-3 text-label-lg text-on-secondary-container">
                        <span class="material-symbols-outlined text-[20px]">hourglass_top</span>
                        <span>Your shop is waiting for AngkorRide to review it. Customers can already find it, but it won't carry the Verified badge until it is approved.</span>
                    </div>
                @elseif (in_array($business->admin_status, ['rejected', 'suspended'], true))
                    <div role="alert" class="flex items-start gap-space-xs rounded-xl bg-error-container px-space-md py-3 text-label-lg text-on-error-container">
                        <span class="material-symbols-outlined text-[20px]">block</span>
                        <span>Your shop is {{ $business->admin_status === 'rejected' ? 'not approved' : 'suspended' }} and hidden from customers.@if ($business->admin_note) Reason: {{ $business->admin_note }}@endif Please contact AngkorRide to resolve this.</span>
                    </div>
                @endif
            @endisset

            @if ($errors->has('booking'))
                <div role="alert" class="flex items-center gap-space-xs rounded-xl bg-error-container px-space-md py-3 text-label-lg text-on-error-container">
                    <span class="material-symbols-outlined text-[20px]">error</span>
                    {{ $errors->first('booking') }}
                </div>
            @endif

            @if ($errors->has('voucher'))
                <div role="alert" class="flex items-center gap-space-xs rounded-xl bg-error-container px-space-md py-3 text-label-lg text-on-error-container">
                    <span class="material-symbols-outlined text-[20px]">error</span>
                    {{ $errors->first('voucher') }}
                </div>
            @endif

            @hasSection('custom-heading')
                {{-- The page draws its own heading (e.g. the Overview greeting) --}}
            @else
                <div class="flex flex-col justify-between gap-space-sm sm:flex-row sm:items-center">
                    <h1 class="text-headline-lg text-on-surface">@yield('title', 'Overview')</h1>
                    @yield('header-actions')
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <script>
        (function () {
            var sidebar = document.getElementById('sidebar');
            var backdrop = document.getElementById('sidebarBackdrop');
            function setOpen(open) {
                sidebar.classList.toggle('-translate-x-full', !open);
                backdrop.classList.toggle('hidden', !open);
            }
            document.getElementById('sidebarOpen').addEventListener('click', function () { setOpen(true); });
            document.getElementById('sidebarClose').addEventListener('click', function () { setOpen(false); });
            backdrop.addEventListener('click', function () { setOpen(false); });
        })();
    </script>
</body>
</html>