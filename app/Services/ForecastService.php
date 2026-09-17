<?php

namespace App\Services;

use App\Models\CarbonRecord;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ForecastService
{
    public function getHistoricalData()
    {
        // Allow the TFT request to finish without PHP stopping it at 60 seconds.
        set_time_limit(0);

        // Get real CarbonWise records from the database.
        // Aggregate all users by record_date for daily emission totals.
        $records = CarbonRecord::select('record_date')
            ->selectRaw('SUM(transportation) AS transportation')
            ->selectRaw('SUM(electricity) AS electricity')
            ->selectRaw('SUM(food) AS food')
            ->selectRaw('SUM(total_emission) AS total_emission')
            ->groupBy('record_date')
            ->orderBy('record_date')
            ->get();

        // Prepare records for the TFT API.
        // Missing calendar dates are filled temporarily for forecasting only.
        // Nothing is inserted into the carbon_records table.
        $forecastRecords = collect();

        if ($records->isNotEmpty()) {

            $recordsByDate = $records->keyBy(
                fn ($record) => $record->record_date->format('Y-m-d')
            );

            $startDate = $records->first()->record_date->copy();
            $endDate = $records->last()->record_date->copy();

            $previousRecord = null;

            for (
                $date = $startDate->copy();
                $date->lte($endDate);
                $date->addDay()
            ) {

                $dateKey = $date->format('Y-m-d');

                if ($recordsByDate->has($dateKey)) {

                    // Use the actual database record.
                    $record = $recordsByDate->get($dateKey);

                    $previousRecord = [
                        'transportation' => (float) $record->transportation,
                        'electricity' => (float) $record->electricity,
                        'food' => (float) $record->food,
                        'total_emission' => (float) $record->total_emission,
                    ];

                    $forecastRecords->push([
                        'record_date' => $dateKey,
                        ...$previousRecord,
                    ]);

                } elseif ($previousRecord !== null) {

                    // Fill a missing calendar date using the previous valid value.
                    // Temporary data used only for forecasting.
                    $forecastRecords->push([
                        'record_date' => $dateKey,
                        ...$previousRecord,
                    ]);
                }
            }
        }

        // Send the prepared historical data to the Railway TFT API.
        $forecast = collect();
        $predictionInterval = null;

        $tftApiUrl = rtrim(
            env('TFT_API_URL', ''),
            '/'
        );

        if (
            $forecastRecords->isNotEmpty() &&
            $tftApiUrl !== ''
        ) {
            try {

                // TFT forecasting can take more than 30 seconds.
                // The PHP execution limit is disabled above.
                $response = Http::timeout(120)
                    ->post(
                        $tftApiUrl . '/forecast',
                        [
                            'records' => $forecastRecords->values()->all(),
                        ]
                    );

                if ($response->successful()) {

                    $forecast = collect(
                        $response->json('forecast', [])
                    );

                    $predictionInterval = $response->json(
                        'prediction_interval'
                    );
                }

            } catch (\Throwable $e) {

                // Keep the forecasting page from crashing if the TFT service is unavailable.
                Log::error(
                    'TFT Forecast API Error: ' . $e->getMessage()
                );
            }
        }

        // Keep only the latest 30 real historical dates for the historical chart.
        $historicalRecords = $records
            ->sortByDesc('record_date')
            ->take(30)
            ->sortBy('record_date')
            ->values();

        // Calculate forecast totals and daily average.
        // The updated TFT API uses the "forecast" field.
        $predictedEmissions = $forecast->sum(
            'forecast'
        );

        $predictedDailyAverage = $forecast->isNotEmpty()
            ? $forecast->avg('forecast')
            : null;

        // TFT validation metrics from the overall validation evaluation.
        $rmse = 262.82;
        $mae = 183.07;
        $r2 = 0.4561;

        return [

            // Historical data
            'records' => $historicalRecords,

            'labels' => $historicalRecords->pluck(
                'record_date'
            ),

            'values' => $historicalRecords->pluck(
                'total_emission'
            ),

            'totalRecords' => $historicalRecords->count(),

            'latestEmission' => optional(
                $historicalRecords->last()
            )->total_emission ?? 0,

            'averageEmission' => round(
                $historicalRecords->avg('total_emission'),
                2
            ),

            'highestEmission' => $historicalRecords->max(
                'total_emission'
            ),

            // TFT forecast
            'forecast' => $forecast,

            'forecastLabels' => $forecast->pluck(
                'record_date'
            ),

            'forecastValues' => $forecast->pluck(
                'forecast'
            ),

            'predictedEmissions' => round(
                $predictedEmissions,
                2
            ),

            'predictedDailyAverage' => $predictedDailyAverage !== null
                ? round($predictedDailyAverage, 2)
                : null,

            'modelUsed' => $forecast->isNotEmpty()
                ? 'Temporal Fusion Transformer (TFT)'
                : null,

            // TFT prediction interval.
            'confidenceLevel' => $predictionInterval,

            // TFT validation metrics
            'rmse' => $rmse,

            'mae' => $mae,

            'r2' => $r2,
        ];
    }
}