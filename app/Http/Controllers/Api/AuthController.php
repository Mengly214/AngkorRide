<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register
    |--------------------------------------------------------------------------
    */

    public function register(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:150',
            'email' => 'nullable|email|max:255|unique:users,email',
            'phone' => 'required|string|max:20|unique:users,phone',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'],
            'password_hash' => Hash::make($validated['password']),
            'role' => 'customer',
            'is_verified' => false,
            'is_active' => true,
        ]);

        $token = $user->createToken('android-app')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Login
    |--------------------------------------------------------------------------
    */

    public function login(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('phone', $validated['phone'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password_hash)) {
            throw ValidationException::withMessages([
                'phone' => ['The phone number or password is incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Your account is inactive.',
            ], 403);
        }

        $token = $user->createToken('android-app')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }
}