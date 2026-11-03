@extends('portal.layout')

@section('title', 'Settings')

@php
    $field = 'w-full h-11 rounded-xl border border-transparent bg-surface-container-low px-3.5 text-body-md text-on-surface focus:border-secondary focus:bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-secondary/30';
    $err = fn ($k) => $errors->has($k) ? ' ring-2 ring-error/50' : '';
@endphp

@section('content')
<div class="mx-auto flex w-full max-w-3xl flex-col gap-space-lg">

    {{-- Account --}}
    <form method="POST" action="{{ route('portal.settings.account') }}" class="flex flex-col gap-5 rounded-2xl bg-surface-container-lowest p-5 shadow-sm sm:p-6">
        @csrf
        @method('PUT')
        <div>
            <h2 class="text-headline-sm text-on-surface">Your account</h2>
            <p class="mt-1 text-body-md text-on-surface-variant">The name and phone number you sign in with.</p>
        </div>

        <div class="flex flex-col gap-1.5">
            <label for="full_name" class="text-label-md text-on-surface">Full name</label>
            <input type="text" id="full_name" name="full_name" value="{{ old('full_name', $owner->full_name) }}" required maxlength="100" class="{{ $field }}{{ $err('full_name') }}">
            @error('full_name') <p class="text-body-sm text-error">{{ $message }}</p> @enderror
        </div>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="flex flex-col gap-1.5">
                <label for="phone" class="text-label-md text-on-surface">Phone number</label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone', $owner->phone) }}" required maxlength="25" class="{{ $field }}{{ $err('phone') }}">
                @error('phone') <p class="text-body-sm text-error">{{ $message }}</p> @enderror
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="email" class="text-label-md text-on-surface">Email <span class="font-normal text-on-surface-variant">(optional)</span></label>
                <input type="email" id="email" name="email" value="{{ old('email', $owner->email) }}" maxlength="255" class="{{ $field }}{{ $err('email') }}">
                @error('email') <p class="text-body-sm text-error">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="flex justify-end">
            <button type="submit" class="rounded-xl bg-secondary px-5 py-2.5 text-label-lg text-on-secondary shadow-sm transition-colors hover:bg-on-secondary-container">Save account</button>
        </div>
    </form>

    {{-- Password --}}
    <form method="POST" action="{{ route('portal.settings.password') }}" class="flex flex-col gap-5 rounded-2xl bg-surface-container-lowest p-5 shadow-sm sm:p-6">
        @csrf
        @method('PUT')
        <div>
            <h2 class="text-headline-sm text-on-surface">Change password</h2>
            <p class="mt-1 text-body-md text-on-surface-variant">Use at least 8 characters. There is no password reset yet, so keep your new password safe.</p>
        </div>

        <div class="flex flex-col gap-1.5">
            <label for="current_password" class="text-label-md text-on-surface">Current password</label>
            <input type="password" id="current_password" name="current_password" required autocomplete="current-password" class="{{ $field }}{{ $err('current_password') }}">
            @error('current_password') <p class="text-body-sm text-error">{{ $message }}</p> @enderror
        </div>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="flex flex-col gap-1.5">
                <label for="password" class="text-label-md text-on-surface">New password</label>
                <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password" class="{{ $field }}{{ $err('password') }}">
                @error('password') <p class="text-body-sm text-error">{{ $message }}</p> @enderror
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="password_confirmation" class="text-label-md text-on-surface">Repeat new password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password" class="{{ $field }}">
            </div>
        </div>
        <div class="flex justify-end">
            <button type="submit" class="rounded-xl bg-primary px-5 py-2.5 text-label-lg text-on-primary shadow-sm transition-colors hover:bg-on-surface">Change password</button>
        </div>
    </form>
</div>
@endsection