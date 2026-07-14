<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CarbonRecord;
use App\Models\UserInfo;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmissionOverviewController extends Controller
{
    public function index()
    {
        // Base query for filters
        $records = CarbonRecord::query();

        // Month filter
        if (request('month')) {
            $records->whereYear(
                'record_date',
                substr(request('month'), 0, 4)
            );

            $records->whereMonth(
                'record_date',
                substr(request('month'), 5, 2)
            );
        }

        // Department filter
        if (request('department')) {
            $records->whereIn(
                'g_suite',
                UserInfo::where(
                    'department',
                    request('department')
                )
                ->pluck('g_suite')
            );
        }

        // Calculate summary cards
        $totalEmissions = (clone $records)->sum('total_emission');
        $transportation = (clone $records)->sum('transportation');
        $electricity    = (clone $records)->sum('electricity');
        $food           = (clone $records)->sum('food');

        if ($totalEmissions > 0) {
            $transportationPercentage = ($transportation / $totalEmissions) * 100;
            $electricityPercentage    = ($electricity / $totalEmissions) * 100;
            $foodPercentage           = ($food / $totalEmissions) * 100;
        } else {
            $transportationPercentage = 0;
            $electricityPercentage    = 0;
            $foodPercentage           = 0;
        }

        // Daily Chart Trend
        $dailyTrend = (clone $records)
            ->select(
                DB::raw("DATE(record_date) as day"),
                DB::raw("TO_CHAR(DATE(record_date),'Mon DD') as label"),
                DB::raw("SUM(total_emission) as total")
            )
            ->groupBy(DB::raw("DATE(record_date)"))
            ->orderBy(DB::raw("DATE(record_date)"))
            ->get();

        // Weekly Chart Trend
        $weeklyTrend = (clone $records)
            ->select(
                DB::raw("'Week ' || EXTRACT(WEEK FROM record_date)::int as label"),
                DB::raw("SUM(total_emission) as total")
            )
            ->groupBy(
                DB::raw("EXTRACT(YEAR FROM record_date)"),
                DB::raw("EXTRACT(WEEK FROM record_date)")
            )
            ->orderBy(DB::raw("EXTRACT(YEAR FROM record_date)"))
            ->orderBy(DB::raw("EXTRACT(WEEK FROM record_date)"))
            ->get();

        // Monthly Chart Trend
        $monthlyTrend = (clone $records)
            ->select(
                DB::raw("DATE_TRUNC('month', record_date) as month"),
                DB::raw("TO_CHAR(DATE_TRUNC('month', record_date),'Mon YYYY') as label"),
                DB::raw("SUM(total_emission) as total")
            )
            ->groupBy(DB::raw("DATE_TRUNC('month', record_date)"))
            ->orderBy(DB::raw("DATE_TRUNC('month', record_date)"))
            ->get();

        // Department breakdown table
        $departmentQuery = DB::table('carbon_records')
            ->join(
                'user_info',
                'carbon_records.g_suite',
                '=',
                'user_info.g_suite'
            );

        // Department Month Filter
        if (request('month')) {
            $departmentQuery
                ->whereYear('carbon_records.record_date', substr(request('month'), 0, 4))
                ->whereMonth('carbon_records.record_date', substr(request('month'), 5, 2));
        }

        // Department Selector Filter
        if (request('department')) {
            $departmentQuery->where('user_info.department', request('department'));
        }

        $departmentEmissions = $departmentQuery
            ->select(
                'user_info.department',
                DB::raw('SUM(carbon_records.total_emission) as total')
            )
            ->groupBy('user_info.department')
            ->orderByDesc('total')
            ->get();

        foreach ($departmentEmissions as $department) {
            $department->percentage = $totalEmissions > 0
                ? ($department->total / $totalEmissions) * 100
                : 0;
        }

        // Emissions Comparison (Selected month vs last month)
        $selectedMonth = request('month')
            ? Carbon::parse(request('month') . '-01')
            : now();

        // This Month Query
        $currentMonth = CarbonRecord::query()
            ->whereYear('record_date', $selectedMonth->year)
            ->whereMonth('record_date', $selectedMonth->month);

        // Last Month Query
        $previousMonth = $selectedMonth->copy()->subMonth();

        $lastMonth = CarbonRecord::query()
            ->whereYear('record_date', $previousMonth->year)
            ->whereMonth('record_date', $previousMonth->month);

        // Apply department filter to comparison dataset
        if (request('department')) {
            $users = UserInfo::where('department', request('department'))
                ->pluck('g_suite');

            $currentMonth->whereIn('g_suite', $users);
            $lastMonth->whereIn('g_suite', $users);
        }

        // Chart Data formatting
        $comparisonData = [
            'current' => [
                (clone $currentMonth)->sum('transportation'),
                (clone $currentMonth)->sum('electricity'),
                (clone $currentMonth)->sum('food'),
            ],
            'last' => [
                (clone $lastMonth)->sum('transportation'),
                (clone $lastMonth)->sum('electricity'),
                (clone $lastMonth)->sum('food'),
            ]
        ];

        // Fetch list for Department filter dropdown
        $departments = UserInfo::whereNotNull('department')
            ->distinct()
            ->pluck('department');

        // Render Blade template with context
        return view(
            'admin.emission-overview',
            compact(
                'totalEmissions',
                'transportation',
                'electricity',
                'food',
                'transportationPercentage',
                'electricityPercentage',
                'foodPercentage',
                'dailyTrend',
                'weeklyTrend',
                'monthlyTrend',
                'departmentEmissions',
                'departments',
                'comparisonData'
            )
        );
    }

    // Export emissions report
    public function export()
    {
        return back()->with('success', 'Export feature coming soon.');
    }
}