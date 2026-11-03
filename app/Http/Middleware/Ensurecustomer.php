<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pages for signed-in customers (My Bookings). Run after the "auth" middleware.
 * A business owner who wanders in is sent back to the business portal.
 */
class EnsureCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->role === 'business_admin') {
            return redirect()->route('portal.dashboard');
        }

        abort_unless($user && $user->role === 'customer', 403);

        if (! $user->is_active) {
            \Illuminate\Support\Facades\Auth::logout();

            return redirect()->route('login');
        }

        return $next($request);
    }
}