@extends('admin.layout')

@section('title', $business->name)

@php
    $money = fn ($n) => '$' . number_format((float) $n, 2);
    $checks = [
        ['Business description', filled($business->description)],
        ['Registration number', filled($business->registration_no)],
        ['Address', filled($business->address)],
        ['Contact phone', filled($business->phone)],
        ['Logo', filled($business->logo_url)],
        ['At least one vehicle', $business->vehicles_count > 0],
    ];
    $note = fn ($label, $v) => $v ? "<div><dt class=\"text-label-sm uppercase text-on-surface-variant\">{$label}</dt><dd class=\"mt-0.5 text-on-surface\">" . e($v) . '</dd></div>' : '';
    $vehicleStatus = ['available' => 'Available', 'rented' => 'Rented', 'maintenance' => 'Maintenance', 'retired' => 'Retired'];
@endphp

@section('header-actions')
    <div class="flex items-center gap-3">
        @include('admin.partials.badge', ['status' => $business->admin_status])
        @if ($business->isListed())<a href="{{ route('business.show', $business) }}" target="_blank" rel="noopener" class="flex items-center gap-1 text-label-md text-secondary hover:underline">Public page <span class="material-symbols-outlined text-[16px]">open_in_new</span></a>@endif
        <a href="{{ route('admin.businesses.index') }}" class="text-label-md text-on-surface-variant hover:underline">&larr; All shops</a>
    </div>
@endsection

