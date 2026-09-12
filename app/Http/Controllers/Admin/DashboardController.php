<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CarbonRecord;
use App\Models\MitigationAction;
use App\Models\SdoReport;
use App\Models\Alert;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Selected filters
        $year = request('year', 2026);
        $month = request('month');

        // If "all" is selected, ignore the month filter
        if ($month === 'all') {
            $month = null;
        }

        /*
        Dashboard Summary Cards
        */

        // Total registered users
        $userQuery = User::query();

        if ($year) {
            $userQuery->whereYear('created_at', $year);

            if ($month) {
                $userQuery->whereMonth('created_at', $month);
            }
        }

        $totalUsers = $userQuery->count();

        // Total carbon emissions
        $emissionQuery = CarbonRecord::query();

        if ($year) {
            $emissionQuery->whereYear('record_date', $year);

            if ($month) {
                $emissionQuery->whereMonth('record_date', $month);
            }
        }

        $totalEmissions = $emissionQuery->sum('total_emission');

        // Average emission per user
        $averageEmission = $totalUsers > 0
            ? $totalEmissions / $totalUsers
            : 0;

        // Total mitigation strategies
        $mitigationQuery = MitigationAction::query();

        if ($year) {
            $mitigationQuery->whereYear('created_at', $year);

            if ($month) {
                $mitigationQuery->whereMonth('created_at', $month);
            }
        }

        $mitigationCount = $mitigationQuery->count();

        // Total SDO reports
        $reportQuery = SdoReport::query();

        if ($year) {
            $reportQuery->whereYear('created_at', $year);

            if ($month) {
                $reportQuery->whereMonth('created_at', $month);
            }
        }

        $reportCount = $reportQuery->count();

        /*
        Emission by Source
        */

        $transportationQuery = CarbonRecord::query();

        if ($year && $month) {
            $transportationQuery
                ->whereYear('record_date', $year)
                ->whereMonth('record_date', $month);
        } elseif ($year) {
            $transportationQuery->whereYear('record_date', $year);
        }

        $transportationTotal = $transportationQuery->sum('transportation');

        $electricityQuery = CarbonRecord::query();

        if ($year && $month) {
            $electricityQuery
                ->whereYear('record_date', $year)
                ->whereMonth('record_date', $month);
        } elseif ($year) {
            $electricityQuery->whereYear('record_date', $year);
        }

        $electricityTotal = $electricityQuery->sum('electricity');

        $foodQuery = CarbonRecord::query();

        if ($year && $month) {
            $foodQuery
                ->whereYear('record_date', $year)
                ->whereMonth('record_date', $month);
        } elseif ($year) {
            $foodQuery->whereYear('record_date', $year);
        }

        $foodTotal = $foodQuery->sum('food');

        /*
        Monthly Emissions Trend (Current Year)
        */

        $monthlyEmissions = [];
        $trendYear = $year ?? now()->year;

        for ($i = 1; $i <= 12; $i++) {
            $monthlyEmissions[] = CarbonRecord::whereYear('record_date', $trendYear)
                ->whereMonth('record_date', $i)
                ->sum('total_emission');
        }

        /*
        Top Emitting Departments
        */

        $topDepartments = DB::table('carbon_records')
            ->join(
                'users',
                'carbon_records.user_id',
                '=',
                'users.id'
            )
            ->select(
                'users.department',
                DB::raw('SUM(carbon_records.total_emission) as total_emissions')
            );

        if ($year && $month) {
            $topDepartments
                ->whereYear('carbon_records.record_date', $year)
                ->whereMonth('carbon_records.record_date', $month);
        } elseif ($year) {
            $topDepartments
                ->whereYear('carbon_records.record_date', $year);
        }

        $topDepartments = $topDepartments
            ->groupBy('users.department')
            ->orderByDesc('total_emissions')
            ->limit(5)
            ->get();

        $maxEmission = $topDepartments->max('total_emissions');

        foreach ($topDepartments as $department) {
            $department->percentage = $maxEmission > 0
                ? round(($department->total_emissions / $maxEmission) * 100, 1)
                : 0;
        }

        /*
        User Engagement
        */

        $activeUsersQuery = CarbonRecord::query();

        if ($year) {
            $activeUsersQuery->whereYear('record_date', $year);

            if ($month) {
                $activeUsersQuery->whereMonth('record_date', $month);
            }
        }

        $activeUsers = $activeUsersQuery
            ->distinct('user_id')
            ->count('user_id');

        $engagementRate = $totalUsers > 0
            ? round(($activeUsers / $totalUsers) * 100)
            : 0;

        /*
        Recent Alerts
        */

        $recentAlertsQuery = Alert::query();

        if ($year && $month) {
            $recentAlertsQuery
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month);
        } elseif ($year) {
            $recentAlertsQuery->whereYear('created_at', $year);
        }

        $recentAlerts = $recentAlertsQuery
            ->latest()
            ->limit(3)
            ->get();

        /*
        Recommended Mitigation Strategies
        */

        $recommendedStrategiesQuery = MitigationAction::query();

        if ($year && $month) {
            $recommendedStrategiesQuery
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month);
        } elseif ($year) {
            $recommendedStrategiesQuery->whereYear('created_at', $year);
        }

        $recommendedStrategies = $recommendedStrategiesQuery
            ->latest()
            ->take(4)
            ->get();

        /*
        Forecast Placeholder
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
            'monthlyEmissions',
            'topDepartments',
            'activeUsers',
            'engagementRate',
            'recentAlerts',
            'recommendedStrategies',
            'forecastData'
        ));
    }
}