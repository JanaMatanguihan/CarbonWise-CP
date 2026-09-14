<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CarbonRecord;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

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

            $records = $records->map(function ($record) {
                return $record->toArray();
            })->values();

            // The TFT model uses a 90-day history window.
            // Pad shorter histories so the existing model can still run.
            if ($records->count() === 0) {
                return response()->json([
                    'message' => 'At least one carbon record is required for forecasting.'
                ], 422);
            }

            if ($records->count() < 90) {
                $needed = 90 - $records->count();
                $firstRecord = $records->first();
                $firstDate = Carbon::parse($firstRecord['record_date']);
                $paddedRecords = collect();
                for ($i = $needed; $i >= 1; $i--) {
                    $copy = $firstRecord;
                    $copy['record_date'] = $firstDate->copy()->subDays($i)->toDateString();
                    $paddedRecords->push($copy);
                }
                $records = $paddedRecords->concat($records)->values();
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