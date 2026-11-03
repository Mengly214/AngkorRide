<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** The shop's public details: what customers see on the shop page and vehicle pages. */
class ProfileController extends Controller
{
    public function __construct(private BookingService $bookings)
    {
    }

    public function edit(Request $request)
    {
        return view('portal.profile.edit', [
            'business' => $this->business($request),
            'cities' => RegisterController::CITIES,
        ]);
    }

    public function update(Request $request)
    {
        $business = $this->business($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'city' => ['required', 'string', 'min:2', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'registration_no' => ['nullable', 'string', 'max:100'],
            'opening_time' => ['nullable', 'date_format:H:i'],
            'closing_time' => ['nullable', 'date_format:H:i'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ], [
            'name.required' => 'Please enter your shop name.',
            'city.required' => 'Please enter the city.',
            'logo.image' => 'The logo must be an image (JPG, PNG or WebP).',
            'logo.max' => 'The logo is too large. Please use a file under 3 MB.',
            'opening_time.date_format' => 'Please enter a valid opening time.',
            'closing_time.date_format' => 'Please enter a valid closing time.',
        ]);

        if (filled($data['phone'] ?? null) && $this->bookings->normalizePhone($data['phone']) === null) {
            throw ValidationException::withMessages(['phone' => 'Please enter a valid phone number.']);
        }

        if (filled($data['opening_time'] ?? null) xor filled($data['closing_time'] ?? null)) {
            throw ValidationException::withMessages(['opening_time' => 'Please fill in both opening and closing time, or leave both empty.']);
        }

        if (filled($data['opening_time'] ?? null) && $data['closing_time'] <= $data['opening_time']) {
            throw ValidationException::withMessages(['closing_time' => 'Closing time must be after opening time.']);
        }

        $clean = fn (?string $v) => filled($v) ? trim(strip_tags($v)) : null;

        $business->fill([
            'name' => trim(strip_tags($data['name'])),
            'description' => $clean($data['description'] ?? null),
            'city' => trim(strip_tags($data['city'])),
            'address' => $clean($data['address'] ?? null),
            'phone' => $clean($data['phone'] ?? null),
            'email' => $data['email'] ?? null,
            'registration_no' => $clean($data['registration_no'] ?? null),
            'opening_time' => $data['opening_time'] ?? null,
            'closing_time' => $data['closing_time'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($request->hasFile('logo')) {
            $business->deleteLogoFile();
            $business->logo_url = '/storage/' . $request->file('logo')->storePublicly('businesses', 'public');
        } elseif ($request->boolean('remove_logo')) {
            $business->deleteLogoFile();
            $business->logo_url = null;
        }

        $business->save();

        return redirect()->route('portal.profile.edit')->with('status', 'Shop profile saved.');
    }

    private function business(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return $user->businesses()->firstOrFail();
    }
}