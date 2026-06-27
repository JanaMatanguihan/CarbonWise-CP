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
        $selectedMonth = request('month', now()->format('Y-m'));

        $year = date('Y', strtotime($selectedMonth));
        $month = date('m', strtotime($selectedMonth));
        /*
        Dashboard Summary Cards
        */

        // Total registered users
            $totalUsers = UserInfo::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count();

        // Total carbon emissions
        $totalEmissions = CarbonRecord::whereYear('record_date', $year)
        ->whereMonth('record_date', $month)
        ->sum('total_emission');

        // Average emission per user
        $averageEmission = $totalUsers > 0
            ? $totalEmissions / $totalUsers
            : 0;

        // Total mitigation actions
        $mitigationCount = MitigationAction::whereYear('created_at', $year)
        ->whereMonth('created_at', $month)
        ->count();

        // Total SDO reports
        $reportCount = SdoReport::whereYear('created_at', $year)
        ->whereMonth('created_at', $month)
        ->count();

        /*
        Emission by Source
        */

        $transportationTotal = CarbonRecord::whereYear('record_date', $year)
        ->whereMonth('record_date', $month)
        ->sum('transportation');

        $electricityTotal = CarbonRecord::whereYear('record_date', $year)
            ->whereMonth('record_date', $month)
            ->sum('electricity');

        $foodTotal = CarbonRecord::whereYear('record_date', $year)
            ->whereMonth('record_date', $month)
            ->sum('food');

        $wasteTotal = CarbonRecord::whereYear('record_date', $year)
            ->whereMonth('record_date', $month)
            ->sum('waste');

        /*
         Monthly Emissions Trend (Current Year)
        */

        // Ensure monthlyEmissions is always defined to avoid undefined variable notices
        $monthlyEmissions = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyEmissions[] = CarbonRecord::whereYear(
                'record_date',
                $year
            )
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