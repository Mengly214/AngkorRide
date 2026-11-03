<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * A rental shop signs itself up: one form creates the owner login
 * (role business_admin) and the shop, then signs the owner in.
 */
class RegisterController extends Controller
{
    /** Suggestions for the city field (any other city can still be typed). */
    public const CITIES = ['Siem Reap', 'Phnom Penh', 'Kampot', 'Kep', 'Battambang', 'Sihanoukville', 'Kratie', 'Mondulkiri'];

    public function __construct(private BookingService $bookings)
    {
    }

    public function show(Request $request)
    {
        $user = $request->user();

        if ($user && $user->role === 'business_admin' && $user->businesses()->exists()) {
            return redirect()->route('portal.dashboard');
        }

        return view('portal.register', ['cities' => self::CITIES]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['required', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:200'],
            'shop_name' => ['required', 'string', 'min:2', 'max:150'],
            'city' => ['required', 'string', 'min:2', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'shop_phone' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:1000'],
            'terms' => ['accepted'],
        ], [
            'name.required' => 'Please enter your name.',
            'phone.required' => 'Please enter your phone number.',
            'password.min' => 'Your password needs at least 8 characters.',
            'shop_name.required' => 'Please enter your shop name.',
            'city.required' => 'Please tell us which city your shop is in.',
            'terms.accepted' => 'Please confirm you may rent out the vehicles you list.',
        ]);

        $phone = $this->bookings->normalizePhone($data['phone']);
        if ($phone === null) {
            throw ValidationException::withMessages(['phone' => 'Please enter a valid phone number.']);
        }

        $shopPhone = filled($data['shop_phone'] ?? null) ? trim($data['shop_phone']) : $phone;
        if ($this->bookings->normalizePhone($shopPhone) === null) {
            throw ValidationException::withMessages(['shop_phone' => 'Please enter a valid shop phone number.']);
        }

        if (User::query()->whereIn('phone', $this->bookings->phoneVariants($phone))->exists()) {
            throw ValidationException::withMessages([
                'phone' => 'An account with this phone number already exists. Please sign in, or use another number.',
            ]);
        }

        if (filled($data['email'] ?? null) && User::query()->where('email', $data['email'])->exists()) {
            throw ValidationException::withMessages(['email' => 'An account with this email already exists.']);
        }

        $user = DB::transaction(function () use ($data, $phone, $shopPhone) {
            $owner = User::create([
                'full_name' => trim(strip_tags($data['name'])),
                'phone' => $phone,
                'email' => filled($data['email'] ?? null) ? $data['email'] : null,
                'role' => 'business_admin',
                'password_hash' => Hash::make($data['password']),
                'is_active' => true,
            ]);

            Business::create([
                'owner_user_id' => $owner->id,
                'name' => trim(strip_tags($data['shop_name'])),
                'city' => $this->cityName($data['city']),
                'address' => filled($data['address'] ?? null) ? trim(strip_tags($data['address'])) : null,
                'phone' => $shopPhone,
                'description' => filled($data['description'] ?? null) ? trim(strip_tags($data['description'])) : null,
                'is_active' => true,
                // AngkorRide marks a shop verified after checking it.
                'is_verified' => false,
            ]);

            return $owner;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('portal.vehicles.create')
            ->with('status', 'Welcome to AngkorRide! Your shop is ready. Add your first vehicle to start getting bookings.');
    }

    /** "siem reap" becomes "Siem Reap" so destination cards group properly. */
    private function cityName(string $city): string
    {
        $city = trim(strip_tags($city));

        foreach (self::CITIES as $known) {
            if (mb_strtolower($known) === mb_strtolower($city)) {
                return $known;
            }
        }

        return mb_convert_case($city, MB_CASE_TITLE);
    }
}