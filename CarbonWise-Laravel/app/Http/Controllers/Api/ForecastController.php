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
            $records = CarbonRecord::where('user_id', auth()->id)
                ->orderBy('record_date')
                ->get([
                    'record_date',
                    'transportation',
                    'electricity',
                    'food',
                    'total_emission',
                ]);

            if ($records->count() < 90) {
                return response()->json([
                    'message' => 'At least 90 carbon records are required for forecasting.'
                ], 422);
            }

            $response = Http::timeout(120)->post(
                env('TFT_API_URL') . '/forecast',
                [
                    'records' => $records->toArray()
                ]
            );

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'message' => 'Unable to generate forecast from TFT service.',
                'details' => $response->json()
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'TFT forecasting service is unavailable.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}