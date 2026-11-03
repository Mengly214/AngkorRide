 @extends('layouts.customer')

@section('title', 'Become a partner')

@php
    $field = 'w-full h-12 rounded-xl border border-transparent bg-surface-container-low px-4 text-body-md text-on-surface focus:border-secondary focus:bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-secondary/30';
    $area = 'w-full rounded-xl border border-transparent bg-surface-container-low px-4 py-3 text-body-md text-on-surface focus:border-secondary focus:bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-secondary/30';
    $err = fn ($k) => $errors->has($k) ? ' ring-2 ring-error/50' : '';
@endphp

@section('content')
<div class="relative mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 lg:px-12 lg:py-14">
    <div class="pointer-events-none absolute -top-6 left-1/2 -z-10 h-64 w-3/4 -translate-x-1/2 rounded-full bg-gradient-to-b from-secondary/10 via-secondary-container/20 to-transparent blur-3xl"></div>

    <div class="grid grid-cols-1 items-stretch gap-8 lg:grid-cols-5">
        <form method="POST" action="{{ route('portal.register.submit') }}" class="flex flex-col gap-6 rounded-3xl border border-surface-container bg-surface-container-lowest p-6 shadow-lg sm:p-8 lg:col-span-3 lg:p-10">
            @csrf

            <div class="flex flex-col gap-2">
                <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-secondary-container/60 px-3 py-1 text-label-sm uppercase tracking-wide text-on-secondary-container">
                    <span class="material-symbols-outlined text-sm">storefront</span> Partner program
                </span>
                <h1 class="text-display-lg tracking-tight text-on-surface">Register your rental shop</h1>
                <p class="text-body-md text-on-surface-variant">Create your shop in two minutes. You can add vehicles and photos right after.</p>
            </div>

            @if ($errors->any())
                <div role="alert" class="flex items-start gap-2 rounded-xl bg-error-container px-4 py-3 text-body-md text-on-error-container">
                    <span class="material-symbols-outlined text-xl">error</span><span>Please fix the highlighted fields and try again.</span>
                </div>
            @endif

            {{-- Shop --}}
            <fieldset class="flex flex-col gap-5">
                <legend class="mb-1 flex items-center gap-2 text-headline-sm text-on-surface"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-secondary text-label-md text-on-secondary">1</span> Your shop</legend>

                <div class="flex flex-col gap-1.5">
                    <label for="shop_name" class="text-label-md text-on-surface">Shop name</label>
                    <input type="text" name="shop_name" id="shop_name" value="{{ old('shop_name') }}" required maxlength="150" placeholder="e.g. Angkor Moto Rental" class="{{ $field }}{{ $err('shop_name') }}">
                    @error('shop_name') <p class="text-body-sm text-error">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="flex flex-col gap-1.5">
                        <label for="city" class="text-label-md text-on-surface">City</label>
                        <input type="text" name="city" id="city" value="{{ old('city', 'Siem Reap') }}" required maxlength="100" list="cityList" autocomplete="off" class="{{ $field }}{{ $err('city') }}">
                        <datalist id="cityList">@foreach ($cities as $c)<option value="{{ $c }}">@endforeach</datalist>
                        @error('city') <p class="text-body-sm text-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="shop_phone" class="text-label-md text-on-surface">Shop phone <span class="font-normal text-on-surface-variant">(optional)</span></label>
                        <input type="tel" name="shop_phone" id="shop_phone" value="{{ old('shop_phone') }}" maxlength="20" placeholder="Same as your phone if empty" class="{{ $field }}{{ $err('shop_phone') }}">
                        @error('shop_phone') <p class="text-body-sm text-error">{{ $message }}</p> @else <p class="text-body-sm text-on-surface-variant">Customers can call this number.</p> @enderror
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="address" class="text-label-md text-on-surface">Address <span class="font-normal text-on-surface-variant">(optional)</span></label>
                    <input type="text" name="address" id="address" value="{{ old('address') }}" maxlength="255" placeholder="Street, area or landmark" class="{{ $field }}{{ $err('address') }}">
                    @error('address') <p class="text-body-sm text-error">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="description" class="text-label-md text-on-surface">About your shop <span class="font-normal text-on-surface-variant">(optional)</span></label>
                    <textarea name="description" id="description" rows="3" maxlength="1000" placeholder="What do you rent, and what makes your shop a good choice?" class="{{ $area }}{{ $err('description') }}">{{ old('description') }}</textarea>
                    @error('description') <p class="text-body-sm text-error">{{ $message }}</p> @enderror
                </div>
            </fieldset>

            {{-- Owner --}}
            <fieldset class="flex flex-col gap-5">
                <legend class="mb-1 flex items-center gap-2 text-headline-sm text-on-surface"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-secondary text-label-md text-on-secondary">2</span> Your login</legend>

                <div class="flex flex-col gap-1.5">
                    <label for="name" class="text-label-md text-on-surface">Your full name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required maxlength="100" autocomplete="name" class="{{ $field }}{{ $err('name') }}">
                    @error('name') <p class="text-body-sm text-error">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="flex flex-col gap-1.5">
                        <label for="phone" class="text-label-md text-on-surface">Your phone number</label>
                        <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" required maxlength="25" autocomplete="tel" placeholder="e.g. 012 345 678" class="{{ $field }}{{ $err('phone') }}">
                        @error('phone') <p class="text-body-sm text-error">{{ $message }}</p> @else <p class="text-body-sm text-on-surface-variant">You sign in with this number.</p> @enderror
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="email" class="text-label-md text-on-surface">Email <span class="font-normal text-on-surface-variant">(optional)</span></label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" class="{{ $field }}{{ $err('email') }}">
                        @error('email') <p class="text-body-sm text-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="password" class="text-label-md text-on-surface">Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required minlength="8" autocomplete="new-password" class="{{ $field }}{{ $err('password') }} pr-12">
                        <button type="button" id="togglePw" aria-label="Show password" aria-pressed="false" class="absolute right-2 top-2 flex h-8 w-8 items-center justify-center rounded-lg text-on-surface-variant hover:bg-surface-container">
                            <span class="material-symbols-outlined text-xl">visibility</span>
                        </button>
                    </div>
                    @error('password') <p class="text-body-sm text-error">{{ $message }}</p> @else <p class="text-body-sm text-on-surface-variant">At least 8 characters. There is no password reset yet, so keep it safe.</p> @enderror
                </div>
            </fieldset>

            <label class="flex items-start gap-3 text-body-md text-on-surface-variant">
                <input type="checkbox" name="terms" value="1" @checked(old('terms')) class="mt-1 h-4 w-4 accent-secondary">
                <span>I own, or am allowed to rent out, the vehicles I will list, and I will honour confirmed bookings.</span>
            </label>
            @error('terms') <p class="-mt-4 text-body-sm text-error">{{ $message }}</p> @enderror

            <button type="submit" id="submitBtn" class="flex h-14 w-full items-center justify-center gap-2 rounded-xl bg-secondary text-headline-sm text-on-secondary shadow-md transition-all hover:bg-on-secondary-container disabled:opacity-60">
                <span>Create my shop</span><span class="material-symbols-outlined text-lg">arrow_forward</span>
            </button>

            <p class="text-center text-body-md text-on-surface-variant">
                Already have a shop?
                <a href="{{ route('portal.login') }}" class="font-semibold text-secondary hover:underline">Partner sign in</a>
            </p>
        </form>

        <aside class="relative hidden overflow-hidden rounded-3xl bg-primary-container p-8 text-on-primary lg:col-span-2 lg:flex lg:flex-col lg:justify-between lg:gap-8 xl:p-10">
            <div class="pointer-events-none absolute -bottom-20 -right-20 h-72 w-72 rounded-full bg-secondary/25 blur-3xl"></div>
            <div class="relative z-10 flex flex-col gap-3">
                <h2 class="text-display-lg text-white">Put your fleet in front of travellers.</h2>
                <p class="text-body-lg text-on-primary-container">Everything you need to take bookings, in one simple dashboard.</p>
            </div>
            <ul class="relative z-10 flex flex-col gap-4">
                @foreach ([
                    ['two_wheeler', 'List vehicles with photos', 'Set your own daily price, deposit and status.'],
                    ['inbox', 'Confirm booking requests', 'See every request, confirm or reject, and track pickups and returns.'],
                    ['date_range', 'Control your calendar', 'See booked days at a glance and block days for maintenance.'],
                    ['payments', 'Customers pay at pickup', 'No online payments to manage. You collect from the customer.'],
                ] as [$icon, $title, $text])
                    <li class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-secondary-fixed"><span class="material-symbols-outlined text-xl">{{ $icon }}</span></span>
                        <span class="flex flex-col"><span class="text-label-lg text-white">{{ $title }}</span><span class="text-body-sm text-on-primary-container">{{ $text }}</span></span>
                    </li>
                @endforeach
            </ul>
        </aside>
    </div>
</div>

<script>
(function () {
    var pw = document.getElementById('password'), t = document.getElementById('togglePw');
    t.addEventListener('click', function () {
        var show = pw.type === 'password';
        pw.type = show ? 'text' : 'password';
        t.setAttribute('aria-pressed', show ? 'true' : 'false');
        t.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        t.firstElementChild.textContent = show ? 'visibility_off' : 'visibility';
    });
    document.querySelector('form').addEventListener('submit', function () {
        var b = document.getElementById('submitBtn'); setTimeout(function () { b.disabled = true; }, 0);
    });
})();
</script>
@endsection