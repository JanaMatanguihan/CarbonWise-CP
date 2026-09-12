<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CarbonRecord;
use App\Models\User;
use App\Models\MitigationAction;
use App\Exports\AnalyticsReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class AnalyticsReportController extends Controller
{
    public function index()
    {
        // Filters
        $selectedMonth = request('month');

        $records = CarbonRecord::query();

        if ($selectedMonth) {
            $date = Carbon::parse($selectedMonth . '-01');

            $records
                ->whereYear('record_date', $date->year)
                ->whereMonth('record_date', $date->month);
        }

        // Summary Report Cards
        $totalUsers = User::count();

        $totalEmissions = (clone $records)
            ->sum('total_emission');

        $averageEmission = $totalUsers > 0
            ? $totalEmissions / $totalUsers
            : 0;

        $activeUsers = (clone $records)
            ->distinct('user_id')
            ->count('user_id');

        $mitigationActions = MitigationAction::count();

        // Top Emitting Sources
        $transportation = (clone $records)
            ->sum('transportation');

        $electricity = (clone $records)
            ->sum('electricity');

        $food = (clone $records)
            ->sum('food');

        // Source Data
        $sources = [
            'Transportation' => $transportation,
            'Electricity' => $electricity,
            'Food Consumption' => $food,
        ];

        $highestSource = max($sources);

        // Emissions Comparison
        if ($selectedMonth) {
            $selectedDate = Carbon::parse($selectedMonth . '-01');
        } else {
            $selectedDate = now()->startOfMonth();
        }

        // Current Month
        $currentMonth = CarbonRecord::query()
            ->whereYear('record_date', $selectedDate->year)
            ->whereMonth('record_date', $selectedDate->month);

        // Previous Month
        $previousMonth = $selectedDate->copy()->subMonth();

        $lastMonth = CarbonRecord::query()
            ->whereYear('record_date', $previousMonth->year)
            ->whereMonth('record_date', $previousMonth->month);

        // Comparison Data
        $comparisonData = [
            'current' => [
                (float) (clone $currentMonth)->sum('transportation'),
                (float) (clone $currentMonth)->sum('electricity'),
                (float) (clone $currentMonth)->sum('food'),
            ],

            'last' => [
                (float) (clone $lastMonth)->sum('transportation'),
                (float) (clone $lastMonth)->sum('electricity'),
                (float) (clone $lastMonth)->sum('food'),
            ],
        ];

        // Return Analytics Page
        return view(
            'admin.analytics-reports',
            compact(
                'totalUsers',
                'totalEmissions',
                'averageEmission',
                'activeUsers',
                'mitigationActions',
                'sources',
                'highestSource',
                'comparisonData'
            )
        );
    }

    // Export Excel Report
    public function exportExcel()
    {
        return Excel::download(
            new AnalyticsReportExport,
            'carbonwise-analytics-report.xlsx'
        );
    }

    // Export PDF Report
    public function exportPDF()
    {
        $records = CarbonRecord::latest()
            ->get();

        $pdf = Pdf::loadView(
            'admin.analytics-pdf',
            compact('records')
        );

        return $pdf->download(
            'carbonwise-analytics-report.pdf'
        );
    }
}