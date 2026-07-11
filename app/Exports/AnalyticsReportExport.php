<?php

namespace App\Exports;

use App\Models\CarbonRecord;
use Maatwebsite\Excel\Concerns\FromCollection;

class AnalyticsReportExport implements FromCollection
{
    public function collection()
    {
        return CarbonRecord::select(
            'g_suite',
            'transportation',
            'electricity',
            'food',
            'waste',
            'total_emission',
            'record_date'
        )->get();
    }
}