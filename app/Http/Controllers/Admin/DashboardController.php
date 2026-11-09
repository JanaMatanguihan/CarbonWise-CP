<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CarbonRecord;
use App\Models\MitigationAction;
use App\Models\SdoReport;
use App\Models\Alert;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Selected dashboard filters.
        $year = request('year', now()->year);
        $month = request('month');

        if ($month === 'all' || $month === '') {
            $month = null;
        }

        $year = $year ? (int) $year : null;
        $month = $month ? (int) $month : null;

        if ($month !== null && ($month < 1 || $month > 12)) {
            $month = null;
        }

        /*
        |--------------------------------------------------------------------------
        | Shared Carbon Record Filter
        |--------------------------------------------------------------------------
        */

        $carbonQuery = CarbonRecord::query();

        if ($year !== null) {
            $carbonQuery->whereYear('record_date', $year);
        }

        if ($month !== null) {
            $carbonQuery->whereMonth('record_date', $month);
        }

        /*
        |--------------------------------------------------------------------------
        | Dashboard Summary Cards
        |--------------------------------------------------------------------------
        */

        // Total Users means all registered accounts, not accounts created
        // during the selected carbon-record month.
        $totalUsers = User::count();

        $totalEmissions = (clone $carbonQuery)->sum('total_emission');

        // Count users who actually submitted carbon records in this period.
        $activeUsers = (clone $carbonQuery)
            ->distinct()
            ->count('user_id');

        // Average emissions per contributing user in the selected period.
        $averageEmission = $activeUsers > 0
            ? $totalEmissions / $activeUsers
            : 0;

        // New users follows the selected year/month filter.
        $newUsersQuery = User::query();

        if ($year !== null) {
            $newUsersQuery->whereYear('created_at', $year);
        }

        if ($month !== null) {
            $newUsersQuery->whereMonth('created_at', $month);
        }

        $newUsers = $newUsersQuery->count();

        $mitigationQuery = MitigationAction::query();

        if ($year !== null) {
            $mitigationQuery->whereYear('created_at', $year);
        }

        if ($month !== null) {
            $mitigationQuery->whereMonth('created_at', $month);
        }

        $mitigationCount = $mitigationQuery->count();

        $reportQuery = SdoReport::query();

        if ($year !== null) {
            $reportQuery->whereYear('created_at', $year);
        }

        if ($month !== null) {
            $reportQuery->whereMonth('created_at', $month);
        }

        $reportCount = $reportQuery->count();

        /*
        |--------------------------------------------------------------------------
        | Emission by Source
        |--------------------------------------------------------------------------
        */

        $transportationTotal = (clone $carbonQuery)->sum('transportation');
        $electricityTotal = (clone $carbonQuery)->sum('electricity');
        $foodTotal = (clone $carbonQuery)->sum('food');

        /*
        |--------------------------------------------------------------------------
        | Emissions Trend
        |--------------------------------------------------------------------------
        */

        $trendYear = $year ?? now()->year;

        $trendQuery = CarbonRecord::query()
            ->whereYear('record_date', $trendYear);

        if ($month !== null) {
            $trendQuery->whereMonth('record_date', $month);
        }

        // Daily totals. Labels use actual dates rather than generic Day 1/2/3.
        $dailyRows = (clone $trendQuery)
            ->selectRaw('DATE(record_date) as period')
            ->selectRaw('SUM(total_emission) as total')
            ->groupByRaw('DATE(record_date)')
            ->orderBy('period')
            ->get();

        $dailyLabels = $dailyRows->map(function ($row) {
            return Carbon::parse($row->period)->format('M j');
        })->values()->all();

        $dailyEmissions = $dailyRows->map(function ($row) {
            return (float) $row->total;
        })->values()->all();

        // Weekly totals. DATE_TRUNC is used for PostgreSQL, which is the
        // configured CarbonWise database; the alternatives support local DBs.
        $driver = DB::getDriverName();

        $weekExpression = match ($driver) {
            'pgsql' => "DATE_TRUNC('week', record_date)",
            'sqlite' => "strftime('%Y-%W', record_date)",
            default => "DATE_FORMAT(record_date, '%Y-%u')",
        };

        $weeklyRows = (clone $trendQuery)
            ->selectRaw("{$weekExpression} as period")
            ->selectRaw('SUM(total_emission) as total')
            ->groupByRaw($weekExpression)
            ->orderBy('period')
            ->get();

        $weeklyLabels = $weeklyRows->map(function ($row) use ($driver) {
            try {
                $weekStart = Carbon::parse($row->period);
                return 'Week of ' . $weekStart->format('M j');
            } catch (\Throwable $e) {
                return 'Week ' . ($row->period ?? '');
            }
        })->values()->all();

        $weeklyEmissions = $weeklyRows->map(function ($row) {
            return (float) $row->total;
        })->values()->all();

        // Monthly trend always shows January-December for the selected year.
        // This chart intentionally ignores the selected month filter.
        // Months with no records are null (gaps), not fabricated zero values.
        $monthlyRows = CarbonRecord::query()
            ->whereYear('record_date', $trendYear)
            ->selectRaw('EXTRACT(MONTH FROM record_date) as month_number')
            ->selectRaw('SUM(total_emission) as total')
            ->selectRaw('COUNT(*) as record_count')
            ->groupByRaw('EXTRACT(MONTH FROM record_date)')
            ->get()
            ->keyBy(fn ($row) => (int) $row->month_number);

        $monthlyLabels = [];
        $monthlyEmissions = [];

        for ($monthNumber = 1; $monthNumber <= 12; $monthNumber++) {
            $monthlyLabels[] = Carbon::create($trendYear, $monthNumber, 1)
                ->format('M');

            $monthlyRow = $monthlyRows->get($monthNumber);

            $monthlyEmissions[] = $monthlyRow
                ? (float) $monthlyRow->total
                : null;
        }

        /*
        |--------------------------------------------------------------------------
        | Top Emitting Departments
        |--------------------------------------------------------------------------
        */

        $topDepartmentsQuery = DB::table('carbon_records')
            ->join(
                'users',
                'carbon_records.user_id',
                '=',
                'users.id'
            )
            ->whereNotNull('users.department')
            ->whereRaw("TRIM(users.department) <> ''");

        if ($year !== null) {
            $topDepartmentsQuery->whereYear(
                'carbon_records.record_date',
                $year
            );
        }

        if ($month !== null) {
            $topDepartmentsQuery->whereMonth(
                'carbon_records.record_date',
                $month
            );
        }

        $topDepartments = $topDepartmentsQuery
            ->select(
                'users.department',
                DB::raw(
                    'SUM(carbon_records.total_emission) as total_emissions'
                )
            )
            ->groupBy('users.department')
            ->orderByDesc('total_emissions')
            ->limit(5)
            ->get();

        // Calculate the denominator with a fresh, ungrouped query.
        // Reusing $topDepartmentsQuery here is incorrect because it already
        // contains GROUP BY, ORDER BY, and LIMIT for the ranked department list.
        $departmentTotalQuery = DB::table('carbon_records')
            ->join(
                'users',
                'carbon_records.user_id',
                '=',
                'users.id'
            )
            ->whereNotNull('users.department')
            ->whereRaw("TRIM(users.department) <> ''");

        if ($year !== null) {
            $departmentTotalQuery->whereYear(
                'carbon_records.record_date',
                $year
            );
        }

        if ($month !== null) {
            $departmentTotalQuery->whereMonth(
                'carbon_records.record_date',
                $month
            );
        }

        $departmentEmissionTotal = (float) $departmentTotalQuery
            ->sum('carbon_records.total_emission');

        foreach ($topDepartments as $department) {
            $department->percentage = $departmentEmissionTotal > 0
                ? round(
                    ((float) $department->total_emissions / $departmentEmissionTotal) * 100,
                    2
                )
                : 0;

            // Use the same percentage for the bar and its visible label.
            $department->bar_percentage = $department->percentage;
        }

        /*
        |--------------------------------------------------------------------------
        | User Engagement
        |--------------------------------------------------------------------------
        */

        $engagementRate = $totalUsers > 0
            ? round(($activeUsers / $totalUsers) * 100)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Recent Alerts
        |--------------------------------------------------------------------------
        */

        $recentAlertsQuery = Alert::query();

        if ($year !== null) {
            $recentAlertsQuery->whereYear('created_at', $year);
        }

        if ($month !== null) {
            $recentAlertsQuery->whereMonth('created_at', $month);
        }

        $recentAlerts = $recentAlertsQuery
            ->latest()
            ->limit(3)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Recommended Mitigation Strategies
        |--------------------------------------------------------------------------
        */

        $recommendedStrategiesQuery = MitigationAction::query();

        if ($year !== null) {
            $recommendedStrategiesQuery->whereYear('created_at', $year);
        }

        if ($month !== null) {
            $recommendedStrategiesQuery->whereMonth('created_at', $month);
        }

        $recommendedStrategies = $recommendedStrategiesQuery
            ->latest()
            ->take(4)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Forecast Placeholder
        |--------------------------------------------------------------------------
        */

        $forecastData = $monthlyEmissions;

        return view('admin.dashboard', compact(
            'totalUsers',
            'totalEmissions',
            'averageEmission',
            'mitigationCount',
            'reportCount',
            'transportationTotal',
            'electricityTotal',
            'foodTotal',
            'dailyLabels',
            'dailyEmissions',
            'weeklyLabels',
            'weeklyEmissions',
            'monthlyLabels',
            'monthlyEmissions',
            'topDepartments',
            'activeUsers',
            'engagementRate',
            'newUsers',
            'recentAlerts',
            'recommendedStrategies',
            'forecastData'
        ));
    }
}
