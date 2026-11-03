@extends('layouts.customer')

@section('title', 'Create account')

@php
    $field = 'w-full h-12 rounded-xl border border-transparent bg-surface-container-low px-4 text-body-md text-on-surface focus:border-secondary focus:bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-secondary/30';
    $err = fn ($k) => $errors->has($k) ? ' ring-2 ring-error/50' : '';
@endphp

@section('content')
<div class="relative mx-auto w-full max-w-6xl px-6 py-10 lg:px-12 lg:py-14">
    <div class="pointer-events-none absolute -top-6 left-1/2 -z-10 h-64 w-3/4 -translate-x-1/2 rounded-full bg-gradient-to-b from-secondary/10 via-secondary-container/20 to-transparent blur-3xl"></div>

    <div class="grid grid-cols-1 items-stretch gap-8 lg:grid-cols-2">
        <form method="POST" action="{{ route('register.submit') }}" class="flex flex-col gap-5 rounded-3xl border border-surface-container bg-surface-container-lowest p-6 shadow-lg sm:p-8 lg:p-10">
            @csrf
            @if ($next) <input type="hidden" name="next" value="{{ $next }}"> @endif

            <div class="flex flex-col gap-2">
                <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-secondary-container/60 px-3 py-1 text-label-sm uppercase tracking-wide text-on-secondary-container">
                    <span class="material-symbols-outlined text-sm">person_add</span> Free account
                </span>
                <h1 class="text-display-lg tracking-tight text-on-surface">Create account</h1>
                <p class="text-body-md text-on-surface-variant">Browsing is free for everyone. An account lets you book and keep track of your rentals.</p>
            </div>

            @if ($errors->any())
                <div role="alert" class="flex items-start gap-2 rounded-xl bg-error-container px-4 py-3 text-body-md text-on-error-container">
                    <span class="material-symbols-outlined text-xl">error</span><span>Please fix the highlighted fields and try again.</span>
                </div>
            @endif

            <div class="flex flex-col gap-1.5">
                <label for="name" class="text-label-md text-on-surface">Full name</label>
                <div class="relative">
                    <span class="material-symbols-outlined pointer-events-none absolute left-3.5 top-3 text-xl text-on-surface-variant">person</span>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required maxlength="100" autocomplete="name" class="{{ $field }}{{ $err('name') }} pl-11">
                </div>
                @error('name') <p class="text-body-sm text-error">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="flex flex-col gap-1.5">
                    <label for="phone" class="text-label-md text-on-surface">Phone number</label>
                    <div class="relative">
                        <span class="material-symbols-outlined pointer-events-none absolute left-3.5 top-3 text-xl text-on-surface-variant">call</span>
                        <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" required maxlength="25" autocomplete="tel" placeholder="e.g. 012 345 678" class="{{ $field }}{{ $err('phone') }} pl-11">
                    </div>
                    @error('phone') <p class="text-body-sm text-error">{{ $message }}</p> @else <p class="text-body-sm text-on-surface-variant">The shop calls this number to confirm.</p> @enderror
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="email" class="text-label-md text-on-surface">Email <span class="font-normal text-on-surface-variant">(optional)</span></label>
                    <div class="relative">
                        <span class="material-symbols-outlined pointer-events-none absolute left-3.5 top-3 text-xl text-on-surface-variant">mail</span>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" class="{{ $field }}{{ $err('email') }} pl-11">
                    </div>
                    @error('email') <p class="text-body-sm text-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="password" class="text-label-md text-on-surface">Password</label>
                <div class="relative">
                    <span class="material-symbols-outlined pointer-events-none absolute left-3.5 top-3 text-xl text-on-surface-variant">lock</span>
                    <input type="password" name="password" id="password" required minlength="8" autocomplete="new-password" class="{{ $field }}{{ $err('password') }} pl-11 pr-12">
                    <button type="button" id="togglePw" aria-label="Show password" aria-pressed="false" class="absolute right-2 top-2 flex h-8 w-8 items-center justify-center rounded-lg text-on-surface-variant hover:bg-surface-container">
                        <span class="material-symbols-outlined text-xl">visibility</span>
                    </button>
                </div>
                <div class="flex items-center gap-2" aria-hidden="true">
                    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-surface-container-high"><div id="pwBar" class="h-full w-0 rounded-full bg-secondary transition-all"></div></div>
                    <span id="pwHint" class="text-label-sm text-on-surface-variant">8+ characters</span>
                </div>
                @error('password') <p class="text-body-sm text-error">{{ $message }}</p> @enderror
            </div>

            <button type="submit" id="submitBtn" class="flex h-14 w-full items-center justify-center gap-2 rounded-xl bg-primary text-headline-sm text-on-primary shadow-md transition-all hover:bg-on-surface disabled:opacity-60">
                <span>Create account</span><span class="material-symbols-outlined text-lg">arrow_forward</span>
            </button>

            <p class="text-center text-body-md text-on-surface-variant">
                Already have an account?
                <a href="{{ route('login', $next ? ['next' => $next] : []) }}" class="font-semibold text-secondary hover:underline">Sign in</a>
            </p>
        </form>

        @include('customer.auth._aside')
    </div>
</div>

<script>
(function () {
    var pw = document.getElementById('password'), t = document.getElementById('togglePw');
    var bar = document.getElementById('pwBar'), hint = document.getElementById('pwHint');
    t.addEventListener('click', function () {
        var show = pw.type === 'password';
        pw.type = show ? 'text' : 'password';
        t.setAttribute('aria-pressed', show ? 'true' : 'false');
        t.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        t.firstElementChild.textContent = show ? 'visibility_off' : 'visibility';
    });
    pw.addEventListener('input', function () {
        var v = pw.value, score = 0;
        if (v.length >= 8) score++; if (v.length >= 12) score++; if (/[A-Z]/.test(v) && /[a-z]/.test(v)) score++; if (/\d/.test(v) || /[^A-Za-z0-9]/.test(v)) score++;
        var pct = v.length === 0 ? 0 : (v.length < 8 ? 15 : 25 * Math.max(1, score));
        bar.style.width = pct + '%';
        hint.textContent = v.length === 0 ? '8+ characters' : (v.length < 8 ? 'Too short' : (score >= 4 ? 'Strong' : (score >= 3 ? 'Good' : 'OK')));
    });
    document.querySelector('form').addEventListener('submit', function () {
        var b = document.getElementById('submitBtn'); setTimeout(function () { b.disabled = true; }, 0);
    });
})();
</script>
@endsection