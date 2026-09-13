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

        if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return response()->view('auth.verified-success', ['message' => 'Invalid verification link.'], 403);
        }

        if (!$request->hasValidSignature()) {
            return response()->view('auth.verified-success', ['message' => 'This verification link is invalid or has expired.'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return view('auth.verified-success');
        }

        $user->markEmailAsVerified();

        return view('auth.verified-success');
    }
}