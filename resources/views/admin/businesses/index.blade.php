@extends('admin.layout')

@section('title', 'Businesses')

@php
    $tabs = ['pending' => 'To review', 'approved' => 'Approved', 'rejected' => 'Rejected', 'suspended' => 'Suspended', 'all' => 'All'];
    $keep = fn (array $o = []) => array_filter(array_merge(['tab' => $tab !== 'pending' ? $tab : null, 'q' => $q !== '' ? $q : null], $o), fn ($v) => $v !== null);
@endphp

@section('header-actions')
    <form method="GET" action="{{ route('admin.businesses.index') }}" class="flex items-center gap-2">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="search" name="q" value="{{ $q }}" maxlength="60" placeholder="Search shop, city, owner or phone" class="w-64 rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2 text-body-md focus:border-secondary focus:outline-none">
        <button class="rounded-lg bg-primary px-3 py-2 text-label-md text-on-primary">Search</button>
    </form>
@endsection

@section('content')
<div class="flex flex-col gap-space-md">
    <nav class="flex flex-wrap gap-2" aria-label="Status">
        @foreach ($tabs as $key => $name)
            <a href="{{ route('admin.businesses.index', $keep(['tab' => $key])) }}" @if ($tab === $key) aria-current="page" @endif
               class="rounded-full px-4 py-1.5 text-label-md {{ $tab === $key ? 'bg-primary text-on-primary' : 'bg-surface-container-lowest text-on-surface-variant hover:text-on-surface' }}">{{ $name }} <span class="opacity-70">{{ $counts[$key] }}</span></a>
        @endforeach
    </nav>

    <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-sm">
        @if ($rows->isEmpty())
            <p class="p-10 text-center text-body-md text-on-surface-variant">
                @if ($q !== '') No shops match “{{ $q }}”. <a class="font-semibold text-secondary hover:underline" href="{{ route('admin.businesses.index', ['tab' => 'all']) }}">Show all</a>
                @elseif ($tab === 'pending') Nothing to review right now.
                @else No shops in this list. @endif
            </p>
        @else
            <div class="relative overflow-x-auto">
                <table class="w-full text-left text-body-md">
                    <thead class="bg-surface-container-low text-label-sm uppercase text-on-surface-variant">
                        <tr><th class="px-5 py-3">Shop</th><th class="px-3 py-3">Owner</th><th class="px-3 py-3 text-right">Vehicles</th><th class="px-3 py-3 text-right">Bookings</th><th class="px-3 py-3">Applied</th><th class="px-3 py-3">Status</th><th class="px-5 py-3 text-right"><span class="sr-only">Open</span></th></tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container">
                        @foreach ($rows as $b)
                            <tr class="hover:bg-surface-container-low/50">
                                <td class="px-5 py-3">
                                    <a href="{{ route('admin.businesses.show', $b) }}" class="font-semibold text-on-surface hover:text-secondary">{{ $b->name }}</a>
                                    <div class="text-body-sm text-on-surface-variant">{{ $b->city }}@unless ($b->is_active) &bull; paused by owner @endunless</div>
                                </td>
                                <td class="px-3 py-3"><div>{{ $b->owner->full_name ?? '—' }}</div><div class="text-body-sm text-on-surface-variant">{{ $b->owner->phone ?? '' }}</div></td>
                                <td class="px-3 py-3 text-right">{{ $b->vehicles_count }}</td>
                                <td class="px-3 py-3 text-right">{{ $b->bookings_count }}</td>
                                <td class="px-3 py-3 text-on-surface-variant">{{ $b->created_at->format('M j, Y') }}</td>
                                <td class="px-3 py-3">@include('admin.partials.badge', ['status' => $b->admin_status])</td>
                                <td class="px-5 py-3 text-right"><a href="{{ route('admin.businesses.show', $b) }}" class="text-label-md font-semibold text-secondary hover:underline">{{ $b->admin_status === 'pending' ? 'Review' : 'View' }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @include('admin.partials.pager', ['p' => $rows])
        @endif
    </div>
</div>
@endsection