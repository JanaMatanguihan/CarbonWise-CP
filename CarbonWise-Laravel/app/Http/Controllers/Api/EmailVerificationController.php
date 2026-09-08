<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        // Check that the hash matches the user's email
        if (!hash_equals(
            sha1($user->getEmailForVerification()),
            $hash
        )) {
            return response()->json([
                'message' => 'Invalid verification link.'
            ], 403);
        }

        // Check the signed URL
        if (!$request->hasValidSignature()) {
            return response()->json([
                'message' => 'This verification link is invalid or has expired.'
            ], 403);
        }

        // Already verified
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email is already verified.'
            ]);
        }

        // THIS is what updates email_verified_at
        $user->markEmailAsVerified();

        return response()->json([
            'message' => 'Email verified successfully!'
        ]);
    }
}