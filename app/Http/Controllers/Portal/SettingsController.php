<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/** The owner's own login: name, phone, email and password. */
class SettingsController extends Controller
{
    public function __construct(private BookingService $bookings)
    {
    }

    public function edit(Request $request)
    {
        return view('portal.settings.edit', ['owner' => $request->user()]);
    }

    public function updateAccount(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $data = $request->validate([
            'full_name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['required', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:255'],
        ], [
            'full_name.required' => 'Please enter your name.',
            'phone.required' => 'Please enter your phone number.',
        ]);

        $phone = $this->bookings->normalizePhone($data['phone']);
        if ($phone === null) {
            throw ValidationException::withMessages(['phone' => 'Please enter a valid phone number.']);
        }

        if (User::query()->whereIn('phone', $this->bookings->phoneVariants($phone))->where('id', '!=', $user->id)->exists()) {
            throw ValidationException::withMessages(['phone' => 'Another account already uses this phone number.']);
        }

        if (filled($data['email'] ?? null) && User::query()->where('email', $data['email'])->where('id', '!=', $user->id)->exists()) {
            throw ValidationException::withMessages(['email' => 'Another account already uses this email.']);
        }

        $user->update([
            'full_name' => trim(strip_tags($data['full_name'])),
            'phone' => $phone,
            'email' => filled($data['email'] ?? null) ? $data['email'] : null,
        ]);

        return redirect()->route('portal.settings.edit')->with('status', 'Account details saved. Use this phone number the next time you sign in.');
    }

    public function updatePassword(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:200', 'confirmed'],
        ], [
            'current_password.required' => 'Please enter your current password.',
            'password.min' => 'Your new password needs at least 8 characters.',
            'password.confirmed' => 'The two new passwords do not match.',
        ]);

        if (! Hash::check($data['current_password'], $user->password_hash)) {
            throw ValidationException::withMessages(['current_password' => 'Your current password is not correct.']);
        }

        $user->forceFill(['password_hash' => Hash::make($data['password'])])->save();
        $request->session()->regenerate();

        return redirect()->route('portal.settings.edit')->with('status', 'Password changed.');
    }
}