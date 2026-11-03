@extends('layouts.customer')

@section('title', 'Sign in')

@php
    $field = 'w-full h-12 rounded-xl border border-transparent bg-surface-container-low px-4 text-body-md text-on-surface focus:border-secondary focus:bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-secondary/30';
    $bad = $errors->any() ? ' ring-2 ring-error/50' : '';
@endphp

@section('content')
<div class="relative mx-auto w-full max-w-6xl px-6 py-10 lg:px-12 lg:py-14">
    <div class="pointer-events-none absolute -top-6 left-1/2 -z-10 h-64 w-3/4 -translate-x-1/2 rounded-full bg-gradient-to-b from-secondary/10 via-secondary-container/20 to-transparent blur-3xl"></div>

    <div class="grid grid-cols-1 items-stretch gap-8 lg:grid-cols-2">
        <form method="POST" action="{{ route('login.submit') }}" class="flex flex-col gap-6 rounded-3xl border border-surface-container bg-surface-container-lowest p-6 shadow-lg sm:p-8 lg:p-10">
            @csrf
            @if ($next) <input type="hidden" name="next" value="{{ $next }}"> @endif

            <div class="flex flex-col gap-2">
                <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-secondary-container/60 px-3 py-1 text-label-sm uppercase tracking-wide text-on-secondary-container">
                    <span class="material-symbols-outlined text-sm">login</span> Welcome back
                </span>
                <h1 class="text-display-lg tracking-tight text-on-surface">Sign in</h1>
                <p class="text-body-md text-on-surface-variant">See your bookings, check their status and cancel them.</p>
            </div>

            @if ($errors->any())
                <div role="alert" class="flex items-start gap-2 rounded-xl bg-error-container px-4 py-3 text-body-md text-on-error-container">
                    <span class="material-symbols-outlined text-xl">error</span><span>{{ $errors->first() }}</span>
                </div>
            @endif

            <div class="flex flex-col gap-1.5">
                <label for="phone" class="text-label-md text-on-surface">Phone number</label>
                <div class="relative">
                    <span class="material-symbols-outlined pointer-events-none absolute left-3.5 top-3 text-xl text-on-surface-variant">call</span>
                    <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" required maxlength="25" autocomplete="tel" autofocus placeholder="e.g. 012 345 678" class="{{ $field }}{{ $bad }} pl-11">
                </div>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="password" class="text-label-md text-on-surface">Password</label>
                <div class="relative">
                    <span class="material-symbols-outlined pointer-events-none absolute left-3.5 top-3 text-xl text-on-surface-variant">lock</span>
                    <input type="password" name="password" id="password" required autocomplete="current-password" class="{{ $field }}{{ $bad }} pl-11 pr-12">
                    <button type="button" id="togglePw" aria-label="Show password" aria-pressed="false" class="absolute right-2 top-2 flex h-8 w-8 items-center justify-center rounded-lg text-on-surface-variant hover:bg-surface-container">
                        <span class="material-symbols-outlined text-xl">visibility</span>
                    </button>
                </div>
            </div>

            <button type="submit" id="submitBtn" class="flex h-14 w-full items-center justify-center gap-2 rounded-xl bg-primary text-headline-sm text-on-primary shadow-md transition-all hover:bg-on-surface disabled:opacity-60">
                <span>Sign in</span><span class="material-symbols-outlined text-lg">arrow_forward</span>
            </button>

            <p class="text-center text-body-md text-on-surface-variant">
                New to AngkorRide?
                <a href="{{ route('register', $next ? ['next' => $next] : []) }}" class="font-semibold text-secondary hover:underline">Create an account</a>
            </p>
            <p class="text-center text-body-sm text-on-surface-variant">Forgot your password? Call AngkorRide support on <a href="tel:+85512345678" class="font-semibold text-on-surface hover:underline">+855 12 345 678</a>.</p>
        </form>

        @include('customer.auth._aside')
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