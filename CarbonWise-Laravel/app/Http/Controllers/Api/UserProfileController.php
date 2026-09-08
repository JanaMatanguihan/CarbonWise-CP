<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserProfileController extends Controller
{
    public function show(Request $request)
    {
        Log::info('USER PROFILE START');

        $start = microtime(true);

        $user = $request->user();

        if (!$user) {
            Log::warning('USER PROFILE NO USER');

            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        Log::info('USER PROFILE USER FOUND', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        $response = response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'department' => $user->department,
                'sr_code' => $user->sr_code,
                'campus' => $user->campus,
                'year_level' => $user->year_level,
            ],
        ]);

        $time = microtime(true) - $start;

        Log::info('USER PROFILE FINISHED', [
            'time' => $time,
        ]);

        return $response;
    }
}