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
        |--------------------------------------------------------------------------
        | COMMUNITY DATA CUTOFF
        |--------------------------------------------------------------------------
        |
        | July 20, 2026 is the last verified date containing
        | community-level aggregated emission data.
        |
        | Records after this date belong to newer test-user
        | submissions and must not be mixed with community totals.
        |
        */

        $communityDataCutoff = '2026-07-20';

        /*
        |--------------------------------------------------------------------------
        | RETRIEVE HISTORICAL COMMUNITY DATA
        |--------------------------------------------------------------------------
        |
        | Aggregate all users' carbon records by date.
        |
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
        |--------------------------------------------------------------------------
        | PREPARE CONTINUOUS DAILY RECORDS
        |--------------------------------------------------------------------------
        |
        | Missing dates are forward-filled temporarily.
        | No database records are created or modified.
        |
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
        |--------------------------------------------------------------------------
        | TFT V6 API CONFIGURATION
        |--------------------------------------------------------------------------
        |
        | Read the API URL from Laravel configuration.
        | This allows local V6 testing without hardcoding
        | the API address in this service.
        |
        */

        $forecast = collect();

        $predictionInterval = null;
        $modelUsed = null;

        $tftApiUrl = rtrim(
            (string) config('services.tft.url', ''),
            '/'
        );

        /*
        |--------------------------------------------------------------------------
        | REQUEST TFT V6 FORECAST
        |--------------------------------------------------------------------------
        |
        | Send historical community records to the API.
        |
        */

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

                    /*
                     * Retrieve TFT V6 forecast results.
                     */

                    $forecast = collect(
                        $response->json('forecast', [])
                    );

                    /*
                     * Retrieve prediction interval.
                     */

                    $predictionInterval = $response->json(
                        'prediction_interval'
                    );

                    /*
                     * Retrieve model name returned by FastAPI.
                     */

                    $modelUsed = $response->json('model');

                    Log::info(
                        'TFT Forecast API request successful.',
                        [
                            'model' => $modelUsed,
                            'forecast_count' => $forecast->count(),
                            'prediction_interval' => $predictionInterval,
                        ]
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
        |--------------------------------------------------------------------------
        | HISTORICAL CHART DATA
        |--------------------------------------------------------------------------
        |
        | Latest 30 verified community-level dates only.
        |
        */

        $historicalRecords = $records
            ->sortByDesc('record_date')
            ->take(30)
            ->sortBy('record_date')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | FORECAST SUMMARY CALCULATIONS
        |--------------------------------------------------------------------------
        */

        $predictedEmissions = $forecast->sum('forecast');

        $predictedDailyAverage = $forecast->isNotEmpty()
            ? $forecast->avg('forecast')
            : null;

        /*
        |--------------------------------------------------------------------------
        | TFT V6 MODEL EVALUATION METRICS
        |--------------------------------------------------------------------------
        |
        | Source: Previously evaluated V6 validation results.
        |
        | Validation period: January 1 - June 29, 2026
        | Number of validation records: 180 days
        |
        | These are historical validation metrics.
        | They are not calculated from the current forecast.
        |
        | July 1 - July 20, 2026 test results are separate
        | and must not be represented by these values.
        |
        */

        $rmse = 122.26;
        $mae = 93.02;
        $r2 = 0.8823;

        $evaluationModel = 'Temporal Fusion Transformer V6';

        $evaluationPeriod = 'V6 Validation | 180 Days';

        /*
        |--------------------------------------------------------------------------
        | RETURN DATA TO THE FORECASTING DASHBOARD
        |--------------------------------------------------------------------------
        */

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
                $historicalRecords->avg('total_emission') ?? 0,
                2
            ),

            'highestEmission' => $historicalRecords->max(
                'total_emission'
            ),

            // TFT V6 forecast

            'forecast' => $forecast,

            'forecastLabels' => $forecast->pluck(
                'record_date'
            ),

            // TFT V6 median forecast values

            'forecastValues' => $forecast->pluck(
                'forecast'
            ),

            /*
            |--------------------------------------------------------------------------
            | ML INTEGRATION: TFT V6 PREDICTION BOUNDARIES
            |--------------------------------------------------------------------------
            |
            | These values are generated by the trained TFT V6
            | model and returned by the FastAPI service.
            |
            | lower_bound: Lower prediction boundary.
            | upper_bound: Upper prediction boundary.
            |
            | Laravel passes these values to the Blade view
            | for visualization using ApexCharts.
            |
            */

            'lowerBoundValues' => $forecast->pluck(
                'lower_bound'
            ),

            'upperBoundValues' => $forecast->pluck(
                'upper_bound'
            ),

            'predictedEmissions' => round(
                $predictedEmissions,
                2
            ),

            'predictedDailyAverage' => $predictedDailyAverage !== null
                ? round($predictedDailyAverage, 2)
                : null,

            'modelUsed' => $forecast->isNotEmpty()
                ? ($modelUsed ?? 'Temporal Fusion Transformer V6')
                : null,

            // Prediction interval

            'confidenceLevel' => $predictionInterval,

            // TFT V6 evaluation metrics

            'rmse' => $rmse,
            'mae' => $mae,
            'r2' => $r2,

            // Evaluation information

            'evaluationModel' => $evaluationModel,
            'evaluationPeriod' => $evaluationPeriod,

        ];
    }
}