@section('content')
<div class="grid grid-cols-1 gap-space-lg xl:grid-cols-3">
    <div class="flex flex-col gap-space-lg xl:col-span-2">

        <section class="rounded-2xl bg-surface-container-lowest p-5 shadow-sm sm:p-6">
            <div class="flex items-start gap-4">
                @if ($business->logo_url)
                    <img src="{{ $business->logo_url }}" alt="" class="h-16 w-16 shrink-0 rounded-xl object-cover">
                @else
                    <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-secondary-container text-headline-md text-on-secondary-container">{{ \Illuminate\Support\Str::upper(mb_substr($business->name, 0, 1)) }}</span>
                @endif
                <div class="min-w-0">
                    <h2 class="text-headline-md text-on-surface">{{ $business->name }}</h2>
                    <p class="text-body-md text-on-surface-variant">{{ $business->city }} &bull; applied {{ $business->created_at->format('M j, Y') }}@unless ($business->is_active) &bull; paused by the owner @endunless</p>
                </div>
            </div>
            <dl class="mt-5 grid grid-cols-1 gap-4 text-body-md sm:grid-cols-2">
                {!! $note('Address', $business->address) !!}
                {!! $note('Phone', $business->phone) !!}
                {!! $note('Email', $business->email) !!}
                {!! $note('Registration number', $business->registration_no) !!}
                {!! $note('Opening hours', $business->opening_time && $business->closing_time ? $business->opening_time->format('H:i') . ' – ' . $business->closing_time->format('H:i') : null) !!}
                <div><dt class="text-label-sm uppercase text-on-surface-variant">Owner</dt><dd class="mt-0.5 text-on-surface">{{ $business->owner->full_name ?? '—' }}<br><span class="text-on-surface-variant">{{ $business->owner->phone ?? '' }}@if ($business->owner?->email) &bull; {{ $business->owner->email }}@endif</span>@if ($business->owner && ! $business->owner->is_active) <span class="ml-1 text-label-sm text-error">account suspended</span>@endif</dd></div>
            </dl>
            @if ($business->description)
                <div class="mt-5"><div class="text-label-sm uppercase text-on-surface-variant">About</div><p class="mt-1 whitespace-pre-line text-body-md text-on-surface">{{ $business->description }}</p></div>
            @endif
        </section>

        <div class="grid grid-cols-3 gap-3">
            <div class="rounded-2xl bg-surface-container-lowest p-4 shadow-sm"><span class="text-label-sm uppercase text-on-surface-variant">Vehicles</span><div class="mt-1 text-headline-lg font-extrabold">{{ $business->vehicles_count }}</div></div>
            <div class="rounded-2xl bg-surface-container-lowest p-4 shadow-sm"><span class="text-label-sm uppercase text-on-surface-variant">Bookings</span><div class="mt-1 text-headline-lg font-extrabold">{{ $business->bookings_count }}</div></div>
            <div class="rounded-2xl bg-surface-container-lowest p-4 shadow-sm"><span class="text-label-sm uppercase text-on-surface-variant">Booking value</span><div class="mt-1 text-headline-lg font-extrabold">{{ $money($gross) }}</div></div>
        </div>

        <section class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-sm">
            <div class="border-b border-surface-container px-5 py-4"><h2 class="text-headline-sm text-on-surface">Vehicles</h2></div>
            @if ($vehicles->isEmpty())
                <p class="p-6 text-body-md text-on-surface-variant">This shop has not added any vehicles yet.</p>
            @else
                <div class="relative overflow-x-auto"><table class="w-full text-left text-body-md">
                    <thead class="bg-surface-container-low text-label-sm uppercase text-on-surface-variant"><tr><th class="px-5 py-3">Vehicle</th><th class="px-3 py-3">Type</th><th class="px-3 py-3 text-right">Per day</th><th class="px-5 py-3 text-right">Status</th></tr></thead>
                    <tbody class="divide-y divide-surface-container">
                        @foreach ($vehicles as $v)
                            <tr><td class="px-5 py-3"><div class="font-semibold text-on-surface">{{ $v->brand }} {{ $v->model }}</div><div class="text-body-sm text-on-surface-variant">{{ $v->plate_number }}</div></td><td class="px-3 py-3">{{ $v->vehicleType->name ?? '—' }}</td><td class="px-3 py-3 text-right">{{ $money($v->price_per_day) }}</td><td class="px-5 py-3 text-right text-on-surface-variant">{{ $vehicleStatus[$v->status] ?? ucfirst($v->status) }}</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            @endif
        </section>

        @if ($bookings->isNotEmpty())
            <section class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-sm">
                <div class="border-b border-surface-container px-5 py-4"><h2 class="text-headline-sm text-on-surface">Latest bookings</h2></div>
                <ul class="divide-y divide-surface-container">
                    @foreach ($bookings as $b)
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <div class="min-w-0"><div class="font-semibold text-on-surface">{{ $b->reference ?? '—' }} <span class="font-normal text-on-surface-variant">&bull; {{ $b->customer->full_name ?? '—' }}</span></div><div class="truncate text-body-sm text-on-surface-variant">{{ trim(($b->vehicle->brand ?? '') . ' ' . ($b->vehicle->model ?? '')) }} &bull; {{ $b->start_datetime->format('M j') }} → {{ $b->end_datetime->format('M j, Y') }}</div></div>
                            <div class="flex shrink-0 items-center gap-3"><span class="font-semibold">{{ $money($b->total_price) }}</span>@include('admin.partials.badge', ['status' => $b->displayStatus()])</div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>

    {{-- Review panel --}}
    <aside class="flex flex-col gap-space-lg">
        <section class="rounded-2xl bg-surface-container-lowest p-5 shadow-sm">
            <h2 class="text-headline-sm text-on-surface">Review</h2>

            @if ($business->admin_note && in_array($business->admin_status, ['rejected', 'suspended']))
                <div class="mt-3 rounded-lg bg-error-container px-3 py-2 text-body-sm text-on-error-container"><strong>{{ $business->admin_status === 'rejected' ? 'Rejected' : 'Suspended' }}:</strong> {{ $business->admin_note }}</div>
            @endif
            @if ($business->reviewed_at)
                <p class="mt-3 text-body-sm text-on-surface-variant">Last decision {{ $business->reviewed_at->diffForHumans() }}@if ($reviewer) by {{ $reviewer->full_name }}@endif.</p>
            @endif

            <ul class="mt-4 space-y-1.5 text-body-md">
                @foreach ($checks as [$label, $ok])
                    <li class="flex items-center gap-2 {{ $ok ? 'text-on-surface' : 'text-on-surface-variant' }}"><span class="material-symbols-outlined text-[18px] {{ $ok ? 'text-secondary' : 'text-outline' }}">{{ $ok ? 'check_circle' : 'radio_button_unchecked' }}</span>{{ $label }}</li>
                @endforeach
            </ul>
            <p class="mt-2 text-body-sm text-on-surface-variant">Shops upload no documents yet; check the registration number against your own records.</p>

            <div class="mt-5 flex flex-col gap-3 border-t border-surface-container pt-5">
                @if (array_key_exists('approve', $actions))
                    <form method="POST" action="{{ route('admin.businesses.action', [$business, 'approve']) }}">@csrf
                        <button class="flex w-full items-center justify-center gap-1.5 rounded-lg bg-secondary px-4 py-2.5 text-label-lg text-on-secondary hover:bg-on-secondary-container"><span class="material-symbols-outlined text-[18px]">verified</span> {{ $business->admin_status === 'rejected' ? 'Approve after all' : 'Approve shop' }}</button>
                    </form>
                @endif
                @if (array_key_exists('reinstate', $actions))
                    <form method="POST" action="{{ route('admin.businesses.action', [$business, 'reinstate']) }}">@csrf
                        <button class="flex w-full items-center justify-center gap-1.5 rounded-lg bg-secondary px-4 py-2.5 text-label-lg text-on-secondary hover:bg-on-secondary-container"><span class="material-symbols-outlined text-[18px]">restart_alt</span> Reinstate shop</button>
                    </form>
                @endif
                @foreach (['reject' => ['Reject application', 'Why is it rejected? The owner will see this.'], 'suspend' => ['Suspend shop', 'Why is it suspended? The owner will see this.']] as $act => [$btn, $hint])
                    @if (array_key_exists($act, $actions))
                        <form method="POST" action="{{ route('admin.businesses.action', [$business, $act]) }}" onsubmit="return confirm('{{ $btn }}? The shop will be hidden from customers.')" class="space-y-2">@csrf
                            <label for="reason-{{ $act }}" class="block text-label-md text-on-surface-variant">{{ $hint }}</label>
                            <textarea id="reason-{{ $act }}" name="reason" rows="3" maxlength="500" required class="w-full rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2 text-body-md focus:border-error focus:outline-none">{{ old('reason') }}</textarea>
                            <button class="flex w-full items-center justify-center gap-1.5 rounded-lg bg-error-container px-4 py-2.5 text-label-lg text-on-error-container hover:brightness-95"><span class="material-symbols-outlined text-[18px]">{{ $act === 'reject' ? 'cancel' : 'block' }}</span> {{ $btn }}</button>
                        </form>
                    @endif
                @endforeach
            </div>
            <p class="mt-4 text-body-sm text-on-surface-variant">Visible to customers: <strong>{{ $business->isListed() ? 'yes' : 'no' }}</strong>. New shops are visible while waiting for review; rejected and suspended shops are hidden.</p>
        </section>
    </aside>
</div>
@endsection