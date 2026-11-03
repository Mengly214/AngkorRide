@extends('portal.layout')

@section('title', 'Overview')
@section('custom-heading', '1')

@section('content')
@php
    use Illuminate\Support\Str;

    // Links that keep the current filters (range, tab, metric) in the URL.
    $url = fn (array $override = [], string $fragment = '') => route(
        'portal.dashboard',
        array_filter(array_merge(request()->query(), $override), fn ($v) => $v !== null && $v !== '')
    ) . $fragment;

    $money = fn ($v) => '$' . number_format($v, fmod((float) $v, 1.0) == 0 ? 0 : 2);

    $statusBadge = fn (string $s) => match ($s) {
        'pending'   => ['Pending',   'bg-amber-100 text-amber-800'],
        'confirmed' => ['Confirmed', 'bg-secondary-container text-on-secondary-container'],
        'ongoing'   => ['Active',    'bg-surface-container-high text-on-surface'],
        'completed' => ['Completed', 'bg-tertiary-fixed text-on-tertiary-fixed'],
        'cancelled' => ['Cancelled', 'bg-error-container text-on-error-container'],
        default     => [Str::headline($s), 'bg-surface-container text-on-surface-variant'],
    };

    $initialsOf = fn (?string $name) => Str::of($name ?: '?')->explode(' ')->filter()->take(2)
        ->map(fn ($p) => Str::upper(Str::substr($p, 0, 1)))->join('');

    $dateSpan = function ($start, $end) {
        if ($start->isSameDay($end)) {
            return $start->format('M j');
        }
        return $start->format('M j') . ' – ' . ($start->isSameMonth($end) ? $end->format('j') : $end->format('M j'));
    };

    $topTypes = $byType->take(2);
    $typeDots = ['bg-secondary', 'bg-primary-container'];
    $fleetTotal = max($fleet['total'], 1);
    $segment = fn (int $n) => round($n / $fleetTotal * 100, 1);

    $events = $pickups->map(fn ($b) => ['kind' => 'Pickup', 'at' => $b->start_datetime, 'booking' => $b])
        ->concat($returns->map(fn ($b) => ['kind' => 'Return', 'at' => $b->end_datetime, 'booking' => $b]))
        ->sortBy(fn ($e) => $e['at']->timestamp);

    $bucketCount = count($chart['buckets']);
    $firstLabel = $chart['buckets'][0]['label'] ?? '';
    $midLabel = $chart['buckets'][intdiv($bucketCount, 2)]['label'] ?? '';
    $lastLabel = $chart['buckets'][$bucketCount - 1]['label'] ?? '';
@endphp

{{-- Greeting + primary actions --}}
<div class="flex flex-col justify-between gap-space-md lg:flex-row lg:items-center">
    <div class="flex flex-col gap-1">
        <h1 class="text-headline-lg text-on-surface sm:text-display-lg">{{ $greeting }}, {{ $business->name }} <span aria-hidden="true">👋</span></h1>
        <p class="text-body-md text-on-surface-variant">{{ $today }} &mdash; here's what's happening with your fleet.</p>
    </div>
    <div class="flex flex-wrap items-center gap-space-sm">
        <a href="{{ route('portal.vehicles.index') }}"
           class="flex items-center gap-2 rounded-lg bg-surface-container px-space-md py-2.5 text-label-lg text-on-surface shadow-sm transition-colors hover:bg-surface-container-high">
            <span class="material-symbols-outlined text-[18px]">two_wheeler</span> My Vehicles
        </a>
        <a href="{{ route('portal.vehicles.create') }}"
           class="flex items-center gap-2 rounded-lg bg-secondary px-space-md py-2.5 text-label-lg text-on-secondary shadow-sm transition-colors hover:bg-on-secondary-container">
            <span class="material-symbols-outlined text-[20px]">add_circle</span> Add Vehicle
        </a>
    </div>
</div>

