@extends('portal.layout')

@section('title', 'Vouchers & Promotions')

@php
    $money = fn ($n) => '$' . number_format((float) $n, 2);
    $badge = ['active' => 'bg-tertiary-fixed text-on-tertiary-fixed', 'scheduled' => 'bg-secondary-container text-on-secondary-container', 'paused' => 'bg-surface-container text-on-surface-variant', 'expired' => 'bg-surface-container text-on-surface-variant', 'used_up' => 'bg-surface-container text-on-surface-variant'];
    $tz = \App\Support\VoucherForm::TZ;
    $activeCount = $vouchers->filter(fn ($v) => \App\Support\VoucherForm::status($v)[0] === 'active')->count();
@endphp

@section('header-actions')
    <a href="{{ route('portal.vouchers.create') }}" class="flex items-center gap-1.5 rounded-lg bg-secondary px-4 py-2 text-label-lg text-on-secondary hover:bg-on-secondary-container"><span class="material-symbols-outlined text-[18px]">add</span> New voucher</a>
@endsection

@section('content')
<div class="flex flex-col gap-space-lg">
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-3 lg:gap-4">
        <div class="rounded-2xl bg-surface-container-lowest p-4 shadow-sm sm:p-5"><span class="text-label-sm uppercase text-on-surface-variant">Active now</span><div class="mt-2 text-display-lg font-extrabold text-secondary">{{ $activeCount }}</div><p class="mt-1 text-body-sm text-on-surface-variant">of {{ $vouchers->count() }} {{ $vouchers->count() == 1 ? 'voucher' : 'vouchers' }}</p></div>
        <div class="rounded-2xl bg-surface-container-lowest p-4 shadow-sm sm:p-5"><span class="text-label-sm uppercase text-on-surface-variant">Rentals with a voucher</span><div class="mt-2 text-display-lg font-extrabold text-on-surface">{{ $vouchers->sum('rentals') }}</div><p class="mt-1 text-body-sm text-on-surface-variant">confirmed, ongoing or completed</p></div>
        <div class="col-span-2 rounded-2xl bg-surface-container-lowest p-4 shadow-sm sm:p-5 lg:col-span-1"><span class="text-label-sm uppercase text-on-surface-variant">Discounts given</span><div class="mt-2 text-display-lg font-extrabold text-on-surface">{{ $money($vouchers->sum('discount_given')) }}</div><p class="mt-1 text-body-sm text-on-surface-variant">taken off your rental prices</p></div>
    </div>

    <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-sm">
        @if ($vouchers->isEmpty())
            <div class="p-10 text-center">
                <span class="material-symbols-outlined text-5xl text-on-surface-variant/40">sell</span>
                <p class="mt-2 text-headline-sm text-on-surface">No vouchers yet</p>
                <p class="mx-auto mt-1 max-w-md text-body-md text-on-surface-variant">Create a code such as WELCOME10 to attract new customers. They type it at checkout and see the discount straight away.</p>
                <a href="{{ route('portal.vouchers.create') }}" class="mt-5 inline-flex items-center gap-1.5 rounded-lg bg-secondary px-4 py-2 text-label-lg text-on-secondary hover:bg-on-secondary-container">Create your first voucher</a>
            </div>
        @else
            <div class="relative overflow-x-auto">
                <table class="w-full text-left text-body-md">
                    <thead class="bg-surface-container-low text-label-sm uppercase text-on-surface-variant">
                        <tr><th class="px-5 py-3">Code</th><th class="px-3 py-3">Offer</th><th class="px-3 py-3">Conditions</th><th class="px-3 py-3 text-right">Used</th><th class="px-3 py-3 text-right">Discount given</th><th class="px-3 py-3">Status</th><th class="px-5 py-3 text-right"><span class="sr-only">Actions</span></th></tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container">
                        @foreach ($vouchers as $v)
                            @php [$key, $label] = \App\Support\VoucherForm::status($v); @endphp
                            <tr class="align-top">
                                <td class="px-5 py-3"><div class="font-bold tracking-wide text-on-surface">{{ $v->code }}</div>@if ($v->description)<div class="text-body-sm text-on-surface-variant">{{ $v->description }}</div>@endif</td>
                                <td class="whitespace-nowrap px-3 py-3 font-semibold text-secondary">{{ \App\Support\VoucherForm::offer($v) }}</td>
                                <td class="px-3 py-3 text-body-sm text-on-surface-variant">
                                    {{ (float) $v->min_subtotal > 0 ? 'Min. rental ' . $money($v->min_subtotal) : 'No minimum' }}<br>
                                    @if ($v->starts_at || $v->expires_at){{ $v->starts_at ? $v->starts_at->copy()->setTimezone($tz)->format('M j, Y') : 'Any time' }} → {{ $v->expires_at ? $v->expires_at->copy()->setTimezone($tz)->format('M j, Y') : 'no end' }}@else No dates @endif
                                </td>
                                <td class="px-3 py-3 text-right">{{ $v->used_count }}@if ($v->max_uses !== null) <span class="text-on-surface-variant">/ {{ $v->max_uses }}</span>@endif</td>
                                <td class="px-3 py-3 text-right font-semibold">{{ $money($v->discount_given) }}</td>
                                <td class="px-3 py-3"><span class="inline-block whitespace-nowrap rounded-full px-2.5 py-0.5 text-label-sm {{ $badge[$key] }}">{{ $label }}</span></td>
                                <td class="whitespace-nowrap px-5 py-3 text-right">
                                    <a href="{{ route('portal.vouchers.edit', $v->id) }}" class="text-label-md font-semibold text-secondary hover:underline">Edit</a>
                                    <form method="POST" action="{{ route('portal.vouchers.toggle', $v->id) }}" class="ml-3 inline">@csrf<button class="text-label-md font-semibold text-on-surface-variant hover:text-on-surface hover:underline">{{ $v->is_active ? 'Pause' : 'Resume' }}</button></form>
                                    <form method="POST" action="{{ route('portal.vouchers.destroy', $v->id) }}" class="ml-3 inline" onsubmit="return confirm('Delete {{ $v->code }}?')">@csrf @method('DELETE')<button class="text-label-md font-semibold text-error hover:underline">Delete</button></form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <p class="text-body-sm text-on-surface-variant">Your codes only work on your own vehicles. "Used" counts requests that are waiting for you or went ahead; a rejected, cancelled or expired request gives its use back. AngkorRide may also run codes that work at every shop; those are not listed here.</p>
</div>
@endsection