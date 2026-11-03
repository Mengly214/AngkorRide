<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in — AngkorRide Business Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-primary-100/40 text-primary-900 antialiased min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm bg-white rounded-ar-lg border border-primary-100 shadow-sm p-6">
        <p class="text-lg font-bold text-primary-700">AngkorRide</p>
        <p class="text-sm text-gray-500 mb-6">Business Portal login</p>

        @if ($errors->any())
            <div class="mb-4 rounded-ar-md bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('portal.login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone number</label>
                <input type="text" name="phone" value="{{ old('phone') }}" required
                       class="w-full rounded-ar-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required
                       class="w-full rounded-ar-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-primary-500">
            </div>
            <button type="submit"
                    class="w-full rounded-ar-md bg-primary-500 text-white font-semibold py-2.5 text-sm hover:bg-primary-700">
                Log in
            </button>
        </form>

        <p class="mt-5 text-center text-sm text-gray-500">
            New shop?
            <a href="{{ route('portal.register') }}" class="font-semibold text-primary-700 hover:underline">Register your shop</a>
        </p>
        <p class="mt-2 text-center text-xs text-gray-400"><a href="{{ route('home') }}" class="hover:underline">&larr; Back to AngkorRide</a></p>
    </div>
</body>
</html>