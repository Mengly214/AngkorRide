<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Log in — AngkorRide Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-surface px-4 font-explore text-on-surface antialiased">
    <div class="w-full max-w-sm rounded-2xl bg-surface-container-lowest p-7 shadow-sm">
        <p class="text-headline-md text-on-surface">AngkorRide</p>
        <p class="mb-6 text-label-sm uppercase tracking-wider text-secondary">Admin</p>

        @if ($errors->any())
            <div role="alert" class="mb-4 rounded-lg bg-error-container px-4 py-3 text-body-sm text-on-error-container">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-4">
            @csrf
            <div>
                <label for="phone" class="mb-1 block text-label-md text-on-surface-variant">Phone number</label>
                <input id="phone" type="text" name="phone" value="{{ old('phone') }}" required autocomplete="username" class="w-full rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-body-md focus:border-secondary focus:outline-none">
            </div>
            <div>
                <label for="password" class="mb-1 block text-label-md text-on-surface-variant">Password</label>
                <input id="password" type="password" name="password" required autocomplete="current-password" class="w-full rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-body-md focus:border-secondary focus:outline-none">
            </div>
            <button type="submit" class="w-full rounded-lg bg-secondary py-2.5 text-label-lg text-on-secondary hover:bg-on-secondary-container">Log in</button>
        </form>
        <p class="mt-5 text-center text-body-sm text-on-surface-variant">Administrators only.</p>
    </div>
</body>
</html>