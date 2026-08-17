<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CarbonRecord;

class ForecastController extends Controller
        {
        public function index()
        {
            $records = CarbonRecord::select(
                    'record_date',
                    'total_emission'
                )
                ->orderBy('record_date')
                ->get();

            $labels = $records->pluck('record_date');

            $values = $records->pluck('total_emission');

            $totalRecords = $records->count();

            $latestEmission = optional($records->last())->total_emission ?? 0;

            $averageEmission = round($records->avg('total_emission'), 2);

            $highestEmission = $records->max('total_emission');

            return view('admin.forecasting', [
            'records'           => $records,
            'labels'            => $labels,
            'values'            => $values,

            'totalRecords'      => $totalRecords,
            'latestEmission'    => $latestEmission,
            'averageEmission'   => $averageEmission,
            'highestEmission'   => $highestEmission,
            ]);
        }
    }