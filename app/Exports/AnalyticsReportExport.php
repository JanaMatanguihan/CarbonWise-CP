<?php

namespace App\Exports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class AnalyticsReportExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithCustomChunkSize,
    WithColumnWidths
{
    /**
     * Query the latest 10,000 carbon records.
     */
    public function query(): Builder
    {
        return DB::table('carbon_records')
            ->leftJoin(
                'users',
                'carbon_records.user_id',
                '=',
                'users.id'
            )
            ->select([
                'users.email as user_email',
                'carbon_records.transportation',
                'carbon_records.electricity',
                'carbon_records.food',
                'carbon_records.total_emission',
                'carbon_records.record_date',
            ])
            ->orderByDesc('carbon_records.record_date')
            ->orderByDesc('carbon_records.id')
            ->limit(10000);
    }

    /**
     * Column headings.
     */
    public function headings(): array
    {
        return [
            'User Email',
            'Transportation',
            'Electricity',
            'Food',
            'Total Emission (kg CO2e)',
            'Record Date',
        ];
    }

    /**
     * Map each database row to an Excel row.
     */
    public function map($row): array
    {
        return [
            $row->user_email ?? '',
            $row->transportation ?? '',
            $row->electricity ?? '',
            $row->food ?? '',
            $row->total_emission ?? 0,
            $row->record_date ?? '',
        ];
    }

    /**
     * Process records in chunks of 500.
     */
    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * Use fixed widths to avoid automatic width calculation.
     */
    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 20,
            'C' => 18,
            'D' => 18,
            'E' => 25,
            'F' => 18,
        ];
    }
}
