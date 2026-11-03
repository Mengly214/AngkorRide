@extends('portal.layout')

@section('title', 'Customers')

@php
    $money = fn ($n) => '$' . number_format((float) $n, 2);
    $tabs = ['all' => 'All', 'returning' => 'Returning', 'new' => 'First-time'];
    $sorts = ['recent' => 'Most recent', 'spent' => 'Highest spend', 'rentals' => 'Most rentals'];
    $keep = fn (array $o = []) => array_filter(array_merge(['tab' => $tab !== 'all' ? $tab : null, 'sort' => $sort !== 'recent' ? $sort : null, 'q' => $search !== '' ? $search : null], $o), fn ($v) => $v !== null);
    $initials = fn ($name) => \Illuminate\Support\Str::upper(collect(preg_split('/\s+/u', trim($name)))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->join('')) ?: '?';
@endphp

@section('header-actions')
    <form method="GET" action="{{ route('portal.customers.index') }}" class="flex items-center gap-2">
        @if ($tab !== 'all')<input type="hidden" name="tab" value="{{ $tab }}">@endif
        @if ($sort !== 'recent')<input type="hidden" name="sort" value="{{ $sort }}">@endif
        <input type="search" name="q" value="{{ $search }}" maxlength="60" placeholder="Search name or phone" class="w-56 rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2 text-body-md focus:border-secondary focus:outline-none">
        <button class="rounded-lg bg-primary px-3 py-2 text-label-md text-on-primary">Search</button>
    </form>
@endsection

