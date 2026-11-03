@extends('portal.layout')

@section('title', 'Availability Calendar')

@php
    $input = 'w-full rounded-ar-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-primary-500';
    $daysInMonth = $month->daysInMonth;
    $vname = fn ($v) => trim($v->brand . ' ' . $v->model) ?: $v->plate_number;
    $rows = $selected ? collect([$selected]) : $vehicles;
    $kindClass = [
        'confirmed' => 'bg-primary-500 text-white',
        'ongoing'   => 'bg-primary-700 text-white',
        'pending'   => 'bg-amber-300 text-amber-950',
        'blocked'   => 'bg-gray-300 text-gray-700',
    ];
    $kindLabel = ['confirmed' => 'Confirmed', 'ongoing' => 'On the road', 'pending' => 'Pending request', 'blocked' => 'Blocked'];
    $stripe = 'background-image:repeating-linear-gradient(135deg,rgba(255,255,255,.55) 0 4px,transparent 4px 8px)';
    $lead = $month->dayOfWeekIso - 1;
    $todayKey = $today->format('Y-m-d');
    $q = fn ($m) => array_filter(['m' => $m, 'vehicle' => $selected?->id]);
@endphp

@section('header-actions')
    <a href="#blockForm" class="rounded-ar-md bg-primary-500 text-white text-sm font-semibold px-4 py-2 hover:bg-primary-700">+ Block days</a>
@endsection

