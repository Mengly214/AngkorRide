@extends('admin.layout')

@section('title', $voucher ? 'Edit platform voucher' : 'New platform voucher')

@section('header-actions')
    <a href="{{ route('admin.vouchers.index') }}" class="text-label-lg text-secondary hover:underline">&larr; All vouchers</a>
@endsection

@section('content')
<form method="POST" action="{{ $voucher ? route('admin.vouchers.update', $voucher->id) : route('admin.vouchers.store') }}" class="max-w-3xl rounded-2xl bg-surface-container-lowest p-5 shadow-sm sm:p-7" novalidate>
    @csrf
    @if ($voucher) @method('PUT') @endif

    <div class="mb-5 flex items-start gap-2 rounded-xl bg-secondary-container px-4 py-3 text-body-md text-on-secondary-container">
        <span class="material-symbols-outlined text-[20px]">public</span>
        <span>A platform-wide code works at <strong>every shop</strong>. Shops receive the discounted price, so a platform-wide code lowers their income. Use it for campaigns you have agreed with your partners.</span>
    </div>

    @include('partials.voucher-fields', ['values' => $values, 'voucher' => $voucher])

    <div class="mt-6 flex flex-wrap items-center gap-3">
        <button type="submit" class="rounded-lg bg-secondary px-5 py-2.5 text-label-lg text-on-secondary hover:bg-on-secondary-container">{{ $voucher ? 'Save changes' : 'Create voucher' }}</button>
        <a href="{{ route('admin.vouchers.index') }}" class="rounded-lg px-4 py-2.5 text-label-lg text-on-surface-variant hover:bg-surface-container-high">Cancel</a>
    </div>
</form>
@endsection