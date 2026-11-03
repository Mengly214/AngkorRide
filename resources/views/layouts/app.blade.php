<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'AngkorRide') — Explore Your Way</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-primary-100/40 text-primary-900 antialiased">
    <header class="border-b border-primary-100 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
            <a href="{{ url('/') }}" class="text-lg font-bold text-primary-700">AngkorRide</a>
        </div>
    </header>

    <main>
        @yield('content')
    </main>
</body>
</html>
