<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserInfo;
use App\Models\CarbonRecord;
use App\Models\MitigationAction;
use App\Models\SdoReport;
use App\Models\Alert;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $selectedMonth = request('month');

        $year = null;
        $month = null;

        if ($selectedMonth) {
            [$year, $month] = explode('-', $selectedMonth);
        }

        /*
        Dashboard Summary Cards
        */

       

        // Total registered users
        $userQuery = UserInfo::query();

        if ($year && $month) {
            $userQuery->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month);
        }

        $totalUsers = $userQuery->count();

        // Total carbon emissions
        $emissionQuery = CarbonRecord::query();

        if ($year && $month) {
            $emissionQuery->whereYear('record_date', $year)
                        ->whereMonth('record_date', $month);
        }

        $totalEmissions = $emissionQuery->sum('total_emission');

        // Average emission per user
        $averageEmission = $totalUsers > 0
            ? $totalEmissions / $totalUsers
            : 0;

        // Total mitigation actions
        $mitigationQuery = MitigationAction::query();

        if ($year && $month) {
            $mitigationQuery->whereYear('created_at', $year)
                            ->whereMonth('created_at', $month);
        }

        $mitigationCount = $mitigationQuery->count();

        // Total SDO reports
       $reportQuery = SdoReport::query();

        if ($year && $month) {
            $reportQuery->whereYear('created_at', $year)
                        ->whereMonth('created_at', $month);
        }

        $reportCount = $reportQuery->count();

        /*
        Emission by Source
        */
        $transportationQuery = CarbonRecord::query();

        if ($year && $month) {
            $transportationQuery->whereYear('record_date', $year)
                                ->whereMonth('record_date', $month);
        }

        $transportationTotal = $transportationQuery->sum('transportation');

        $electricityQuery = CarbonRecord::query();

        if ($year && $month) {
            $electricityQuery->whereYear('record_date', $year)
                            ->whereMonth('record_date', $month);
        }

        $electricityTotal = $electricityQuery->sum('electricity');

        $foodQuery = CarbonRecord::query();

        if ($year && $month) {
            $foodQuery->whereYear('record_date', $year)
                    ->whereMonth('record_date', $month);
        }

        $foodTotal = $foodQuery->sum('food');

        $wasteQuery = CarbonRecord::query();

        if ($year && $month) {
            $wasteQuery->whereYear('record_date', $year)
                    ->whereMonth('record_date', $month);
        }

        $wasteTotal = $wasteQuery->sum('waste');

        /*
         Monthly Emissions Trend (Current Year)
        */

        // Ensure monthlyEmissions is always defined to avoid undefined variable notices
        $monthlyEmissions = [];
        $trendYear = $year ?? now()->year;

        $monthlyEmissions = [];

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
                'user_info',
                'carbon_records.g_suite',
                '=',
                'user_info.g_suite'
            )
            ->select(
                'user_info.department',
                DB::raw('SUM(carbon_records.total_emission) as total_emissions')
            )
            ->whereYear('carbon_records.record_date', $year)
            ->whereMonth('carbon_records.record_date', $month)
            ->groupBy('user_info.department')
            ->orderByDesc('total_emissions')
            ->limit(5)
            ->get();

            foreach ($topDepartments as $department) {

            $department->percentage = $totalEmissions > 0
                ? round(($department->total_emissions / $totalEmissions) * 100, 1)
                : 0;

}
        /*
         User Engagement
        */

        $activeUsers = CarbonRecord::whereYear('record_date', $year)
        ->whereMonth('record_date', $month)
        ->distinct('g_suite')
        ->count('g_suite');

        $engagementRate = $totalUsers > 0
            ? round(($activeUsers / $totalUsers) * 100)
            : 0;

        /*
        Recent Alerts
        */

        $recentAlerts = Alert::whereYear('created_at', $year)
        ->whereMonth('created_at', $month)
        ->latest()
        ->take(5)
        ->get();
        /*
        Recommended Mitigation Strategies
        */

       $recommendedStrategies = MitigationAction::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
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
            'wasteTotal',

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