@extends('admin.layout')

@section('title', 'Users')

@php
    $tabs = ['all' => 'All', 'customer' => 'Customers', 'business_admin' => 'Shop owners', 'admin' => 'Admins'];
    $roleName = ['customer' => 'Customer', 'business_admin' => 'Shop owner', 'admin' => 'Admin'];
    $keep = fn (array $o = []) => array_filter(array_merge(['tab' => $tab !== 'all' ? $tab : null, 'status' => $status, 'q' => $q !== '' ? $q : null], $o), fn ($v) => $v !== null);
@endphp

@section('header-actions')
    <form method="GET" action="{{ route('admin.users.index') }}" class="flex items-center gap-2">
        @if ($tab !== 'all')<input type="hidden" name="tab" value="{{ $tab }}">@endif
        @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
        <input type="search" name="q" value="{{ $q }}" maxlength="60" placeholder="Search name, phone or email" class="w-64 rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2 text-body-md focus:border-secondary focus:outline-none">
        <button class="rounded-lg bg-primary px-3 py-2 text-label-md text-on-primary">Search</button>
    </form>
@endsection

@section('content')
<div class="flex flex-col gap-space-md">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <nav class="flex flex-wrap gap-2" aria-label="Role">
            @foreach ($tabs as $key => $name)
                <a href="{{ route('admin.users.index', $keep(['tab' => $key !== 'all' ? $key : null])) }}" @if ($tab === $key) aria-current="page" @endif
                   class="rounded-full px-4 py-1.5 text-label-md {{ $tab === $key ? 'bg-primary text-on-primary' : 'bg-surface-container-lowest text-on-surface-variant hover:text-on-surface' }}">{{ $name }} <span class="opacity-70">{{ $counts[$key] }}</span></a>
            @endforeach
        </nav>
        <div class="flex items-center gap-1 text-body-sm text-on-surface-variant">
            <span class="mr-1">Show</span>
            @foreach ([null => 'Everyone', 'active' => 'Active', 'suspended' => 'Suspended'] as $key => $name)
                <a href="{{ route('admin.users.index', $keep(['status' => $key])) }}" class="rounded-lg px-2.5 py-1 {{ (string) $status === (string) $key ? 'bg-secondary-container font-semibold text-on-secondary-container' : 'hover:bg-surface-container-high' }}">{{ $name }}</a>
            @endforeach
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-sm">
        @if ($rows->isEmpty())
            <p class="p-10 text-center text-body-md text-on-surface-variant">No users match.</p>
        @else
            <div class="relative overflow-x-auto">
                <table class="w-full text-left text-body-md">
                    <thead class="bg-surface-container-low text-label-sm uppercase text-on-surface-variant">
                        <tr><th class="px-5 py-3">User</th><th class="px-3 py-3">Role</th><th class="px-3 py-3 text-right">Bookings</th><th class="px-3 py-3">Joined</th><th class="px-3 py-3">Status</th><th class="px-5 py-3 text-right">Action</th></tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container">
                        @foreach ($rows as $u)
                            <tr class="align-top">
                                <td class="px-5 py-3"><div class="font-semibold text-on-surface">{{ $u->full_name }}@if ($u->id === auth()->id()) <span class="font-normal text-on-surface-variant">(you)</span>@endif</div><div class="text-body-sm text-on-surface-variant">{{ $u->phone }}@if ($u->email) &bull; {{ $u->email }}@endif</div></td>
                                <td class="px-3 py-3">{{ $roleName[$u->role] ?? $u->role }}</td>
                                <td class="px-3 py-3 text-right">{{ $u->role === 'customer' ? $u->bookings_count : '—' }}</td>
                                <td class="px-3 py-3 text-on-surface-variant">{{ $u->created_at->format('M j, Y') }}</td>
                                <td class="px-3 py-3">@include('admin.partials.badge', ['status' => $u->is_active ? 'active' : 'suspended'])</td>
                                <td class="px-5 py-3 text-right">
                                    @if ($u->id === auth()->id())
                                        <span class="text-body-sm text-on-surface-variant">—</span>
                                    @elseif ($u->is_active)
                                        <details class="inline-block text-left">
                                            <summary class="cursor-pointer list-none rounded-lg bg-surface-container px-3 py-1.5 text-label-md font-semibold text-on-surface hover:bg-surface-container-high [&::-webkit-details-marker]:hidden">Suspend</summary>
                                            <form method="POST" action="{{ route('admin.users.toggle', $u) }}" class="mt-2 w-64 space-y-2 rounded-xl bg-surface-container-low p-3" onsubmit="return confirm('Suspend {{ addslashes($u->full_name) }}? They will be signed out right away.')">@csrf
                                                <label class="block text-label-sm text-on-surface-variant" for="r-{{ $u->id }}">Reason</label>
                                                <textarea id="r-{{ $u->id }}" name="reason" rows="2" maxlength="500" required class="w-full rounded-lg border border-outline-variant bg-surface-container-lowest px-2 py-1.5 text-body-sm focus:outline-none"></textarea>
                                                <button class="w-full rounded-lg bg-error-container px-3 py-1.5 text-label-md text-on-error-container">Suspend account</button>
                                            </form>
                                        </details>
                                    @else
                                        <form method="POST" action="{{ route('admin.users.toggle', $u) }}">@csrf<button class="rounded-lg bg-secondary px-3 py-1.5 text-label-md text-on-secondary hover:bg-on-secondary-container">Reactivate</button></form>
                                    @endif
                                </td>
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