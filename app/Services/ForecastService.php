<?php
namespace App\Services;

use App\Models\CarbonRecord;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ForecastService
{
    public function getHistoricalData()
    {
        set_time_limit(0);

        /*
         * COMMUNITY DATA CUTOFF
         *
         * July 20, 2026 is the last verified date containing
         * community-level aggregated emission data.
         *
         * Records after this date belong to the newer test-user
         * submissions and must not be mixed with community totals.
         */
        $communityDataCutoff = '2026-07-20';

        /*
         * Retrieve only verified community-level historical data.
         * Aggregate all users by date.
         */
        $records = CarbonRecord::query()
            ->whereDate('record_date', '<=', $communityDataCutoff)
            ->select('record_date')
            ->selectRaw('SUM(transportation) AS transportation')
            ->selectRaw('SUM(electricity) AS electricity')
            ->selectRaw('SUM(food) AS food')
            ->selectRaw('SUM(total_emission) AS total_emission')
            ->groupBy('record_date')
            ->orderBy('record_date')
            ->get();

        /*
         * Prepare continuous daily records for the TFT API.
         * Missing dates are forward-filled temporarily.
         * No database records are created or modified.
         */
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

                    $forecastRecords->push([
                        'record_date' => $dateKey,
                        ...$previousRecord,
                    ]);
                }
            }
        }

        /*
         * Request the 30-day TFT forecast.
         */
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

                } else {

                    Log::error(
                        'TFT Forecast API returned an unsuccessful response.',
                        [
                            'status' => $response->status(),
                            'body' => $response->body(),
                        ]
                    );
                }

            } catch (\Throwable $e) {

                Log::error(
                    'TFT Forecast API Error: ' . $e->getMessage()
                );
            }
        }

        /*
         * Historical chart data:
         * Latest 30 verified community-level dates only.
         */
        $historicalRecords = $records
            ->sortByDesc('record_date')
            ->take(30)
            ->sortBy('record_date')
            ->values();

        /*
         * Forecast summary calculations.
         */
        $predictedEmissions = $forecast->sum('forecast');

        $predictedDailyAverage = $forecast->isNotEmpty()
            ? $forecast->avg('forecast')
            : null;

        /*
         * Actual V2 validation metrics.
         *
         * Source:
         * tft_v2_validation_predictions.csv
         *
         * Validation period:
         * January 1 - June 29, 2026
         *
         * Number of validation records: 180
         */
        $rmse = 248.6474;
        $mae = 172.5523;
        $r2 = 0.5132;

        return [

            // Historical community emissions
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

            // Prediction interval
            'confidenceLevel' => $predictionInterval,

            // V2 validation metrics
            'rmse' => $rmse,
            'mae' => $mae,
            'r2' => $r2,
        ];
    }
}
