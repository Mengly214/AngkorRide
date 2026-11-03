<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'AngkorRide') — AngkorRide</title>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-explore bg-surface text-body-md text-on-surface antialiased">

@include('partials.site-header', ['active' => $active ?? null])

<main class="w-full pt-20">
    @if (session('status'))
        <div role="status" class="mx-auto mt-4 w-full max-w-3xl px-6">
            <div class="rounded-xl bg-tertiary-fixed px-4 py-3 text-label-lg text-on-tertiary-fixed">{{ session('status') }}</div>
        </div>
    @endif
    @yield('content')
</main>

@include('partials.site-footer')

</body>
</html>