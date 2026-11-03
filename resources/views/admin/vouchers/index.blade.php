@extends('admin.layout')

@section('title', 'Vouchers')

@php
    $money = fn ($n) => '$' . number_format((float) $n, 2);
    $tabs = ['all' => 'All', 'platform' => 'Platform-wide', 'shop' => 'Shop codes'];
    $badge = ['active' => 'bg-tertiary-fixed text-on-tertiary-fixed', 'scheduled' => 'bg-secondary-container text-on-secondary-container', 'paused' => 'bg-surface-container text-on-surface-variant', 'expired' => 'bg-surface-container text-on-surface-variant', 'used_up' => 'bg-surface-container text-on-surface-variant'];
    $tz = \App\Support\VoucherForm::TZ;
    $keep = fn (array $o = []) => array_filter(array_merge(['tab' => $tab !== 'all' ? $tab : null, 'q' => $q !== '' ? $q : null], $o), fn ($v) => $v !== null);
@endphp

@section('header-actions')
    <div class="flex flex-wrap items-center gap-2">
        <form method="GET" action="{{ route('admin.vouchers.index') }}" class="flex items-center gap-2">
            @if ($tab !== 'all')<input type="hidden" name="tab" value="{{ $tab }}">@endif
            <input type="search" name="q" value="{{ $q }}" maxlength="40" placeholder="Search code or shop" class="w-52 rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2 text-body-md focus:border-secondary focus:outline-none">
            <button class="rounded-lg bg-primary px-3 py-2 text-label-md text-on-primary">Search</button>
        </form>
        <a href="{{ route('admin.vouchers.create') }}" class="flex items-center gap-1.5 rounded-lg bg-secondary px-4 py-2 text-label-lg text-on-secondary hover:bg-on-secondary-container"><span class="material-symbols-outlined text-[18px]">add</span> New platform voucher</a>
    </div>
@endsection

@section('content')
<div class="flex flex-col gap-space-md">
    <nav class="flex flex-wrap gap-2" aria-label="Voucher type">
        @foreach ($tabs as $key => $name)
            <a href="{{ route('admin.vouchers.index', $keep(['tab' => $key !== 'all' ? $key : null])) }}" @if ($tab === $key) aria-current="page" @endif
               class="rounded-full px-4 py-1.5 text-label-md {{ $tab === $key ? 'bg-primary text-on-primary' : 'bg-surface-container-lowest text-on-surface-variant hover:text-on-surface' }}">{{ $name }} <span class="opacity-70">{{ $counts[$key] }}</span></a>
        @endforeach
    </nav>

    <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-sm">
        @if ($vouchers->isEmpty())
            <p class="p-10 text-center text-body-md text-on-surface-variant">@if ($q !== '') No vouchers match “{{ $q }}”. @else No vouchers yet. Create a platform-wide code that works at every shop. @endif</p>
        @else
            <div class="relative overflow-x-auto">
                <table class="w-full text-left text-body-md">
                    <thead class="bg-surface-container-low text-label-sm uppercase text-on-surface-variant">
                        <tr><th class="px-5 py-3">Code</th><th class="px-3 py-3">Works at</th><th class="px-3 py-3">Offer</th><th class="px-3 py-3">Conditions</th><th class="px-3 py-3 text-right">Used</th><th class="px-3 py-3 text-right">Discount given</th><th class="px-3 py-3">Status</th><th class="px-5 py-3 text-right"><span class="sr-only">Actions</span></th></tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container">
                        @foreach ($vouchers as $v)
                            @php [$key, $label] = \App\Support\VoucherForm::status($v); @endphp
                            <tr class="align-top">
                                <td class="px-5 py-3"><div class="font-bold tracking-wide text-on-surface">{{ $v->code }}</div>@if ($v->description)<div class="text-body-sm text-on-surface-variant">{{ $v->description }}</div>@endif</td>
                                <td class="px-3 py-3">@if ($v->business)<a href="{{ route('admin.businesses.show', $v->business) }}" class="hover:text-secondary hover:underline">{{ $v->business->name }}</a>@else<span class="rounded-full bg-secondary-container px-2.5 py-0.5 text-label-sm text-on-secondary-container">Every shop</span>@endif</td>
                                <td class="whitespace-nowrap px-3 py-3 font-semibold text-secondary">{{ \App\Support\VoucherForm::offer($v) }}</td>
                                <td class="px-3 py-3 text-body-sm text-on-surface-variant">{{ (float) $v->min_subtotal > 0 ? 'Min. ' . $money($v->min_subtotal) : 'No minimum' }}<br>@if ($v->starts_at || $v->expires_at){{ $v->starts_at ? $v->starts_at->copy()->setTimezone($tz)->format('M j, Y') : 'Any time' }} → {{ $v->expires_at ? $v->expires_at->copy()->setTimezone($tz)->format('M j, Y') : 'no end' }}@else No dates @endif</td>
                                <td class="px-3 py-3 text-right">{{ $v->used_count }}@if ($v->max_uses !== null) <span class="text-on-surface-variant">/ {{ $v->max_uses }}</span>@endif</td>
                                <td class="px-3 py-3 text-right font-semibold">{{ $money($v->discount_given) }}</td>
                                <td class="px-3 py-3"><span class="inline-block whitespace-nowrap rounded-full px-2.5 py-0.5 text-label-sm {{ $badge[$key] }}">{{ $label }}</span></td>
                                <td class="whitespace-nowrap px-5 py-3 text-right">
                                    @unless ($v->business_id)<a href="{{ route('admin.vouchers.edit', $v->id) }}" class="text-label-md font-semibold text-secondary hover:underline">Edit</a>@endunless
                                    <form method="POST" action="{{ route('admin.vouchers.toggle', $v->id) }}" class="ml-3 inline">@csrf<button class="text-label-md font-semibold text-on-surface-variant hover:text-on-surface hover:underline">{{ $v->is_active ? 'Pause' : 'Resume' }}</button></form>
                                    @unless ($v->business_id)<form method="POST" action="{{ route('admin.vouchers.destroy', $v->id) }}" class="ml-3 inline" onsubmit="return confirm('Delete {{ $v->code }}?')">@csrf @method('DELETE')<button class="text-label-md font-semibold text-error hover:underline">Delete</button></form>@endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @include('admin.partials.pager', ['p' => $vouchers])
        @endif
    </div>
    <p class="text-body-sm text-on-surface-variant">Platform-wide codes work at every shop. Shop codes belong to the shop: you can pause one (for example if it is misused) but not edit it. “Discount given” counts confirmed, ongoing and completed rentals.</p>
</div>
@endsection