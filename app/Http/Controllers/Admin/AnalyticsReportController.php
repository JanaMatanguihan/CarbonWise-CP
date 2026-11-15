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
    /**
     * Display Analytics and Reports dashboard.
     */
    public function index()
    {
        // Filters
        $selectedMonth = request('month');
        $selectedYear = request('year', now()->year);

        $records = CarbonRecord::query();

        if ($selectedMonth && $selectedMonth !== 'all') {
            $date = Carbon::createFromDate(
                (int) $selectedYear,
                (int) $selectedMonth,
                1
            );

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

        $sources = [
            'Transportation' => $transportation,
            'Electricity' => $electricity,
            'Food Consumption' => $food,
        ];

        $highestSource = max($sources);

        // Emissions Comparison
        if ($selectedMonth && $selectedMonth !== 'all') {
            $selectedDate = Carbon::createFromDate(
                (int) $selectedYear,
                (int) $selectedMonth,
                1
            );
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

    /**
     * Export Excel Report.
     */
    public function exportExcel()
    {
        return Excel::download(
            new AnalyticsReportExport(),
            'carbonwise-analytics-report.xlsx'
        );
    }

    /**
     * Export PDF Report.
     *
     * Exports the latest 500 carbon records,
     * including the associated user's email.
     */
    public function exportPDF()
    {
        /*
         * Retrieve carbon records and join the users table.
         * The email is selected as user_email for the PDF view.
         */
        $records = CarbonRecord::query()
            ->leftJoin(
                'users',
                'carbon_records.user_id',
                '=',
                'users.id'
            )
            ->select([
                'carbon_records.*',
                'users.email as user_email',
            ])
            ->orderByDesc('carbon_records.record_date')
            ->orderByDesc('carbon_records.id')
            ->limit(500)
            ->get();

        /*
         * Overall report statistics.
         */
        $recordCount = $records->count();

        // Sum of the total_emission column.
        $totalEmissions = (float) $records->sum('total_emission');

        // Average total emission per carbon record.
        $averageEmissionPerRecord = $recordCount > 0
            ? $totalEmissions / $recordCount
            : 0;

        /*
         * Calculate emissions by source.
         */
        $sourceTotals = [
            'Transportation' => (float) $records->sum('transportation'),
            'Electricity' => (float) $records->sum('electricity'),
            'Food' => (float) $records->sum('food'),
        ];

        /*
         * Combined source emissions.
         */
        $combinedSourceTotal = array_sum($sourceTotals);

        /*
         * Rank sources from highest to lowest.
         */
        $rankedSources = $sourceTotals;

        arsort($rankedSources);

        /*
         * Identify the highest contributing source.
         */
        $highestSource = null;
        $highestPercentage = 0;

        if ($combinedSourceTotal > 0) {
            $highestSource = array_key_first($rankedSources);

            $highestPercentage =
                ($rankedSources[$highestSource] / $combinedSourceTotal) * 100;
        }

        /*
         * Calculate each source's percentage contribution.
         */
        $sourcePercentages = [];

        foreach ($sourceTotals as $source => $value) {
            $sourcePercentages[$source] = $combinedSourceTotal > 0
                ? ($value / $combinedSourceTotal) * 100
                : 0;
        }

        /*
         * Compare total_emission with the sum of the
         * three source columns.
         *
         * A difference may indicate that total_emission
         * includes other components or uses different
         * calculation logic.
         */
        $emissionDifference = $totalEmissions - $combinedSourceTotal;

        /*
         * Source-specific mitigation recommendations.
         */
        $recommendations = [
            'Transportation' =>
                'Encourage public transportation, carpooling, walking, ' .
                'cycling, and more efficient travel practices.',

            'Electricity' =>
                'Promote energy-efficient appliances, switch off unused ' .
                'equipment, and practice responsible electricity consumption.',

            'Food' =>
                'Encourage sustainable food choices, reduce food waste, ' .
                'and promote responsible food consumption.',
        ];

        $recommendation = $highestSource !== null
            ? $recommendations[$highestSource]
            : 'No recommendation can be generated because no emission data is available.';

        /*
         * Overall source interpretation.
         */
        if ($highestSource !== null) {
            $interpretation =
                $highestSource .
                ' is the leading emission source, contributing ' .
                number_format($highestPercentage, 2) .
                '% of the combined source emissions. ' .
                'It has the largest share among the three monitored ' .
                'categories. Prioritizing reduction efforts in this ' .
                'category may help address a substantial portion of ' .
                'the recorded source emissions.';
        } else {
            $interpretation =
                'No source emission data is available for the exported records.';
        }

        /*
         * Total emissions interpretation.
         */
        if ($recordCount > 0) {
            $totalInterpretation =
                'The ' . number_format($recordCount) .
                ' exported carbon records contain a total of ' .
                number_format($totalEmissions, 2) .
                ' kg CO2e based on the total_emission field. ' .
                'The average emission per carbon record is ' .
                number_format($averageEmissionPerRecord, 2) .
                ' kg CO2e.';
        } else {
            $totalInterpretation =
                'No carbon records are available for this report.';
        }

        /*
         * Source comparison interpretation.
         */
        if ($combinedSourceTotal > 0) {
            $sourceComparisonInterpretation =
                'Transportation contributes ' .
                number_format($sourcePercentages['Transportation'], 2) .
                '%, Electricity contributes ' .
                number_format($sourcePercentages['Electricity'], 2) .
                '%, and Food contributes ' .
                number_format($sourcePercentages['Food'], 2) .
                '% of the combined source emissions.';
        } else {
            $sourceComparisonInterpretation =
                'No source contributions are available for comparison.';
        }

        /*
         * Prepare data for the PDF Blade template.
         */
        $pdf = Pdf::loadView(
            'admin.analytics-pdf',
            compact(
                'records',
                'recordCount',
                'totalEmissions',
                'averageEmissionPerRecord',
                'sourceTotals',
                'combinedSourceTotal',
                'rankedSources',
                'sourcePercentages',
                'highestSource',
                'highestPercentage',
                'emissionDifference',
                'interpretation',
                'totalInterpretation',
                'sourceComparisonInterpretation',
                'recommendation'
            )
        )->setPaper('a4', 'landscape');

        return $pdf->download(
            'carbonwise-analytics-report.pdf'
        );
    }
}