@section('content')
<div class="space-y-4">

    @if ($errors->any())
        <div role="alert" class="rounded-ar-md bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
            @if ($errors->has('from'))
                <a href="{{ route('portal.bookings.index') }}" class="mt-2 inline-block font-semibold underline">Open Bookings</a>
            @endif
        </div>
    @endif

    {{-- Toolbar --}}
    <div class="bg-white rounded-ar-lg border border-primary-100 p-4 flex flex-wrap items-center gap-3 justify-between">
        <div class="flex items-center gap-2">
            <a href="{{ route('portal.availability.index', $q($prevMonth)) }}" aria-label="Previous month"
               class="rounded-ar-md border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50">&larr;</a>
            <h2 class="min-w-[9rem] text-center text-base font-bold text-primary-900">{{ $month->format('F Y') }}</h2>
            <a href="{{ route('portal.availability.index', $q($nextMonth)) }}" aria-label="Next month"
               class="rounded-ar-md border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50">&rarr;</a>
            @if ($month->format('Y-m') !== $thisMonth)
                <a href="{{ route('portal.availability.index', $q($thisMonth)) }}" class="ml-1 text-sm font-semibold text-primary-700 hover:underline">Today</a>
            @endif
        </div>

        <form method="GET" action="{{ route('portal.availability.index') }}" class="flex items-center gap-2">
            <input type="hidden" name="m" value="{{ $month->format('Y-m') }}">
            <label for="vehicleFilter" class="text-sm text-gray-600">Show</label>
            <select id="vehicleFilter" name="vehicle" class="rounded-ar-md border border-gray-300 px-3 py-1.5 text-sm" onchange="this.form.submit()">
                <option value="">All vehicles</option>
                @foreach ($vehicles as $veh)
                    <option value="{{ $veh->id }}" @selected($selected && $selected->id === $veh->id)>{{ $vname($veh) }} · {{ $veh->plate_number }}</option>
                @endforeach
            </select>
            <noscript><button class="rounded-ar-md bg-primary-500 text-white text-sm px-3 py-1.5">Go</button></noscript>
        </form>
    </div>

    {{-- Legend --}}
    <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-600">
        @foreach ($kindLabel as $k => $label)
            <span class="inline-flex items-center gap-1.5">
                <span class="inline-block h-3 w-3 rounded-sm {{ $kindClass[$k] }}" @if ($k === 'blocked') style="{{ $stripe }}" @endif></span>{{ $label }}
            </span>
        @endforeach
    </div>

    @if ($vehicles->isEmpty())
        <div class="bg-white rounded-ar-lg border border-primary-100 p-10 text-center text-sm text-gray-500">
            No vehicles yet.
            <a href="{{ route('portal.vehicles.create') }}" class="text-primary-700 font-medium hover:underline">Add your first one</a>
            and its bookings will show up here.
        </div>
    @elseif (! $selected)
        {{-- All vehicles: one row per vehicle, one column per day --}}
        <div id="timeline" class="relative bg-white rounded-ar-lg border border-primary-100 overflow-x-auto">
            <table class="border-collapse text-xs" style="min-width:100%">
                <thead>
                    <tr class="bg-primary-100/50 text-gray-500">
                        <th class="sticky left-0 z-10 bg-primary-100 px-3 py-2 text-left font-semibold min-w-[9rem]">Vehicle</th>
                        @for ($d = 1; $d <= $daysInMonth; $d++)
                            @php $date = $month->copy()->day($d); $isToday = $date->format('Y-m-d') === $todayKey; @endphp
                            <th class="min-w-[1.75rem] px-0 py-1 text-center font-medium {{ $isToday ? 'bg-primary-500 text-white' : '' }} {{ $date->isWeekend() && ! $isToday ? 'text-gray-400' : '' }}">
                                <div class="text-[10px] leading-tight">{{ $date->format('D')[0] }}</div>
                                <div>{{ $d }}</div>
                            </th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $veh)
                        <tr class="border-t border-primary-100">
                            <th class="sticky left-0 z-10 bg-white px-3 py-2 text-left font-medium text-primary-900 min-w-[9rem]">
                                <a href="{{ route('portal.availability.index', ['vehicle' => $veh->id, 'm' => $month->format('Y-m')]) }}" class="hover:underline">{{ $vname($veh) }}</a>
                                <div class="text-[10px] font-normal text-gray-500">{{ $veh->plate_number }}@if ($veh->status !== 'available') · hidden @endif</div>
                            </th>
                            @for ($d = 1; $d <= $daysInMonth; $d++)
                                @php
                                    $cell = $map[$veh->id][$d] ?? null;
                                    $date = $month->copy()->day($d);
                                    $isToday = $date->format('Y-m-d') === $todayKey;
                                @endphp
                                <td class="h-9 p-0 border-l border-primary-100/60 {{ $isToday ? 'bg-primary-100/40' : ($date->isWeekend() ? 'bg-gray-50' : '') }}">
                                    @if ($cell)
                                        <a href="{{ route('portal.availability.index', ['vehicle' => $veh->id, 'm' => $month->format('Y-m')]) }}"
                                           title="{{ $kindLabel[$cell['kind']] }} · {{ $cell['title'] }}{{ $cell['sub'] ? ' · ' . $cell['sub'] : '' }}"
                                           class="block h-7 mx-px rounded-sm {{ $kindClass[$cell['kind']] }}"
                                           @if ($cell['kind'] === 'blocked') style="{{ $stripe }}" @endif>
                                            <span class="sr-only">{{ $kindLabel[$cell['kind']] }}</span>
                                        </a>
                                    @endif
                                </td>
                            @endfor
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-xs text-gray-500">Tip: click a vehicle's name to see its full month and pick days to block.</p>
    @else
        {{-- One vehicle: month calendar --}}
        <div class="bg-white rounded-ar-lg border border-primary-100 overflow-hidden">
            <div class="flex items-center justify-between gap-2 border-b border-primary-100 px-4 py-3">
                <div class="min-w-0">
                    <div class="truncate font-bold text-primary-900">{{ $vname($selected) }}</div>
                    <div class="text-xs text-gray-500">{{ $selected->plate_number }}@if ($selected->status !== 'available') · currently hidden from customers ({{ $selected->status }}) @endif</div>
                </div>
                <a href="{{ route('portal.availability.index', ['m' => $month->format('Y-m')]) }}" class="shrink-0 text-sm font-semibold text-primary-700 hover:underline">All vehicles</a>
            </div>

            <div class="grid grid-cols-7 border-b border-primary-100 bg-primary-100/40 text-center text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                @foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $wd)<div class="py-2">{{ $wd }}</div>@endforeach
            </div>

            <div class="grid grid-cols-7" id="calGrid">
                @for ($i = 0; $i < $lead; $i++)<div class="min-h-[4.5rem] border-b border-r border-primary-100/60 bg-gray-50/60"></div>@endfor
                @for ($d = 1; $d <= $daysInMonth; $d++)
                    @php
                        $date = $month->copy()->day($d);
                        $key = $date->format('Y-m-d');
                        $cell = $map[$selected->id][$d] ?? null;
                        $past = $key < $todayKey;
                        $isToday = $key === $todayKey;
                    @endphp
                    <button type="button" data-date="{{ $key }}" @disabled($past || $cell)
                        title="{{ $cell ? $kindLabel[$cell['kind']] . ' · ' . $cell['title'] : ($past ? '' : 'Click to pick this day for blocking') }}"
                        class="cal-day relative min-h-[4.5rem] border-b border-r border-primary-100/60 p-1 text-left align-top {{ $past ? 'bg-gray-50 text-gray-300' : '' }} {{ ! $past && ! $cell ? 'hover:bg-primary-100/40' : '' }}">
                        <span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full px-1 text-xs {{ $isToday ? 'bg-primary-500 font-bold text-white' : 'text-gray-600' }}">{{ $d }}</span>
                        @if ($cell)
                            <span class="mt-1 block rounded-sm px-1 py-0.5 text-[10px] leading-tight {{ $kindClass[$cell['kind']] }} {{ $past ? 'opacity-60' : '' }}" @if ($cell['kind'] === 'blocked') style="{{ $stripe }}" @endif>
                                @if ($cell['is_start'] || $date->dayOfWeekIso === 1)
                                    <span class="block truncate font-semibold">{{ $cell['title'] }}</span>
                                    @if ($cell['sub'])<span class="hidden truncate sm:block opacity-90">{{ $cell['sub'] }}</span>@endif
                                @else
                                    <span class="block">&nbsp;</span>
                                @endif
                            </span>
                        @endif
                    </button>
                @endfor
                @php $tail = (7 - (($lead + $daysInMonth) % 7)) % 7; @endphp
                @for ($i = 0; $i < $tail; $i++)<div class="min-h-[4.5rem] border-b border-r border-primary-100/60 bg-gray-50/60"></div>@endfor
            </div>
        </div>

        {{-- This month in a list (easier to read on a phone) --}}
        <div class="bg-white rounded-ar-lg border border-primary-100 p-4">
            <h3 class="mb-2 text-sm font-bold text-primary-900">This month</h3>
            @php $list = $entries[$selected->id] ?? []; @endphp
            @if (count($list) === 0)
                <p class="text-sm text-gray-500">Nothing booked or blocked in {{ $month->format('F') }}. Customers can book every day.</p>
            @else
                <ul class="divide-y divide-primary-100">
                    @foreach ($list as $e)
                        @php
                            $f = \Illuminate\Support\Carbon::parse($e['first_day']); $l = \Illuminate\Support\Carbon::parse($e['last_day']);
                        @endphp
                        <li class="flex items-center justify-between gap-3 py-2 text-sm">
                            <div class="min-w-0">
                                <span class="inline-block h-2.5 w-2.5 rounded-sm align-middle {{ $kindClass[$e['kind']] }}"></span>
                                <span class="font-medium text-primary-900">{{ $f->format('j M') }}@if ($e['first_day'] !== $e['last_day']) – {{ $l->format('j M') }}@endif</span>
                                <span class="text-gray-600">· {{ $e['title'] }}</span>
                                @if ($e['sub'])<span class="text-gray-400">· {{ $e['sub'] }}</span>@endif
                                <span class="ml-1 rounded-ar-pill bg-gray-100 px-2 py-0.5 text-[11px] text-gray-600">{{ $kindLabel[$e['kind']] }}</span>
                            </div>
                            @if ($e['block_id'])
                                <form method="POST" action="{{ route('portal.availability.destroy', $e['block_id']) }}" onsubmit="return confirm('Unblock these days? Customers will be able to book them again.')">
                                    @csrf @method('DELETE')
                                    <button class="shrink-0 text-xs font-semibold text-red-600 hover:underline">Unblock</button>
                                </form>
                            @else
                                <a href="{{ route('portal.bookings.index', ['q' => $e['sub']]) }}" class="shrink-0 text-xs font-semibold text-primary-700 hover:underline">View booking</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    @if ($vehicles->isNotEmpty())
    {{-- Block form --}}
    <div id="blockForm" class="bg-white rounded-ar-lg border border-primary-100 p-5 scroll-mt-24">
        <h3 class="text-base font-bold text-primary-900">Block days</h3>
        <p class="mb-4 text-sm text-gray-600">Take a vehicle off the calendar for maintenance or private use. Customers cannot book blocked days. Days that already have a booking cannot be blocked.</p>

        <form method="POST" action="{{ route('portal.availability.store') }}" class="grid gap-4 sm:grid-cols-2">
            @csrf
            <div class="sm:col-span-2">
                <label for="blk_vehicle" class="mb-1 block text-sm font-medium text-gray-700">Vehicle</label>
                <select id="blk_vehicle" name="vehicle_id" class="{{ $input }}" required>
                    @foreach ($vehicles as $veh)
                        <option value="{{ $veh->id }}" @selected(old('vehicle_id', $selected?->id) === $veh->id)>{{ $vname($veh) }} · {{ $veh->plate_number }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="blk_from" class="mb-1 block text-sm font-medium text-gray-700">First day</label>
                <input id="blk_from" type="date" name="from" value="{{ old('from') }}" min="{{ $todayKey }}" class="{{ $input }}" required>
            </div>
            <div>
                <label for="blk_to" class="mb-1 block text-sm font-medium text-gray-700">Last day</label>
                <input id="blk_to" type="date" name="to" value="{{ old('to') }}" min="{{ $todayKey }}" class="{{ $input }}" required>
            </div>
            <div>
                <label for="blk_reason" class="mb-1 block text-sm font-medium text-gray-700">Reason</label>
                <select id="blk_reason" name="reason" class="{{ $input }}">
                    @foreach (\App\Models\VehicleBlock::REASONS as $key => $label)
                        <option value="{{ $key }}" @selected(old('reason', 'maintenance') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="blk_note" class="mb-1 block text-sm font-medium text-gray-700">Note <span class="font-normal text-gray-400">(optional, only you see it)</span></label>
                <input id="blk_note" type="text" name="note" maxlength="120" value="{{ old('note') }}" placeholder="e.g. Brake service" class="{{ $input }}">
            </div>
            <div class="sm:col-span-2 flex justify-end">
                <button type="submit" class="rounded-ar-md bg-primary-500 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700">Block these days</button>
            </div>
        </form>
    </div>

    {{-- Upcoming blocks --}}
    <div class="bg-white rounded-ar-lg border border-primary-100 p-5">
        <h3 class="mb-2 text-base font-bold text-primary-900">Upcoming blocked days</h3>
        @if ($upcomingBlocks->isEmpty())
            <p class="text-sm text-gray-500">No blocked days. Every vehicle can be booked.</p>
        @else
            <ul class="divide-y divide-primary-100">
                @foreach ($upcomingBlocks as $b)
                    <li class="flex items-center justify-between gap-3 py-2 text-sm">
                        <div class="min-w-0">
                            <span class="font-medium text-primary-900">{{ $vname($b->vehicle) }}</span>
                            <span class="text-gray-600">· {{ $b->start_datetime->format('j M') }}@if (! $b->start_datetime->isSameDay($b->lastDay())) – {{ $b->lastDay()->format('j M Y') }}@else {{ $b->start_datetime->format('Y') }}@endif</span>
                            <span class="ml-1 rounded-ar-pill bg-gray-100 px-2 py-0.5 text-[11px] text-gray-600">{{ $b->reasonLabel() }}</span>
                            @if ($b->note)<span class="text-gray-400">· {{ $b->note }}</span>@endif
                        </div>
                        <form method="POST" action="{{ route('portal.availability.destroy', $b) }}" onsubmit="return confirm('Unblock these days? Customers will be able to book them again.')">
                            @csrf @method('DELETE')
                            <button class="shrink-0 text-xs font-semibold text-red-600 hover:underline">Unblock</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
    @endif
</div>

<script>
    // Open the all-vehicles timeline scrolled to today.
    (function () {
        var box = document.getElementById('timeline'), th = box && box.querySelector('thead th.bg-primary-500');
        if (th) box.scrollLeft = Math.max(0, th.offsetLeft - 180);
    })();

    // Click days in the calendar to fill the "block days" form: first click = first day, second = last day.
    (function () {
        var grid = document.getElementById('calGrid');
        if (!grid) return;
        var from = document.getElementById('blk_from'), to = document.getElementById('blk_to'), anchor = null;
        grid.addEventListener('click', function (e) {
            var cell = e.target.closest('.cal-day');
            if (!cell || cell.disabled) return;
            var d = cell.getAttribute('data-date');
            if (!anchor || d < anchor) { anchor = d; from.value = d; to.value = d; }
            else { to.value = d; anchor = null; }
            document.querySelectorAll('.cal-day').forEach(function (c) {
                var x = c.getAttribute('data-date');
                c.classList.toggle('ring-2', x >= from.value && x <= to.value);
                c.classList.toggle('ring-inset', x >= from.value && x <= to.value);
                c.classList.toggle('ring-primary-500', x >= from.value && x <= to.value);
            });
            document.getElementById('blockForm').scrollIntoView({behavior: 'smooth', block: 'nearest'});
        });
    })();
</script>
@endsection