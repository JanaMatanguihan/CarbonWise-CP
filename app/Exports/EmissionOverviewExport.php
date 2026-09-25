<?php

namespace App\Exports;

use App\Models\CarbonRecord;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmissionOverviewExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $month;
    protected $department;

    public function __construct($month = null, $department = null)
    {
        $this->month = $month;
        $this->department = $department;
    }

    /**
     * Get the carbon emission records for the report.
     */
    public function collection()
    {
        $query = CarbonRecord::query()
            ->with('user')
            ->orderBy('record_date', 'asc');

        /*
         * Filter by selected month.
         */
        if ($this->month) {

            [$year, $month] = explode('-', $this->month);

            $query->whereYear('record_date', $year)
                ->whereMonth('record_date', $month);
        }

        /*
         * Filter by department through the related user.
         */
        if ($this->department) {

            $query->whereHas('user', function ($userQuery) {

                $userQuery->where(
                    'department',
                    $this->department
                );

            });
        }

        return $query->get();
    }

    /**
     * Define the Excel column headings.
     */
    public function headings(): array
    {
        return [
            'G Suite',
            'Name',
            'Department',
            'Record Date',
            'Transportation',
            'Electricity',
            'Food',
            'Total Emission',
        ];
    }

    /**
     * Map each carbon record to an Excel row.
     */
    public function map($record): array
    {
        $user = $record->user;

        return [
            $user?->email ?? '',
            $user?->name ?? '',
            $user?->department ?? '',
            $record->record_date
                ? $record->record_date->format('Y-m-d')
                : '',
            number_format((float) $record->transportation, 2, '.', ''),
            number_format((float) $record->electricity, 2, '.', ''),
            number_format((float) $record->food, 2, '.', ''),
            number_format((float) $record->total_emission, 2, '.', ''),
        ];
    }
}