<?php

namespace App\Exports;

use App\Models\CarbonRecord;
use App\Models\UserInfo;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Database\Eloquent\Builder;

class EmissionOverviewExport implements FromQuery, WithHeadings
{
    protected $month;
    protected $department;

    public function __construct($month = null, $department = null)
    {
        $this->month = $month;
        $this->department = $department;
    }

    public function query(): Builder
    {
        $query = CarbonRecord::query();

        if ($this->month) {
            $query->whereYear('record_date', substr($this->month, 0, 4))
                  ->whereMonth('record_date', substr($this->month, 5, 2));
        }

        if ($this->department) {

            $users = UserInfo::where(
                'department',
                $this->department
            )->pluck('g_suite');

            $query->whereIn('g_suite', $users);
        }

        return $query->select(
            'record_date',
            'g_suite',
            'transportation',
            'electricity',
            'food',
            'total_emission'
        );
    }

    public function headings(): array
    {
        return [
            'Record Date',
            'User',
            'Transportation',
            'Electricity',
            'Food',
            'Total Emission',
        ];
    }
}