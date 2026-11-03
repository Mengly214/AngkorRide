<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check() && Auth::user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        $forms = array_merge(
            app(BookingService::class)->phoneVariants($credentials['phone']),
            [trim($credentials['phone'])],
        );

        $ok = false;
        foreach (array_unique($forms) as $phone) {
            if (Auth::attempt(['phone' => $phone, 'password' => $credentials['password']])) {
                $ok = true;
                break;
            }
        }

        // One message for every failure, so the page never reveals which numbers are admins.
        $fail = fn () => ValidationException::withMessages(['phone' => 'The phone number or password is incorrect.']);

        if (! $ok) {
            throw $fail();
        }

        $user = Auth::user();

        if ($user->role !== 'admin' || ! $user->is_active) {
            Auth::logout();

            throw $fail();
        }

        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}