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
            return response()->view('auth.verified-success', ['message' => 'Invalid verification link.'], 403);
        }

        // Check the signed URL
        if (!$request->hasValidSignature()) {
            return response()->view('auth.verified-success', ['message' => 'This verification link is invalid or has expired.'], 403);
        }

        // Already verified
        if ($user->hasVerifiedEmail()) {
            return view('auth.verified-success');
        }

        // updates email_verified_at
        $user->markEmailAsVerified();

        // Return web success view instead of JSON so the browser renders the card
        return view('auth.verified-success');
    }
}