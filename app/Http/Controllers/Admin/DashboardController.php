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
        /*
        |--------------------------------------------------------------------------
        | Dashboard Summary Cards
        |--------------------------------------------------------------------------
        */

        // Total registered users
        $totalUsers = User::count();

        // Total carbon emissions
        $totalEmissions = CarbonRecord::sum('total_emission');

        // Average emission per user
        $averageEmission = $totalUsers > 0
            ? $totalEmissions / $totalUsers
            : 0;

        // Total mitigation actions
        $mitigationCount = MitigationAction::count();

        // Total SDO reports
        $reportCount = SdoReport::count();

        /*
        |--------------------------------------------------------------------------
        | Emission by Source
        |--------------------------------------------------------------------------
        */

        $transportationTotal = CarbonRecord::sum('transportation');
        $electricityTotal    = CarbonRecord::sum('electricity');
        $foodTotal           = CarbonRecord::sum('food');
        $wasteTotal          = CarbonRecord::sum('waste');

        /*
        |--------------------------------------------------------------------------
        | Monthly Emissions Trend (Current Year)
        |--------------------------------------------------------------------------
        */

        $monthlyEmissions = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthlyEmissions[] = CarbonRecord::whereYear(
                    'record_date',
                    now()->year
                )
                ->whereMonth('record_date', $month)
                ->sum('total_emission');
        }

        /*
    /*
        |--------------------------------------------------------------------------
        | Top Emitting Departments
        |--------------------------------------------------------------------------
        */

        $totalEmissions = CarbonRecord::sum('total_emission');

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

        $activeUsers = CarbonRecord::distinct('user_id')->count('user_id');

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

        $recommendedStrategies = MitigationAction::where('status', 'completed')
        ->latest()
        ->take(5)
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