<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CarbonScoreController extends Controller
{
    public function today(Request $request)
    {
        $user = $request->user();

        $records = $user->carbonRecords()
            ->whereDate('record_date', Carbon::today())
            ->get();

        $total = $records->sum('total_emission');

        $score = 100 - $total;

        if ($score < 0) {
            $score = 0;
        }

        if ($score > 100) {
            $score = 100;
        }

        return response()->json([
            'score' => $score,
            'total_emission' => $total,
        ]);
    }
}