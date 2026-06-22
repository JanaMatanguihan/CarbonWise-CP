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
        $selectedMonth = request('month', now()->format('Y-m'));

        [$year, $month] = explode('-', $selectedMonth);
        /*
        |--------------------------------------------------------------------------
        | Dashboard Summary Cards
        |--------------------------------------------------------------------------
        */

        // Total registered users
        $totalUsers = User::whereYear('created_at', $year)
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
        |--------------------------------------------------------------------------
        | Emission by Source
        |--------------------------------------------------------------------------
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
        |--------------------------------------------------------------------------
        | Monthly Emissions Trend (Current Year)
        |--------------------------------------------------------------------------
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
        |--------------------------------------------------------------------------
        | Top Emitting Departments
        |--------------------------------------------------------------------------
        */

        $totalEmissions = CarbonRecord::whereYear('record_date', $year)
        ->whereMonth('record_date', $month)
        ->sum('total_emission');

        $topDepartments = CarbonRecord::join(
        'users',
        'carbon_records.user_id',
        '=',
        'users.id'
            )
            ->select(
                'users.department',
                DB::raw('SUM(carbon_records.total_emission) as total_emissions')
            )

            ->whereYear('carbon_records.record_date', $year)
            ->whereMonth('carbon_records.record_date', $month)

            ->whereNotNull('users.department')
            ->groupBy('users.department')
            ->orderByDesc('total_emissions')
            ->take(5)
            ->get()
            ->map(function ($dept) use ($totalEmissions) {
                $dept->percentage = $totalEmissions > 0
                    ? round(($dept->total_emissions / $totalEmissions) * 100, 1)
                    : 0;

        return $dept;
    });
        /*
        |--------------------------------------------------------------------------
        | User Engagement
        |--------------------------------------------------------------------------
        */

        $activeUsers = CarbonRecord::whereYear('record_date', $year)
            ->whereMonth('record_date', $month)
            ->distinct('user_id')
            ->count('user_id');

        $engagementRate = $totalUsers > 0
            ? round(($activeUsers / $totalUsers) * 100)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Recent Alerts
        |--------------------------------------------------------------------------
        */

        $recentAlerts = Alert::latest()
            ->take(5)
            ->get();
        /*
        |--------------------------------------------------------------------------
        | Recommended Mitigation Strategies
        |--------------------------------------------------------------------------
        */

        $recommendedStrategies = MitigationAction::latest()
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