{{-- Period filter --}}
<div class="flex flex-wrap items-center gap-space-sm">
    <span class="text-label-md uppercase tracking-wider text-on-surface-variant">Period</span>

    @foreach ([['today', 'Today'], ['7d', '7 days'], ['30d', '30 days']] as [$key, $label])
        <a href="{{ $url(['range' => $key, 'from' => null, 'to' => null]) }}"
           @if ($range['key'] === $key) aria-current="true" @endif
           class="rounded-full px-3.5 py-1.5 text-label-md transition-colors {{ $range['key'] === $key ? 'bg-secondary text-on-secondary shadow-sm' : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high' }}">
            {{ $label }}
        </a>
    @endforeach

    <details class="relative" @if ($range['key'] === 'custom') open @endif>
        <summary class="cursor-pointer list-none rounded-full px-3.5 py-1.5 text-label-md transition-colors [&::-webkit-details-marker]:hidden {{ $range['key'] === 'custom' ? 'bg-secondary text-on-secondary shadow-sm' : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high' }}">
            Custom range
        </summary>
        <form method="GET" action="{{ route('portal.dashboard') }}"
              class="absolute left-0 top-full z-20 mt-2 flex w-72 flex-col gap-space-sm rounded-xl bg-surface-container-lowest p-space-md shadow-lg ring-1 ring-surface-container">
            <input type="hidden" name="range" value="custom">
            @foreach (['metric', 'tab'] as $keep)
                @if (is_string(request()->query($keep)) && request()->query($keep) !== '')
                    <input type="hidden" name="{{ $keep }}" value="{{ request()->query($keep) }}">
                @endif
            @endforeach
            <label class="flex flex-col gap-1 text-label-md text-on-surface-variant">From
                <input type="date" name="from" value="{{ $range['from'] }}" required
                       class="h-10 rounded-lg bg-surface-container-low px-3 text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-secondary">
            </label>
            <label class="flex flex-col gap-1 text-label-md text-on-surface-variant">To
                <input type="date" name="to" value="{{ $range['to'] }}" required
                       class="h-10 rounded-lg bg-surface-container-low px-3 text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-secondary">
            </label>
            <button type="submit" class="h-10 rounded-lg bg-secondary text-label-lg text-on-secondary transition-colors hover:bg-on-secondary-container">Apply</button>
        </form>
    </details>

    <span class="text-body-sm text-on-surface-variant">Showing {{ $range['label'] }}</span>
</div>

