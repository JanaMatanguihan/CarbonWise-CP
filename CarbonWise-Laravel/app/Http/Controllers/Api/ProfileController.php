<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    // Get the logged-in user's profile
    public function show(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    // Update the logged-in user's profile
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                'unique:users,email,' . $user->id
            ],
            'department' => ['sometimes', 'nullable', 'string', 'max:255'],
            'campus' => ['sometimes', 'nullable', 'string', 'max:255'],
            'year_level' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user->fresh(),
        ]);
    }

    // Change password
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }

    // Upload profile picture
public function uploadProfilePicture(Request $request)
{
    $request->validate([
        'profile_picture' => ['required', 'image', 'max:5120'],
    ]);

    $user = $request->user();

    if ($request->hasFile('profile_picture')) {

        // Delete old profile picture
        if ($user->profile_picture) {
            $oldPath = str_replace('/storage/', '', $user->profile_picture);

            Storage::disk('public')->delete($oldPath);
        }

        // Save new picture
        $path = $request->file('profile_picture')
            ->store('profile-pictures', 'public');

        $url = asset('storage/' . $path);

        $user->update([
            'profile_picture' => $url,
        ]);

        return response()->json([
            'message' => 'Profile picture uploaded successfully.',
            'profile_picture' => $url,
            'user' => $user->fresh(),
        ]);
    }

    return response()->json([
        'message' => 'No profile picture was uploaded.',
    ], 400);
}
}