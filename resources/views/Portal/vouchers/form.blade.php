@extends('portal.layout')

@section('title', $voucher ? 'Edit voucher' : 'New voucher')

@section('header-actions')
    <a href="{{ route('portal.vouchers.index') }}" class="text-label-lg text-secondary hover:underline">&larr; All vouchers</a>
@endsection

@section('content')
<form method="POST" action="{{ $voucher ? route('portal.vouchers.update', $voucher->id) : route('portal.vouchers.store') }}" class="max-w-3xl rounded-2xl bg-surface-container-lowest p-5 shadow-sm sm:p-7" novalidate>
    @csrf
    @if ($voucher) @method('PUT') @endif

    @include('partials.voucher-fields', ['values' => $values, 'voucher' => $voucher])

    @if ($voucher)<p class="mt-3 text-body-sm text-on-surface-variant">Changes apply to future bookings. Bookings that already used this code keep the discount they got.</p>@endif

    <div class="mt-6 flex flex-wrap items-center gap-3">
        <button type="submit" class="rounded-lg bg-secondary px-5 py-2.5 text-label-lg text-on-secondary hover:bg-on-secondary-container">{{ $voucher ? 'Save changes' : 'Create voucher' }}</button>
        <a href="{{ route('portal.vouchers.index') }}" class="rounded-lg px-4 py-2.5 text-label-lg text-on-surface-variant hover:bg-surface-container-high">Cancel</a>
    </div>
</form>
@endsection