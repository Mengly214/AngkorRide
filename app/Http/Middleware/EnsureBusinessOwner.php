<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user || $user->role !== 'business_admin') {
            return redirect()->route('portal.login');
        }

        if (! $user->is_active) {
            Auth::logout();

            return redirect()
                ->route('portal.login')
                ->withErrors(['phone' => 'This account has been suspended. Please contact AngkorRide.']);
        }

        $business = $user->businesses()->first();

        if (!$business) {
            Auth::logout();

            return redirect()
                ->route('portal.login')
                ->withErrors(['phone' => 'No business is linked to this account yet.']);
        }

        // Keep the owner's business available to controllers...
        $request->attributes->set('business', $business);

        // Requests nobody answered in time expire before we count what is waiting.
        app(\App\Services\BookingService::class)->expireStale();

        // ...and to every portal view (sidebar header, pending-bookings badge).
        View::share('business', $business);
        View::share(
            'pendingBookingsCount',
            $business->bookings()->where('status', 'pending')->count()
        );

        return $next($request);
    }
}