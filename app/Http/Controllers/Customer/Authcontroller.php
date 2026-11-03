<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Customer accounts: sign up / sign in with a phone number and a password.
 * Browsing vehicles never needs an account; booking and My Bookings do.
 */
class AuthController extends Controller
{
    public function __construct(private BookingService $bookings)
    {
    }

    public function showLogin(Request $request)
    {
        if ($this->isCustomer($request)) {
            return redirect($this->next($request->query('next')) ?? route('bookings.index'));
        }

        return view('customer.auth.login', ['next' => $this->next($request->query('next'))]);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:25'],
            'password' => ['required', 'string', 'max:200'],
        ], [
            'phone.required' => 'Please enter your phone number.',
            'password.required' => 'Please enter your password.',
        ]);

        $phone = $this->bookings->normalizePhone($data['phone']);

        // Same message for every failure so nobody can probe which phones have accounts.
        $fail = ValidationException::withMessages(['phone' => 'The phone number or password is incorrect.']);

        // The same number can be typed several ways (012… or +855 12…): try each stored form.
        $signedIn = false;
        foreach ($this->bookings->phoneVariants($data['phone']) as $form) {
            if (Auth::attempt(['phone' => $form, 'password' => $data['password']])) {
                $signedIn = true;
                break;
            }
        }

        if (! $phone || ! $signedIn) {
            throw $fail;
        }

        if (Auth::user()->role !== 'customer' || ! Auth::user()->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'phone' => 'This is not a customer account. Business owners sign in at the business portal.',
            ]);
        }

        $request->session()->regenerate();

        return redirect($this->next($request->input('next')) ?? route('bookings.index'));
    }

    public function showRegister(Request $request)
    {
        if ($this->isCustomer($request)) {
            return redirect($this->next($request->query('next')) ?? route('bookings.index'));
        }

        return view('customer.auth.register', ['next' => $this->next($request->query('next'))]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['required', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:200'],
        ], [
            'name.required' => 'Please enter your name.',
            'phone.required' => 'Please enter your phone number.',
            'password.min' => 'Your password needs at least 8 characters.',
        ]);

        $user = $this->bookings->registerCustomer(
            trim(strip_tags($data['name'])), $data['phone'], $data['password'], $data['email'] ?? null,
        );

        Auth::login($user);
        $request->session()->regenerate();

        return redirect($this->next($request->input('next')) ?? route('home'))
            ->with('status', 'Welcome to AngkorRide! Your account is ready.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function isCustomer(Request $request): bool
    {
        return $request->user() && $request->user()->role === 'customer';
    }

    /**
     * Where to go after signing in. Only a path on this same site is accepted,
     * so a crafted link can't send people to another website.
     */
    private function next(mixed $value): ?string
    {
        if (! is_string($value) || $value === '' || strlen($value) > 500) {
            return null;
        }

        if (! str_starts_with($value, '/') || str_starts_with($value, '//') || str_contains($value, '\\')) {
            return null;
        }

        return $value;
    }
}