{{-- KPI cards --}}
<div class="grid grid-cols-1 gap-space-md sm:grid-cols-2 xl:grid-cols-4">
    {{-- Total vehicles --}}
    <div class="flex flex-col justify-between rounded-xl bg-surface-container-lowest p-space-lg shadow-sm">
        <div class="flex items-start justify-between">
            <div class="flex flex-col gap-1">
                <span class="text-label-md uppercase tracking-wider text-on-surface-variant">Total Vehicles</span>
                <span class="mt-1 text-display-lg leading-none text-on-surface">{{ $fleet['total'] }}</span>
            </div>
            @if ($fleet['maintenance'] > 0)
                <span class="flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-label-sm text-amber-800">
                    <span class="material-symbols-outlined text-[14px]">build</span> {{ $fleet['maintenance'] }} in service
                </span>
            @endif
        </div>
        <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 pt-3 text-body-sm text-on-surface-variant">
            @forelse ($topTypes as $typeName => $info)
                <span class="flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full {{ $typeDots[$loop->index] ?? 'bg-outline' }}"></span> {{ $info['total'] }} {{ $typeName }}
                </span>
            @empty
                <a href="{{ route('portal.vehicles.create') }}" class="font-semibold text-secondary hover:underline">Add your first vehicle</a>
            @endforelse
        </div>
    </div>

    {{-- Available --}}
    <div class="flex flex-col justify-between rounded-xl bg-surface-container-lowest p-space-lg shadow-sm">
        <div class="flex items-start justify-between">
            <div class="flex flex-col gap-1">
                <span class="text-label-md uppercase tracking-wider text-on-surface-variant">Available</span>
                <span class="mt-1 text-display-lg leading-none text-secondary">{{ $fleet['available'] }}</span>
            </div>
            <span class="rounded-full bg-secondary-container px-2.5 py-1 text-label-sm text-on-secondary-container">{{ $fleet['available_pct'] }}% available</span>
        </div>
        <div class="mt-4 flex items-center gap-1 pt-3 text-body-sm text-on-surface-variant">
            <span class="material-symbols-outlined text-[16px] text-secondary">bolt</span>
            <span>Ready to rent right now</span>
        </div>
    </div>

    {{-- Bookings in period --}}
    <div class="flex flex-col justify-between rounded-xl bg-surface-container-lowest p-space-lg shadow-sm">
        <div class="flex items-start justify-between">
            <div class="flex flex-col gap-1">
                <span class="text-label-md uppercase tracking-wider text-on-surface-variant">Bookings</span>
                <span class="mt-1 text-display-lg leading-none text-on-surface">{{ $kpi['bookings'] }}</span>
            </div>
            <span class="rounded-full bg-surface-container px-2.5 py-1 text-label-sm text-on-surface-variant">{{ $range['label'] }}</span>
        </div>
        <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 pt-3 text-body-sm text-on-surface-variant">
            <span class="flex items-center gap-1.5 {{ $kpi['pending'] > 0 ? 'font-semibold text-error' : '' }}">
                @if ($kpi['pending'] > 0)<span class="h-1.5 w-1.5 rounded-full bg-error"></span>@endif
                {{ $kpi['pending'] }} pending
            </span>
            <span class="text-outline-variant">&bull;</span>
            <span class="{{ $kpi['active'] > 0 ? 'font-semibold text-secondary' : '' }}">{{ $kpi['active'] }} active now</span>
        </div>
    </div>

    {{-- Revenue --}}
    <div class="flex flex-col justify-between rounded-xl bg-surface-container-lowest p-space-lg shadow-sm">
        <div class="flex items-start justify-between">
            <div class="flex flex-col gap-1">
                <span class="text-label-md uppercase tracking-wider text-on-surface-variant">Revenue</span>
                <span class="mt-1 text-display-lg leading-none text-on-surface">{{ $money($kpi['revenue']) }}</span>
            </div>
            <span class="rounded-full bg-tertiary-fixed px-2.5 py-1 text-label-sm text-on-tertiary-fixed">{{ $kpi['paid_bookings'] }} paid</span>
        </div>
        <div class="mt-4 flex items-center gap-1.5 pt-3 text-body-sm text-on-surface-variant">
            <span class="material-symbols-outlined text-[16px] text-secondary">payments</span>
            <span>Paid bookings only &bull; {{ $range['label'] }}</span>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 items-start gap-space-lg lg:grid-cols-12">

    {{-- ===================== Left column ===================== --}}
    <div class="flex flex-col gap-space-lg lg:col-span-8">

        {{-- Trend chart --}}
        <section class="rounded-xl bg-surface-container-lowest p-space-lg shadow-sm" aria-labelledby="trend-title">
            <div class="flex flex-col justify-between gap-space-sm sm:flex-row sm:items-center">
                <div>
                    <h2 id="trend-title" class="text-headline-md text-on-surface">{{ $metric === 'revenue' ? 'Revenue trend' : 'Booking trend' }}</h2>
                    <p class="text-body-sm text-on-surface-variant">
                        {{ $range['label'] }} &bull;
                        @if ($metric === 'revenue')
                            {{ $money($chart['total']) }} paid revenue
                        @else
                            {{ $chart['total'] }} {{ Str::plural('booking', $chart['total']) }}
                        @endif
                        {{ $range['key'] === 'today' ? 'by hour' : 'by day' }}
                    </p>
                </div>
                <div class="flex gap-1 rounded-full bg-surface-container p-1 text-label-md">
                    <a href="{{ $url(['metric' => 'bookings']) }}" class="rounded-full px-3 py-1 transition-colors {{ $metric === 'bookings' ? 'bg-surface-container-lowest text-on-surface shadow-sm' : 'text-on-surface-variant hover:text-on-surface' }}">Bookings</a>
                    <a href="{{ $url(['metric' => 'revenue']) }}" class="rounded-full px-3 py-1 transition-colors {{ $metric === 'revenue' ? 'bg-surface-container-lowest text-on-surface shadow-sm' : 'text-on-surface-variant hover:text-on-surface' }}">Revenue</a>
                </div>
            </div>

            @if ($chart['total'] > 0)
                <div class="mt-space-md">
                    <div class="mb-1 text-right text-label-sm text-on-surface-variant">
                        Peak: {{ $metric === 'revenue' ? $money($chart['max']) : $chart['max'] }}
                    </div>
                    <div class="flex h-44 items-end {{ $bucketCount > 31 ? 'gap-px' : 'gap-1' }}" role="img"
                         aria-label="{{ $metric === 'revenue' ? 'Revenue' : 'Bookings' }} for {{ $range['label'] }}">
                        @foreach ($chart['buckets'] as $bucket)
                            @php
                                $value = $bucket[$metric];
                                $height = $value > 0 ? max(4, round($value / $chart['max'] * 100)) : 2;
                                $tip = $bucket['label'] . ' — ' . ($metric === 'revenue' ? $money($value) : $value . ' ' . Str::plural('booking', $value));
                            @endphp
                            <div class="flex h-full flex-1 items-end" title="{{ $tip }}">
                                <div class="w-full rounded-t-sm {{ $value > 0 ? 'bg-secondary hover:bg-on-secondary-container' : 'bg-surface-container' }}" style="height: {{ $height }}%"></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2 flex justify-between text-label-sm text-on-surface-variant">
                        <span>{{ $firstLabel }}</span>
                        @if ($bucketCount > 2)<span>{{ $midLabel }}</span>@endif
                        <span>{{ $lastLabel }}</span>
                    </div>
                </div>
            @else
                <div class="mt-space-md flex h-44 flex-col items-center justify-center rounded-lg bg-surface-container-low text-center">
                    <span class="material-symbols-outlined text-[28px] text-outline">bar_chart</span>
                    <p class="mt-1 text-label-lg text-on-surface">Nothing to chart yet</p>
                    <p class="text-body-sm text-on-surface-variant">{{ $metric === 'revenue' ? 'No paid bookings' : 'No bookings' }} in this period.</p>
                </div>
            @endif
        </section>

        {{-- Recent bookings --}}
        <section id="recent-bookings" class="flex flex-col rounded-xl bg-surface-container-lowest p-space-lg shadow-sm" aria-labelledby="recent-title">
            <div class="flex flex-col justify-between gap-space-sm pb-space-md sm:flex-row sm:items-center">
                <div class="flex items-center gap-2">
                    <h2 id="recent-title" class="text-headline-md text-on-surface">Recent Bookings</h2>
                    @if ($tabCounts['action'] > 0)
                        <span class="rounded-full bg-error-container px-2 py-0.5 text-label-sm font-semibold text-on-error-container">{{ $tabCounts['action'] }} Action Required</span>
                    @endif
                </div>
                <span class="flex items-center gap-1 text-label-lg text-outline" title="The full Bookings page is coming soon">
                    View all <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                </span>
            </div>

            <div class="flex items-center gap-2 overflow-x-auto pb-space-md">
                @foreach ([['all', 'All (' . $tabCounts['all'] . ')'], ['action', 'Needs Action'], ['pickups', 'Today\'s Pickups (' . $tabCounts['pickups'] . ')']] as [$tabKey, $tabLabel])
                    <a href="{{ $url(['tab' => $tabKey === 'all' ? null : $tabKey], '#recent-bookings') }}"
                       class="flex shrink-0 items-center gap-1.5 rounded-full px-3.5 py-1.5 text-label-md transition-colors {{ $tab === $tabKey ? 'bg-secondary text-on-secondary shadow-sm' : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high' }}">
                        <span>{{ $tabLabel }}</span>
                        @if ($tabKey === 'action' && $tabCounts['action'] > 0)
                            <span class="flex h-4 min-w-4 items-center justify-center rounded-full bg-error px-1 text-[10px] font-bold text-on-error">{{ $tabCounts['action'] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>

            @if ($recent->isEmpty())
                <div class="flex flex-col items-center justify-center rounded-lg bg-surface-container-low px-4 py-10 text-center">
                    <span class="material-symbols-outlined text-[28px] text-outline">event_busy</span>
                    <p class="mt-1 text-label-lg text-on-surface">
                        @if ($tab === 'action') No requests need your action
                        @elseif ($tab === 'pickups') No pickups scheduled for today
                        @else No bookings yet
                        @endif
                    </p>
                    <p class="text-body-sm text-on-surface-variant">
                        @if ($tab === 'all') Bookings from customers will appear here as soon as they come in.
                        @else Check the other tabs for more activity.
                        @endif
                    </p>
                </div>
            @else
                <div class="-mx-space-lg overflow-x-auto px-space-lg">
                    <table class="w-full min-w-[640px] border-collapse text-left">
                        <thead>
                            <tr class="bg-surface-container-low text-label-sm uppercase tracking-wider text-on-surface-variant">
                                <th class="rounded-l-lg px-3 py-3 font-bold">Customer</th>
                                <th class="px-3 py-3 font-bold">Vehicle</th>
                                <th class="px-3 py-3 font-bold">Rental dates</th>
                                <th class="px-3 py-3 font-bold">Total</th>
                                <th class="rounded-r-lg px-3 py-3 font-bold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-container">
                            @foreach ($recent as $booking)
                                @php
                                    [$statusLabel, $statusClass] = $statusBadge($booking->status);
                                    $days = max(1, (int) ceil($booking->start_datetime->diffInHours($booking->end_datetime, true) / 24));
                                    $customerName = $booking->customer?->full_name ?? 'Unknown customer';
                                @endphp
                                <tr class="transition-colors hover:bg-surface-container-low/60">
                                    <td class="px-3 py-3.5">
                                        <div class="flex items-center gap-3">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-secondary-container text-label-md text-on-secondary-container">{{ $initialsOf($customerName) }}</span>
                                            <div class="flex min-w-0 flex-col">
                                                <span class="truncate text-label-lg text-on-surface">{{ $customerName }}</span>
                                                <span class="text-body-sm text-on-surface-variant">{{ $booking->customer?->phone }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3.5">
                                        <div class="flex flex-col">
                                            <span class="text-label-lg text-on-surface">{{ $booking->vehicle?->brand }} {{ $booking->vehicle?->model }}</span>
                                            <span class="text-body-sm text-on-surface-variant">{{ $booking->vehicle?->plate_number }}</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3.5">
                                        <div class="flex flex-col">
                                            <span class="text-label-lg text-on-surface">{{ $dateSpan($booking->start_datetime, $booking->end_datetime) }}</span>
                                            <span class="text-body-sm text-on-surface-variant">{{ $days }} {{ Str::plural('day', $days) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3.5">
                                        <div class="flex flex-col">
                                            <span class="text-label-lg font-bold text-on-surface">{{ $money($booking->total_price) }}</span>
                                            <span class="text-body-sm {{ $booking->payment_status === 'paid' ? 'text-secondary' : 'text-on-surface-variant' }}">{{ Str::headline($booking->payment_status) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3.5">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-label-sm {{ $statusClass }}">{{ $statusLabel }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    {{-- ===================== Right column ===================== --}}
    <div class="flex flex-col gap-space-lg lg:col-span-4">

        {{-- Needs attention --}}
        <section class="rounded-xl bg-surface-container-lowest p-space-lg shadow-sm" aria-labelledby="attention-title">
            <h2 id="attention-title" class="text-headline-md text-on-surface">Needs Attention</h2>

            @if (count($attention) === 0)
                <div class="mt-space-md flex items-center gap-3 rounded-lg bg-tertiary-fixed/50 p-space-md text-on-tertiary-fixed">
                    <span class="material-symbols-outlined text-[24px]">task_alt</span>
                    <div class="flex flex-col">
                        <span class="text-label-lg">You're all caught up</span>
                        <span class="text-body-sm">Nothing is waiting on you right now.</span>
                    </div>
                </div>
            @else
                <ul class="mt-space-md flex flex-col gap-space-sm">
                    @foreach ($attention as $item)
                        @php
                            $tone = match ($item['tone']) {
                                'error' => 'bg-error-container text-on-error-container',
                                'warn'  => 'bg-amber-100 text-amber-900',
                                default => 'bg-surface-container-low text-on-surface',
                            };
                        @endphp
                        <li class="flex items-start gap-3 rounded-lg p-space-md {{ $tone }}">
                            <span class="material-symbols-outlined mt-0.5 text-[22px]">{{ $item['icon'] }}</span>
                            <div class="flex flex-col">
                                <span class="text-label-lg">{{ $item['title'] }}</span>
                                <span class="text-body-sm opacity-80">{{ $item['detail'] }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Fleet availability --}}
        <section class="rounded-xl bg-surface-container-lowest p-space-lg shadow-sm" aria-labelledby="fleet-title">
            <div class="flex items-center justify-between">
                <h2 id="fleet-title" class="text-headline-md text-on-surface">Fleet Availability</h2>
                <a href="{{ route('portal.vehicles.index') }}" class="flex items-center gap-1 text-label-md text-secondary hover:underline">
                    Manage Fleet <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                </a>
            </div>

            <div class="mt-space-md grid grid-cols-4 gap-2 text-center">
                @foreach ([['available', 'Avail', 'text-secondary'], ['rented', 'Rented', 'text-on-surface'], ['maintenance', 'Maint.', 'text-amber-700'], ['retired', 'Retired', 'text-on-surface-variant']] as [$stateKey, $stateLabel, $stateColor])
                    <div class="rounded-lg bg-surface-container-low px-1 py-3">
                        <div class="text-headline-lg {{ $stateColor }}">{{ $fleet[$stateKey] }}</div>
                        <div class="text-label-sm uppercase text-on-surface-variant">{{ $stateLabel }}</div>
                    </div>
                @endforeach
            </div>

            <div class="mt-space-md flex h-2 overflow-hidden rounded-full bg-surface-container" role="img"
                 aria-label="{{ $fleet['available_pct'] }} percent of vehicles available">
                <div class="bg-secondary" style="width: {{ $segment($fleet['available']) }}%"></div>
                <div class="bg-primary-container" style="width: {{ $segment($fleet['rented']) }}%"></div>
                <div class="bg-amber-400" style="width: {{ $segment($fleet['maintenance']) }}%"></div>
                <div class="bg-outline" style="width: {{ $segment($fleet['retired']) }}%"></div>
            </div>
            <div class="mt-2 flex justify-between text-body-sm text-on-surface-variant">
                <span>{{ $fleet['available_pct'] }}% ready to rent</span>
                <span>{{ $fleet['total'] }} total registered</span>
            </div>

            @if ($byType->isNotEmpty())
                <ul class="mt-space-md flex flex-col gap-2">
                    @foreach ($byType as $typeName => $info)
                        <li class="flex items-center justify-between rounded-lg bg-surface-container-low px-space-md py-2.5">
                            <span class="text-label-lg text-on-surface">{{ $typeName }}</span>
                            <span class="text-label-md text-on-surface-variant">{{ $info['available'] }} / {{ $info['total'] }} avail</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Today's pickups & returns --}}
        <section class="rounded-xl bg-surface-container-lowest p-space-lg shadow-sm" aria-labelledby="today-title">
            <div class="flex items-center gap-2">
                <h2 id="today-title" class="text-headline-md text-on-surface">Today's Schedule</h2>
                @if ($events->count() > 0)
                    <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-primary-container px-1.5 text-label-sm text-on-primary">{{ $events->count() }}</span>
                @endif
            </div>

            @if ($events->isEmpty())
                <p class="mt-space-md rounded-lg bg-surface-container-low p-space-md text-body-sm text-on-surface-variant">
                    No pickups or returns scheduled for today.
                </p>
            @else
                <ul class="mt-space-md flex flex-col gap-space-sm">
                    @foreach ($events as $event)
                        @php $b = $event['booking']; @endphp
                        <li class="rounded-lg bg-surface-container-low p-space-md">
                            <div class="flex items-center justify-between">
                                <span class="rounded bg-surface-container-lowest px-2 py-0.5 text-label-md text-on-surface">{{ $event['at']->format('g:i A') }}</span>
                                <span class="rounded-full px-2.5 py-0.5 text-label-sm {{ $event['kind'] === 'Pickup' ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container-high text-on-surface' }}">{{ $event['kind'] }}</span>
                            </div>
                            <p class="mt-2 text-label-lg text-on-surface">{{ $b->customer?->full_name ?? 'Unknown customer' }} &bull; {{ $b->vehicle?->brand }} {{ $b->vehicle?->model }}</p>
                            <p class="flex items-center gap-1 text-body-sm text-on-surface-variant">
                                <span class="material-symbols-outlined text-[14px]">location_on</span> {{ $b->pickup_location }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</div>
@endsection