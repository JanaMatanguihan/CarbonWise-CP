<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CarbonRecord;

class CarbonPatternController extends Controller
{
    public function getPatterns(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // Get the logged-in user's carbon records
        $records = CarbonRecord::where('email', $user->email)
            ->orderBy('record_date', 'desc')
            ->get();

        if ($records->isEmpty()) {
            return response()->json([
                'weekday_pattern' => 'No emission records yet.',
                'highest_impact_pattern' => 'No emission records yet.',
                'insight_pattern' => 'Start tracking your emissions!',
            ]);
        }

        $weekdayTotal = 0;
        $weekendTotal = 0;

        $transportTotal = 0;
        $electricityTotal = 0;
        $foodTotal = 0;

        foreach ($records as $record) {
            $date = \Carbon\Carbon::parse($record->record_date);

            $transport = (float) ($record->transportation ?? 0);
            $electricity = (float) ($record->electricity ?? 0);
            $food = (float) ($record->food ?? 0);

            $transportTotal += $transport;
            $electricityTotal += $electricity;
            $foodTotal += $food;

            $total = $transport + $electricity + $food;

            if ($date->isWeekday()) {
                $weekdayTotal += $total;
            } else {
                $weekendTotal += $total;
            }
        }

        // ============================================================
        // HIGHEST IMPACT ACTIVITY
        // ============================================================

        $highestActivity = 'Transportation';
        $highestValue = $transportTotal;

        if ($electricityTotal > $highestValue) {
            $highestActivity = 'Electricity usage';
            $highestValue = $electricityTotal;
        }

        if ($foodTotal > $highestValue) {
            $highestActivity = 'Food consumption';
            $highestValue = $foodTotal;
        }

        // ============================================================
        // TOTAL EMISSIONS
        // ============================================================

        $totalEmission =
            $transportTotal +
            $electricityTotal +
            $foodTotal;

        $percentage = $totalEmission == 0
            ? 0
            : ($highestValue / $totalEmission) * 100;

        // ============================================================
        // WEEKDAY / WEEKEND PATTERN
        // ============================================================

        if ($weekdayTotal > $weekendTotal && $weekendTotal > 0) {

            $diff = round(
                (($weekdayTotal - $weekendTotal) / $weekendTotal) * 100
            );

            $weekdayPattern =
                "You emit about {$diff}% more CO₂ on weekdays than weekends.";

        } elseif ($weekendTotal > $weekdayTotal && $weekdayTotal > 0) {

            $diff = round(
                (($weekendTotal - $weekdayTotal) / $weekdayTotal) * 100
            );

            $weekdayPattern =
                "You emit about {$diff}% more CO₂ on weekends than weekdays.";

        } else {

            $weekdayPattern =
                "Your weekday and weekend emissions are nearly the same.";
        }

        // ============================================================
        // RETURN RESULT
        // ============================================================

        return response()->json([
            'weekday_pattern' => $weekdayPattern,

            'highest_impact_pattern' =>
                "Your highest impact activity is {$highestActivity}.",

            'insight_pattern' =>
                "{$highestActivity} contributes " .
                number_format($percentage, 0) .
                "% of your total emissions.",

            // Optional: useful if you want the Flutter app
            // to display these numbers later.
            'totals' => [
                'weekday' => $weekdayTotal,
                'weekend' => $weekendTotal,
                'transportation' => $transportTotal,
                'electricity' => $electricityTotal,
                'food' => $foodTotal,
                'total_emission' => $totalEmission,
            ],
        ]);
    }
}