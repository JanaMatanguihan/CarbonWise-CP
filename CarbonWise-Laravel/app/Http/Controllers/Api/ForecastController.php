<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CarbonRecord;
use Illuminate\Support\Facades\Http;

class ForecastController extends Controller
{
    public function tft30DayForecast()
    {
        try {
            $records = CarbonRecord::where('user_id', request()->user()->id)
                ->orderBy('record_date')
                ->get([
                    'record_date',
                    'transportation',
                    'electricity',
                    'food',
                    'total_emission',
                ]);

            $recordCount = $records->count();

            // NEW USER / NO CARBON RECORDS
            if ($recordCount === 0) {
                return response()->json([
                    'status' => 'no_data',
                    'message' => 'No carbon records available yet.',
                    'records_available' => 0,
                    'records_required' => 90,
                    'forecast' => [],
                ], 200);
            }

            // NOT ENOUGH HISTORY FOR TFT
            if ($recordCount < 90) {
                return response()->json([
                    'status' => 'insufficient_data',
                    'message' => 'More carbon activity history is needed before a 30-day forecast can be generated.',
                    'records_available' => $recordCount,
                    'records_required' => 90,
                    'forecast' => [],
                ], 200);
            }

            // ENOUGH DATA -> CALL TFT SERVICE
            $response = Http::timeout(120)->post(
                env('TFT_API_URL') . '/forecast',
                [
                    'records' => $records->toArray(),
                ]
            );

            if ($response->successful()) {
                return response()->json([
                    'status' => 'success',
                    ...$response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to generate forecast from TFT service.',
                'details' => $response->json(),
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'TFT forecasting service is unavailable.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}