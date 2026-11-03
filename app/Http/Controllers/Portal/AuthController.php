<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check() && Auth::user()->role === 'business_admin') {
            return redirect()->route('portal.dashboard');
        }

        return view('portal.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        // The same number can be typed several ways; try each form it may be stored in.
        $ok = false;
        $forms = array_merge(
            app(\App\Services\BookingService::class)->phoneVariants($credentials['phone']),
            [trim($credentials['phone'])],
        );

        foreach (array_unique($forms) as $phone) {
            if (Auth::attempt(['phone' => $phone, 'password' => $credentials['password']])) {
                $ok = true;
                break;
            }
        }

        if (! $ok) {
            throw ValidationException::withMessages([
                'phone' => 'The phone number or password is incorrect.',
            ]);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->role === 'business_admin' && ! $user->is_active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'phone' => 'This account has been suspended. Please contact AngkorRide.',
            ]);
        }

        if ($user->role !== 'business_admin' || !$user->businesses()->exists()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'phone' => 'This account is not linked to a business.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->route('portal.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }
}