<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Register
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],

            'role' => ['required', 'string'],
            'sr_code' => ['nullable', 'string'],
            'campus' => ['nullable', 'string'],
            'year_level' => ['nullable', 'integer'],
            'department' => ['nullable', 'string'],
            'faculty_type' => ['nullable', 'string'],
            'office' => ['nullable', 'string'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => strtolower($validated['role']),
            'sr_code' => $validated['sr_code'] ?? null,
            'campus' => $validated['campus'] ?? null,
            'year_level' => $validated['year_level'] ?? null,
            'department' => $validated['department'] ?? null,
        ]);

        // Send verification email
        $user->sendEmailVerificationNotification();

        $token = $user->createToken('flutter')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful. Please verify your email.',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    // Login
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Don't allow login until email is verified
        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Please verify your email before logging in.',
            ], 403);
        }

        $token = $user->createToken('flutter')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user,
            'token' => $token,
        ]);
    }

    // Get currently authenticated user
    public function user(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    // Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }
}