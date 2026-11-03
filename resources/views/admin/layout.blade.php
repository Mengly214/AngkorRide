@php
    $authUser = auth()->user();
    $pendingShops = \App\Models\Business::where('admin_status', 'pending')->count();
    $refundsDue = \App\Models\Payment::where('status', 'refund_due')->count();

    // Entries without a 'route' are planned but not built yet.
    $nav = [
        ['section' => 'Platform'],
        ['label' => 'Overview', 'icon' => 'dashboard', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard'],
        ['label' => 'Businesses', 'icon' => 'storefront', 'route' => 'admin.businesses.index', 'match' => 'admin.businesses.*', 'badge' => $pendingShops],
        ['label' => 'Users', 'icon' => 'group', 'route' => 'admin.users.index', 'match' => 'admin.users.*'],
        ['label' => 'Bookings', 'icon' => 'calendar_today', 'route' => 'admin.bookings.index', 'match' => 'admin.bookings.*'],
        ['label' => 'Payments', 'icon' => 'payments', 'route' => 'admin.payments.index', 'match' => 'admin.payments.*', 'badge' => $refundsDue],
        ['label' => 'Vouchers', 'icon' => 'sell', 'route' => 'admin.vouchers.index', 'match' => 'admin.vouchers.*'],
        ['section' => 'Coming soon'],
        ['label' => 'Vehicles', 'icon' => 'two_wheeler'],
        ['label' => 'Reports & Analytics', 'icon' => 'monitoring'],
        ['label' => 'Reviews & Reports', 'icon' => 'flag'],
        ['label' => 'Settings & Audit Log', 'icon' => 'settings'],
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
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Overview') — AngkorRide Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface font-explore text-body-md text-on-surface antialiased">

    <div id="sidebarBackdrop" class="fixed inset-0 z-40 hidden bg-on-surface/40 lg:hidden"></div>

    <aside id="sidebar"
           class="fixed left-0 top-0 z-50 flex h-screen w-60 -translate-x-full flex-col justify-between overflow-y-auto bg-primary-container text-surface-container-lowest transition-transform duration-200 lg:translate-x-0">
        <div class="flex flex-col">
            <div class="flex items-center justify-between p-space-md">
                <div class="flex flex-col">
                    <span class="text-headline-sm leading-none tracking-tight text-surface-container-lowest">AngkorRide</span>
                    <span class="mt-1 text-label-sm uppercase tracking-wider text-secondary-fixed">Admin</span>
                </div>
                <button type="button" id="sidebarClose" class="rounded-lg p-1 text-on-primary-container hover:bg-inverse-surface lg:hidden" aria-label="Close menu">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <nav class="flex flex-col gap-1 px-space-md pb-space-md" aria-label="Admin">
                @foreach ($nav as $item)
                    @if (isset($item['section']))
                        <span class="mb-1 mt-space-md px-space-sm text-label-sm uppercase tracking-wider text-on-primary-container">{{ $item['section'] }}</span>
                    @elseif (isset($item['route']))
                        @php $isActive = request()->routeIs($item['match']); @endphp
                        <a href="{{ route($item['route']) }}" @if ($isActive) aria-current="page" @endif
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
                        <span title="Coming soon" aria-disabled="true" class="flex cursor-not-allowed items-center rounded-lg px-space-sm py-2 text-inverse-on-surface/40">
                            <span class="flex min-w-0 items-center gap-space-xs">
                                <span class="material-symbols-outlined text-[20px]">{{ $item['icon'] }}</span>
                                <span class="truncate text-label-lg">{{ $item['label'] }}</span>
                            </span>
                        </span>
                    @endif
                @endforeach
            </nav>
        </div>

        <form method="POST" action="{{ route('admin.logout') }}" class="border-t border-inverse-surface p-space-md">
            @csrf
            <button type="submit" class="flex w-full items-center gap-space-xs rounded-lg px-space-sm py-2 text-label-lg text-inverse-on-surface transition-colors hover:bg-inverse-surface hover:text-surface-container-lowest">
                <span class="material-symbols-outlined text-[20px]">logout</span> Log out
            </button>
        </form>
    </aside>

    <header class="fixed left-0 right-0 top-0 z-30 h-18 bg-surface-container-lowest/90 shadow-[0_1px_8px_rgba(0,0,0,0.04)] backdrop-blur-xl lg:left-60">
        <div class="flex h-full w-full items-center justify-between gap-space-md px-4 sm:px-6 lg:px-8">
            <div class="flex min-w-0 items-center gap-space-sm">
                <button type="button" id="sidebarOpen" class="rounded-lg p-2 text-on-surface-variant hover:bg-surface-container-low lg:hidden" aria-label="Open menu">
                    <span class="material-symbols-outlined text-[24px]">menu</span>
                </button>
                <span class="flex items-center gap-1.5 text-label-lg text-on-surface-variant"><span class="material-symbols-outlined text-[20px] text-secondary">admin_panel_settings</span> Platform management</span>
            </div>
            <div class="flex items-center gap-space-sm">
                <a href="{{ route('home') }}" target="_blank" rel="noopener" class="hidden items-center gap-1 text-label-md text-on-surface-variant hover:text-secondary sm:flex">View site <span class="material-symbols-outlined text-[16px]">open_in_new</span></a>
                <div class="h-8 w-px bg-outline-variant"></div>
                <span class="flex items-center gap-space-xs">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-secondary-container text-label-md text-on-secondary-container">{{ $initials }}</span>
                    <span class="hidden flex-col sm:flex">
                        <span class="text-label-md leading-tight text-on-surface">{{ $authUser->full_name ?? '' }}</span>
                        <span class="text-label-sm leading-tight text-on-surface-variant">Administrator</span>
                    </span>
                </span>
            </div>
        </div>
    </header>

    <main class="pt-18 lg:pl-60">
        <div class="mx-auto flex max-w-[1400px] flex-col gap-space-lg px-4 py-space-lg sm:px-6 lg:px-8">
            @if (session('status'))
                <div role="status" class="flex items-center gap-space-xs rounded-xl bg-tertiary-fixed px-space-md py-3 text-label-lg text-on-tertiary-fixed">
                    <span class="material-symbols-outlined text-[20px]">check_circle</span> {{ session('status') }}
                </div>
            @endif
            @if ($errors->any())
                <div role="alert" class="flex items-center gap-space-xs rounded-xl bg-error-container px-space-md py-3 text-label-lg text-on-error-container">
                    <span class="material-symbols-outlined text-[20px]">error</span> {{ $errors->first() }}
                </div>
            @endif

            <div class="flex flex-col justify-between gap-space-sm sm:flex-row sm:items-center">
                <h1 class="text-headline-lg text-on-surface">@yield('title', 'Overview')</h1>
                @yield('header-actions')
            </div>

            @yield('content')
        </div>
    </main>

    <script>
        (function () {
            var sidebar = document.getElementById('sidebar'), backdrop = document.getElementById('sidebarBackdrop');
            function setOpen(o) { sidebar.classList.toggle('-translate-x-full', !o); backdrop.classList.toggle('hidden', !o); }
            document.getElementById('sidebarOpen').addEventListener('click', function () { setOpen(true); });
            document.getElementById('sidebarClose').addEventListener('click', function () { setOpen(false); });
            backdrop.addEventListener('click', function () { setOpen(false); });
        })();
    </script>
</body>
</html>