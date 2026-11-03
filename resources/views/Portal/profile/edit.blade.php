@extends('portal.layout')

@section('title', 'Business Profile')

@php
    $field = 'w-full h-11 rounded-xl border border-transparent bg-surface-container-low px-3.5 text-body-md text-on-surface focus:border-secondary focus:bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-secondary/30';
    $area = 'w-full rounded-xl border border-transparent bg-surface-container-low px-3.5 py-2.5 text-body-md text-on-surface focus:border-secondary focus:bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-secondary/30';
    $err = fn ($k) => $errors->has($k) ? ' ring-2 ring-error/50' : '';
    $val = fn ($k, $default = null) => old($k, $default);
    $active = old('is_active', $business->is_active ? '1' : '0') == '1';
@endphp

@section('header-actions')
    <a href="{{ route('business.show', $business) }}" target="_blank" rel="noopener" class="flex items-center gap-1.5 rounded-lg bg-surface-container px-3.5 py-2 text-label-lg text-on-surface transition-colors hover:bg-surface-container-high">
        <span class="material-symbols-outlined text-[18px]">open_in_new</span> View public page
    </a>
@endsection

@section('content')
<form method="POST" action="{{ route('portal.profile.update') }}" enctype="multipart/form-data" class="mx-auto flex w-full max-w-3xl flex-col gap-space-lg">
    @csrf
    @method('PUT')

    @if ($errors->any())
        <div role="alert" class="rounded-xl bg-error-container px-4 py-3 text-body-md text-on-error-container">
            <ul class="list-disc space-y-0.5 pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Listing status --}}
    <section class="flex items-start justify-between gap-4 rounded-2xl bg-surface-container-lowest p-5 shadow-sm sm:p-6">
        <div class="min-w-0">
            <h2 class="text-headline-sm text-on-surface">Listing status</h2>
            <p class="mt-1 text-body-md text-on-surface-variant">
                When off, your shop and all its vehicles are hidden from customers. Bookings you already have are not affected.
            </p>
            @unless ($business->is_verified)
                <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-surface-container px-3 py-1 text-label-sm text-on-surface-variant">
                    <span class="material-symbols-outlined text-sm">info</span> The Verified badge is added by AngkorRide after checking your shop.
                </p>
            @else
                <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-secondary-container/60 px-3 py-1 text-label-sm text-on-secondary-container">
                    <span class="material-symbols-outlined text-sm">verified</span> Verified shop
                </p>
            @endunless
        </div>
        <label class="relative mt-1 inline-flex shrink-0 cursor-pointer items-center gap-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked($active) class="peer sr-only" aria-label="Accepting bookings">
            <span class="h-7 w-12 rounded-full bg-surface-variant transition-colors peer-checked:bg-secondary peer-focus-visible:ring-2 peer-focus-visible:ring-secondary/40"></span>
            <span class="pointer-events-none absolute left-1 top-1 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
            <span id="activeLabel" class="text-label-md text-on-surface-variant">{{ $active ? 'On' : 'Off' }}</span>
        </label>
    </section>

    {{-- Logo --}}
    <section class="rounded-2xl bg-surface-container-lowest p-5 shadow-sm sm:p-6">
        <h2 class="text-headline-sm text-on-surface">Logo</h2>
        <div class="mt-4 flex flex-wrap items-center gap-4">
            <div id="logoBox" class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-2xl bg-surface-container-low text-on-surface-variant">
                @if ($business->logo_url)
                    <img id="logoPreview" src="{{ $business->logo_url }}" alt="Current logo" class="h-full w-full object-cover">
                @else
                    <span class="material-symbols-outlined text-4xl">storefront</span>
                @endif
            </div>
            <div class="flex flex-col gap-2">
                <input type="file" name="logo" id="logo" accept="image/jpeg,image/png,image/webp" class="block text-body-sm text-on-surface-variant file:mr-3 file:rounded-lg file:border-0 file:bg-surface-container file:px-3.5 file:py-2 file:text-label-md file:text-on-surface hover:file:bg-surface-container-high">
                <p class="text-body-sm text-on-surface-variant">JPG, PNG or WebP, up to 3 MB. A square picture works best.</p>
                @if ($business->logo_url)
                    <label class="flex items-center gap-2 text-body-md text-on-surface-variant"><input type="checkbox" name="remove_logo" value="1" class="h-4 w-4 accent-secondary"> Remove current logo</label>
                @endif
            </div>
        </div>
    </section>

    {{-- Details --}}
    <section class="flex flex-col gap-5 rounded-2xl bg-surface-container-lowest p-5 shadow-sm sm:p-6">
        <h2 class="text-headline-sm text-on-surface">Shop details</h2>

        <div class="flex flex-col gap-1.5">
            <label for="name" class="text-label-md text-on-surface">Shop name</label>
            <input type="text" id="name" name="name" value="{{ $val('name', $business->name) }}" required maxlength="150" class="{{ $field }}{{ $err('name') }}">
        </div>

        <div class="flex flex-col gap-1.5">
            <label for="description" class="text-label-md text-on-surface">About your shop</label>
            <textarea id="description" name="description" rows="4" maxlength="1000" placeholder="Tell customers what you rent and why they can trust you." class="{{ $area }}{{ $err('description') }}">{{ $val('description', $business->description) }}</textarea>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="flex flex-col gap-1.5">
                <label for="city" class="text-label-md text-on-surface">City</label>
                <input type="text" id="city" name="city" value="{{ $val('city', $business->city) }}" required maxlength="100" list="cityList" autocomplete="off" class="{{ $field }}{{ $err('city') }}">
                <datalist id="cityList">@foreach ($cities as $c)<option value="{{ $c }}">@endforeach</datalist>
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="phone" class="text-label-md text-on-surface">Shop phone</label>
                <input type="tel" id="phone" name="phone" value="{{ $val('phone', $business->phone) }}" maxlength="20" class="{{ $field }}{{ $err('phone') }}">
            </div>
        </div>

        <div class="flex flex-col gap-1.5">
            <label for="address" class="text-label-md text-on-surface">Address</label>
            <input type="text" id="address" name="address" value="{{ $val('address', $business->address) }}" maxlength="255" class="{{ $field }}{{ $err('address') }}">
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="flex flex-col gap-1.5">
                <label for="email" class="text-label-md text-on-surface">Shop email</label>
                <input type="email" id="email" name="email" value="{{ $val('email', $business->email) }}" maxlength="255" class="{{ $field }}{{ $err('email') }}">
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="registration_no" class="text-label-md text-on-surface">Business registration no.</label>
                <input type="text" id="registration_no" name="registration_no" value="{{ $val('registration_no', $business->registration_no) }}" maxlength="100" class="{{ $field }}{{ $err('registration_no') }}">
            </div>
        </div>
    </section>

    {{-- Hours --}}
    <section class="rounded-2xl bg-surface-container-lowest p-5 shadow-sm sm:p-6">
        <h2 class="text-headline-sm text-on-surface">Opening hours</h2>
        <p class="mt-1 text-body-md text-on-surface-variant">Shown on your shop page. Leave both empty to hide them.</p>
        <div class="mt-4 grid grid-cols-2 gap-5 sm:max-w-md">
            <div class="flex flex-col gap-1.5">
                <label for="opening_time" class="text-label-md text-on-surface">Opens</label>
                <input type="time" id="opening_time" name="opening_time" value="{{ $val('opening_time', $business->opening_time?->format('H:i')) }}" class="{{ $field }}{{ $err('opening_time') }}">
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="closing_time" class="text-label-md text-on-surface">Closes</label>
                <input type="time" id="closing_time" name="closing_time" value="{{ $val('closing_time', $business->closing_time?->format('H:i')) }}" class="{{ $field }}{{ $err('closing_time') }}">
            </div>
        </div>
    </section>

    <div class="flex justify-end gap-3 pb-6">
        <button type="submit" class="flex items-center gap-2 rounded-xl bg-secondary px-6 py-3 text-label-lg text-on-secondary shadow-sm transition-colors hover:bg-on-secondary-container">
            <span class="material-symbols-outlined text-[20px]">check</span> Save changes
        </button>
    </div>
</form>

@include('portal.partials.photo-script')
<script>
(function () {
    var cb = document.querySelector('input[type=checkbox][name=is_active]'), lab = document.getElementById('activeLabel');
    if (cb && lab) cb.addEventListener('change', function () { lab.textContent = cb.checked ? 'On' : 'Off'; });
})();
(function () {
    var input = document.getElementById('logo'), box = document.getElementById('logoBox');
    if (!input) return;
    input.addEventListener('change', function () {
        window.arShrinkFiles(input, function (files) {
            if (!files[0]) return;
            var img = box.querySelector('img');
            if (!img) { box.innerHTML = ''; img = document.createElement('img'); img.className = 'h-full w-full object-cover'; img.alt = 'New logo'; box.appendChild(img); }
            img.src = URL.createObjectURL(files[0]);
        });
    });
})();
</script>
@endsection