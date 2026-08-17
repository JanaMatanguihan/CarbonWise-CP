<?php

namespace App\Services;

use App\Models\CarbonRecord;

class ForecastService
{
    public function getHistoricalData()
    {
        $records = CarbonRecord::select(
                'record_date',
                'total_emission'
            )
            ->orderBy('record_date')
            ->get();

        return [

            'records' => $records,

            'labels' => $records->pluck('record_date'),

            'values' => $records->pluck('total_emission'),

            'totalRecords' => $records->count(),

            'latestEmission' => optional($records->last())->total_emission ?? 0,

            'averageEmission' => round($records->avg('total_emission'), 2),

            'highestEmission' => $records->max('total_emission'),

        ];
    }
}