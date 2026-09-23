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
        return view('auth.verified-success', [
            'message' => 'Invalid verification link.',
        ]);
    }

    if (!$request->hasValidSignature()) {
        return view('auth.verified-success', [
            'message' => 'This verification link is invalid or has expired.',
        ]);
    }

    if (!$user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
    }

    return view('auth.verified-success', [
    'message'  => 'Your email has been verified successfully!',
    'deepLink' => 'carbonwise://open?status=success&source=email_verify',
]);
}
}