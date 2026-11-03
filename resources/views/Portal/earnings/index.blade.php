@extends('portal.layout')

@section('title', 'Earnings & Transactions')

@php
    $money = fn ($n) => '$' . number_format((float) $n, 2);
    $money0 = fn ($n) => '$' . number_format((float) $n, 0);
    $q = fn (array $extra = []) => $extra;
    $range = $period['range'];
    $chart = $report['chart'];
    $max = $chart['max'] > 0 ? $chart['max'] : 1;
    $every = count($chart['buckets']) > 14 ? 5 : 1;
    $tabBtn = fn ($on) => 'rounded-lg px-3.5 py-1.5 text-label-md ' . ($on ? 'bg-primary text-on-primary shadow-sm' : 'bg-surface-container-lowest text-on-surface-variant hover:text-on-surface');
@endphp

@section('header-actions')
    <a href="{{ route('portal.earnings.export', $period['param']) }}"
       class="flex items-center gap-1.5 rounded-lg bg-surface-container px-3.5 py-2 text-label-lg text-on-surface transition-colors hover:bg-surface-container-high">
        <span class="material-symbols-outlined text-[18px]">download</span> Export CSV
    </a>
@endsection

@section('content')
<div class="flex flex-col gap-space-lg">

    {{-- Period --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex gap-1 rounded-xl bg-surface-container-high/60 p-1">
            <a href="{{ route('portal.earnings.index', ['range' => 'month']) }}" class="{{ $tabBtn($range === 'month') }}">Month</a>
            <a href="{{ route('portal.earnings.index', ['range' => 'year']) }}" class="{{ $tabBtn($range === 'year') }}">Year</a>
            <a href="{{ route('portal.earnings.index', ['range' => 'all']) }}" class="{{ $tabBtn($range === 'all') }}">All time</a>
        </div>

        <div class="flex items-center gap-2">
            @if ($range !== 'all')
                @if ($period['prev'])
                    <a href="{{ route('portal.earnings.index', $period['prev']) }}" aria-label="Previous" class="flex h-9 w-9 items-center justify-center rounded-lg bg-surface-container-lowest text-on-surface shadow-sm hover:bg-surface-container-high"><span class="material-symbols-outlined text-[20px]">chevron_left</span></a>
                @endif
                <span class="min-w-[8rem] text-center text-headline-sm text-on-surface">{{ $period['label'] }}</span>
                @if ($period['next'])
                    <a href="{{ route('portal.earnings.index', $period['next']) }}" aria-label="Next" class="flex h-9 w-9 items-center justify-center rounded-lg bg-surface-container-lowest text-on-surface shadow-sm hover:bg-surface-container-high"><span class="material-symbols-outlined text-[20px]">chevron_right</span></a>
                @endif
                @unless ($period['is_current'])
                    <a href="{{ route('portal.earnings.index', ['range' => $range]) }}" class="ml-1 text-label-md text-secondary hover:underline">{{ $range === 'year' ? 'This year' : 'This month' }}</a>
                @endunless
            @else
                <span class="text-headline-sm text-on-surface">All time</span>
            @endif
        </div>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
        <div class="rounded-2xl bg-surface-container-lowest p-4 shadow-sm sm:p-5">
            <span class="flex items-center gap-1.5 text-label-sm uppercase text-on-surface-variant"><span class="material-symbols-outlined text-base text-secondary">payments</span> Collected</span>
            <div class="mt-2 text-display-lg font-extrabold text-secondary">{{ $money($report['collected']) }}</div>
            <p class="mt-1 text-body-sm text-on-surface-variant">{{ $report['paid_count'] }} paid {{ $report['paid_count'] === 1 ? 'rental' : 'rentals' }}</p>
        </div>
        <div class="rounded-2xl bg-surface-container-lowest p-4 shadow-sm sm:p-5">
            <span class="flex items-center gap-1.5 text-label-sm uppercase text-on-surface-variant"><span class="material-symbols-outlined text-base text-secondary">hourglass_top</span> To collect</span>
            <div class="mt-2 text-display-lg font-extrabold text-on-surface">{{ $money($report['to_collect']) }}</div>
            <p class="mt-1 text-body-sm text-on-surface-variant">{{ $report['unpaid_count'] }} not marked paid yet</p>
        </div>
        <div class="rounded-2xl bg-surface-container-lowest p-4 shadow-sm sm:p-5">
            <span class="flex items-center gap-1.5 text-label-sm uppercase text-on-surface-variant"><span class="material-symbols-outlined text-base text-secondary">two_wheeler</span> Rentals</span>
            <div class="mt-2 text-display-lg font-extrabold text-on-surface">{{ $report['rentals'] }}</div>
            <p class="mt-1 text-body-sm text-on-surface-variant">Average {{ $money($report['average']) }}@if ($report['cancelled'] > 0) &bull; {{ $report['cancelled'] }} cancelled @endif</p>
        </div>
        <div class="rounded-2xl bg-surface-container-lowest p-4 shadow-sm sm:p-5">
            <span class="flex items-center gap-1.5 text-label-sm uppercase text-on-surface-variant"><span class="material-symbols-outlined text-base text-secondary">sell</span> Vouchers</span>
            <div class="mt-2 text-display-lg font-extrabold text-on-surface">{{ $money($report['discounts']) }}</div>
            <p class="mt-1 text-body-sm text-on-surface-variant">given in discounts &bull; {{ $report['vouchers_used'] }} used</p>
        </div>
    </div>

    {{-- Chart --}}
    <section class="rounded-2xl bg-surface-container-lowest p-5 shadow-sm sm:p-6" aria-labelledby="chartHeading">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <h2 id="chartHeading" class="text-headline-sm text-on-surface">{{ $range === 'month' ? 'Rental value by pickup day' : 'Rental value by month' }}</h2>
            <div class="flex items-center gap-4 text-label-sm text-on-surface-variant">
                <span class="inline-flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-secondary"></span>Collected</span>
                <span class="inline-flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-secondary-container"></span>To collect</span>
            </div>
        </div>

        @if ($chart['max'] <= 0)
            <div class="flex h-40 items-center justify-center rounded-xl bg-surface-container-low text-body-md text-on-surface-variant">No rentals in this period yet.</div>
        @else
            <div class="flex">
                <div class="flex h-44 w-12 shrink-0 flex-col justify-between pr-2 text-right text-label-sm text-on-surface-variant" aria-hidden="true">
                    <span>{{ $money0($chart['max']) }}</span><span>{{ $money0($chart['max'] / 2) }}</span><span>$0</span>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex h-44 items-end gap-[3px] border-b border-l border-surface-container" role="img" aria-label="Bar chart of rental value">
                        @foreach ($chart['buckets'] as $b)
                            @php $total = $b['paid'] + $b['unpaid']; @endphp
                            <div class="group relative flex h-full min-w-0 flex-1 flex-col justify-end" title="{{ $b['title'] }}: {{ $money($total) }}@if ($b['unpaid'] > 0) ({{ $money($b['paid']) }} collected)@endif">
                                @if ($b['unpaid'] > 0)<div class="w-full rounded-t-sm bg-secondary-container" style="height:{{ round($b['unpaid'] / $max * 100, 1) }}%"></div>@endif
                                @if ($b['paid'] > 0)<div class="w-full {{ $b['unpaid'] > 0 ? '' : 'rounded-t-sm' }} bg-secondary" style="height:{{ round($b['paid'] / $max * 100, 1) }}%"></div>@endif
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-1 flex gap-[3px] text-center text-[10px] text-on-surface-variant" aria-hidden="true">
                        @foreach ($chart['buckets'] as $i => $b)
                            <span class="min-w-0 flex-1 overflow-visible whitespace-nowrap">{{ ($range === 'month' ? ($i % $every === 0 || $i === count($chart['buckets']) - 1) : true) ? $b['label'] : '' }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </section>

    {{-- Per vehicle --}}
    <section class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-sm" aria-labelledby="vehHeading">
        <div class="border-b border-surface-container px-5 py-4 sm:px-6"><h2 id="vehHeading" class="text-headline-sm text-on-surface">By vehicle</h2></div>
        @if (count($report['vehicles']) === 0)
            <p class="p-6 text-body-md text-on-surface-variant">Nothing to show for this period.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-body-md">
                    <thead class="bg-surface-container-low text-label-sm uppercase text-on-surface-variant">
                        <tr><th class="px-5 py-3 sm:px-6">Vehicle</th><th class="px-3 py-3 text-right">Rentals</th><th class="px-3 py-3 text-right">Days</th><th class="px-3 py-3 text-right">Collected</th><th class="px-5 py-3 text-right sm:px-6">To collect</th></tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container">
                        @foreach ($report['vehicles'] as $v)
                            <tr>
                                <td class="px-5 py-3 sm:px-6"><div class="font-semibold text-on-surface">{{ $v['name'] }}</div><div class="text-body-sm text-on-surface-variant">{{ $v['plate'] }}</div></td>
                                <td class="px-3 py-3 text-right">{{ $v['rentals'] }}</td>
                                <td class="px-3 py-3 text-right">{{ $v['days'] }}</td>
                                <td class="px-3 py-3 text-right font-semibold text-secondary">{{ $money($v['paid']) }}</td>
                                <td class="px-5 py-3 text-right text-on-surface-variant sm:px-6">{{ $money($v['unpaid']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Transactions --}}
    <section class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-sm" aria-labelledby="txHeading">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-surface-container px-5 py-4 sm:px-6">
            <h2 id="txHeading" class="text-headline-sm text-on-surface">Transactions</h2>
            <span class="text-body-sm text-on-surface-variant">Rentals are counted by pickup date. Pending, rejected and cancelled bookings are not included. Deposits are not counted.</span>
        </div>
        @if ($rows->isEmpty())
            <p class="p-6 text-body-md text-on-surface-variant">No rentals in this period.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-body-md">
                    <thead class="bg-surface-container-low text-label-sm uppercase text-on-surface-variant">
                        <tr><th class="px-5 py-3 sm:px-6">Booking</th><th class="px-3 py-3">Customer</th><th class="px-3 py-3">Vehicle</th><th class="px-3 py-3 text-right">Total</th><th class="px-5 py-3 text-right sm:px-6">Payment</th></tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container">
                        @foreach ($rows as $b)
                            <tr>
                                <td class="px-5 py-3 sm:px-6">
                                    <div class="font-semibold text-on-surface">{{ $b->reference ?? '—' }}</div>
                                    <div class="text-body-sm text-on-surface-variant">{{ $b->start_datetime->format('M j, Y') }} &bull; {{ ucfirst($b->status) }}</div>
                                </td>
                                <td class="px-3 py-3">
                                    @if ($b->customer)
                                        <a href="{{ route('portal.customers.show', $b->customer_id) }}" class="font-semibold text-on-surface hover:text-secondary">{{ $b->customer->full_name }}</a>
                                        <div class="text-body-sm text-on-surface-variant">{{ $b->customer->phone }}</div>
                                    @else — @endif
                                </td>
                                <td class="px-3 py-3"><div class="text-on-surface">{{ trim(($b->vehicle->brand ?? '') . ' ' . ($b->vehicle->model ?? '')) ?: '—' }}</div><div class="text-body-sm text-on-surface-variant">{{ $b->vehicle->plate_number ?? '' }}</div></td>
                                <td class="px-3 py-3 text-right">
                                    <div class="font-semibold text-on-surface">{{ $money($b->total_price) }}</div>
                                    @if ((float) $b->discount_amount > 0)<div class="text-body-sm text-secondary">{{ $b->voucher_code }} −{{ $money($b->discount_amount) }}</div>@endif
                                </td>
                                <td class="px-5 py-3 text-right sm:px-6">
                                    @if ($b->payment_status === 'paid')
                                        <span class="inline-block rounded-full bg-tertiary-fixed px-2.5 py-0.5 text-label-sm text-on-tertiary-fixed">Paid</span>
                                    @else
                                        <form method="POST" action="{{ route('portal.bookings.action', [$b, 'paid']) }}" class="inline">@csrf
                                            <button class="rounded-lg bg-surface-container px-3 py-1.5 text-label-md font-semibold text-on-surface hover:bg-surface-container-high">Mark paid</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($rows->hasPages())
                <div class="flex items-center justify-between gap-3 border-t border-surface-container px-5 py-3 text-body-sm text-on-surface-variant sm:px-6">
                    <span>{{ $rows->firstItem() }}–{{ $rows->lastItem() }} of {{ $rows->total() }}</span>
                    <div class="flex gap-2">
                        @if ($rows->onFirstPage()) <span class="rounded-lg bg-surface-container px-3 py-1.5 opacity-50">Previous</span> @else <a href="{{ $rows->previousPageUrl() }}" class="rounded-lg bg-surface-container-low px-3 py-1.5 hover:bg-surface-container-high">Previous</a> @endif
                        @if ($rows->hasMorePages()) <a href="{{ $rows->nextPageUrl() }}" class="rounded-lg bg-surface-container-low px-3 py-1.5 hover:bg-surface-container-high">Next</a> @else <span class="rounded-lg bg-surface-container px-3 py-1.5 opacity-50">Next</span> @endif
                    </div>
                </div>
            @endif
        @endif
    </section>
</div>
@endsection