@section('content')
<div class="flex flex-col gap-space-lg">

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
        <div class="rounded-2xl bg-surface-container-lowest p-4 shadow-sm sm:p-5">
            <span class="flex items-center gap-1.5 text-label-sm uppercase text-on-surface-variant"><span class="material-symbols-outlined text-base text-secondary">group</span> Customers</span>
            <div class="mt-2 text-display-lg font-extrabold text-on-surface">{{ $summary['total'] }}</div>
            <p class="mt-1 text-body-sm text-on-surface-variant">{{ $summary['new_30d'] }} new in the last 30 days</p>
        </div>
        <div class="rounded-2xl bg-surface-container-lowest p-4 shadow-sm sm:p-5">
            <span class="flex items-center gap-1.5 text-label-sm uppercase text-on-surface-variant"><span class="material-symbols-outlined text-base text-secondary">replay</span> Returning</span>
            <div class="mt-2 text-display-lg font-extrabold text-secondary">{{ $summary['returning'] }}</div>
            <p class="mt-1 text-body-sm text-on-surface-variant">{{ $summary['repeat_rate'] }}% of customers who rented</p>
        </div>
        <div class="rounded-2xl bg-surface-container-lowest p-4 shadow-sm sm:p-5">
            <span class="flex items-center gap-1.5 text-label-sm uppercase text-on-surface-variant"><span class="material-symbols-outlined text-base text-secondary">two_wheeler</span> Rentals each</span>
            <div class="mt-2 text-display-lg font-extrabold text-on-surface">{{ $summary['avg_rentals'] }}</div>
            <p class="mt-1 text-body-sm text-on-surface-variant">average per renting customer</p>
        </div>
        <div class="rounded-2xl bg-surface-container-lowest p-4 shadow-sm sm:p-5">
            <span class="flex items-center gap-1.5 text-label-sm uppercase text-on-surface-variant"><span class="material-symbols-outlined text-base text-secondary">workspace_premium</span> Top customer</span>
            @if ($summary['top'])
                <div class="mt-2 truncate text-headline-md text-on-surface">{{ $summary['top']->name }}</div>
                <p class="mt-1 text-body-sm text-on-surface-variant">{{ $money($summary['top']->spent) }} &bull; {{ $summary['top']->rentals }} {{ $summary['top']->rentals === 1 ? 'rental' : 'rentals' }}</p>
            @else
                <div class="mt-2 text-headline-md text-on-surface-variant">—</div>
                <p class="mt-1 text-body-sm text-on-surface-variant">No rentals yet</p>
            @endif
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <nav class="flex flex-wrap gap-2" aria-label="Customer groups">
            @foreach ($tabs as $key => $name)
                <a href="{{ route('portal.customers.index', $keep(['tab' => $key !== 'all' ? $key : null])) }}" @if ($tab === $key) aria-current="page" @endif
                   class="rounded-full px-4 py-1.5 text-label-md {{ $tab === $key ? 'bg-primary text-on-primary' : 'bg-surface-container-lowest text-on-surface-variant hover:text-on-surface' }}">
                    {{ $name }} <span class="opacity-70">{{ $counts[$key] }}</span>
                </a>
            @endforeach
        </nav>
        <div class="flex items-center gap-2 text-body-sm text-on-surface-variant">
            <span>Sort</span>
            @foreach ($sorts as $key => $name)
                <a href="{{ route('portal.customers.index', $keep(['sort' => $key !== 'recent' ? $key : null])) }}" class="rounded-lg px-2.5 py-1 {{ $sort === $key ? 'bg-secondary-container text-on-secondary-container font-semibold' : 'hover:bg-surface-container-high' }}">{{ $name }}</a>
            @endforeach
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-sm">
        @if ($customers->isEmpty())
            <div class="p-10 text-center text-body-md text-on-surface-variant">
                @if ($search !== '' || $tab !== 'all')
                    No customers match. <a href="{{ route('portal.customers.index') }}" class="font-semibold text-secondary hover:underline">Show everyone</a>
                @else
                    No customers yet. When someone books one of your vehicles, they will appear here.
                @endif
            </div>
        @else
            <div class="relative overflow-x-auto">
                <table class="w-full text-left text-body-md">
                    <thead class="bg-surface-container-low text-label-sm uppercase text-on-surface-variant">
                        <tr><th class="px-5 py-3">Customer</th><th class="px-3 py-3 text-right">Rentals</th><th class="px-3 py-3 text-right">Spent</th><th class="px-3 py-3">Latest pickup</th><th class="px-5 py-3 text-right"><span class="sr-only">Open</span></th></tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container">
                        @foreach ($customers as $c)
                            <tr class="hover:bg-surface-container-low/50">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-secondary-container text-label-md text-on-secondary-container">{{ $initials($c->name) }}</span>
                                        <div class="min-w-0">
                                            <a href="{{ route('portal.customers.show', $c->id) }}" class="font-semibold text-on-surface hover:text-secondary">{{ $c->name }}</a>
                                            @if ($c->returning)<span class="ml-1.5 rounded-full bg-tertiary-fixed px-2 py-0.5 text-label-sm text-on-tertiary-fixed">Returning</span>@endif
                                            <div><a href="tel:{{ $c->phone }}" class="text-body-sm text-secondary hover:underline">{{ $c->phone }}</a></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <div class="font-semibold text-on-surface">{{ $c->rentals }}</div>
                                    @if ($c->cancelled > 0)<div class="text-body-sm text-on-surface-variant">{{ $c->cancelled }} cancelled</div>@endif
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <div class="font-semibold text-on-surface">{{ $money($c->spent) }}</div>
                                    @if ($c->unpaid > 0)<div class="text-body-sm text-on-surface-variant">{{ $money($c->unpaid) }} to collect</div>@endif
                                </td>
                                <td class="px-3 py-3 text-on-surface-variant">{{ $c->last_rental_at ? $c->last_rental_at->format('M j, Y') : '—' }}</td>
                                <td class="px-5 py-3 text-right"><a href="{{ route('portal.customers.show', $c->id) }}" class="text-label-md font-semibold text-secondary hover:underline">View</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($customers->hasPages())
                <div class="flex items-center justify-between gap-3 border-t border-surface-container px-5 py-3 text-body-sm text-on-surface-variant">
                    <span>{{ $customers->firstItem() }}–{{ $customers->lastItem() }} of {{ $customers->total() }}</span>
                    <div class="flex gap-2">
                        @if ($customers->onFirstPage()) <span class="rounded-lg bg-surface-container px-3 py-1.5 opacity-50">Previous</span> @else <a href="{{ $customers->previousPageUrl() }}" class="rounded-lg bg-surface-container-low px-3 py-1.5 hover:bg-surface-container-high">Previous</a> @endif
                        @if ($customers->hasMorePages()) <a href="{{ $customers->nextPageUrl() }}" class="rounded-lg bg-surface-container-low px-3 py-1.5 hover:bg-surface-container-high">Next</a> @else <span class="rounded-lg bg-surface-container px-3 py-1.5 opacity-50">Next</span> @endif
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection