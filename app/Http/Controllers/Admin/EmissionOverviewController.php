<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CarbonRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Exports\EmissionOverviewExport;
use Maatwebsite\Excel\Facades\Excel;

class EmissionOverviewController extends Controller
{
    /*
    / Emission Overview
    */

    public function index()
    {
        $records = CarbonRecord::query();

        /*
        / Selected Date
        */

        if (request('month')) {
            $selectedMonth = Carbon::createFromFormat(
                'Y-m',
                request('month')
            );

            $selectedYear = $selectedMonth->year;
            $selectedMonthNumber = $selectedMonth->month;
        } else {
            $selectedMonth = now();
            $selectedYear = now()->year;
            $selectedMonthNumber = now()->month;
        }


        /*
        / Month Filter
        */

        if (request('month')) {

            $records->whereYear(
                'record_date',
                $selectedYear
            );

            $records->whereMonth(
                'record_date',
                $selectedMonthNumber
            );
        }


        /*
        / Department Filter
        */

        if (request('department')) {

            $records->whereIn(
                'user_id',
                User::where(
                    'department',
                    request('department')
                )->pluck('id')
            );
        }


        /*
        / Summary
        */

        $totalEmissions = (clone $records)
            ->sum('total_emission');

        $transportation = (clone $records)
            ->sum('transportation');

        $electricity = (clone $records)
            ->sum('electricity');

        $food = (clone $records)
            ->sum('food');


        /*
        / Percentages
        */

        if ($totalEmissions > 0) {

            $transportationPercentage =
                ($transportation / $totalEmissions) * 100;

            $electricityPercentage =
                ($electricity / $totalEmissions) * 100;

            $foodPercentage =
                ($food / $totalEmissions) * 100;

        } else {

            $transportationPercentage = 0;
            $electricityPercentage = 0;
            $foodPercentage = 0;

        }


        /*
        / Daily Trend
        */

        $dailyTrend = (clone $records)
            ->select(
                DB::raw("DATE(record_date) as day"),
                DB::raw("TO_CHAR(DATE(record_date), 'Mon DD') as label"),
                DB::raw("SUM(transportation) as transportation"),
                DB::raw("SUM(electricity) as electricity"),
                DB::raw("SUM(food) as food"),
                DB::raw("SUM(total_emission) as total")
            )
            ->groupBy(
                DB::raw("DATE(record_date)")
            )
            ->orderBy(
                DB::raw("DATE(record_date)")
            )
            ->get();


        /*
        / Weekly Trend
        */

        $weeklyTrend = CarbonRecord::query()
            ->whereYear(
                'record_date',
                $selectedYear
            )
            ->whereMonth(
                'record_date',
                $selectedMonthNumber
            )
            ->when(
                request('department'),
                function ($query) {
                    $query->whereIn(
                        'user_id',
                        User::where(
                            'department',
                            request('department')
                        )->pluck('id')
                    );
                }
            )
            ->select(

                DB::raw("
                    'Week ' ||
                    (
                        FLOOR(
                            (EXTRACT(DAY FROM record_date) - 1) / 7
                        ) + 1
                    )::int as label
                "),

                DB::raw("SUM(transportation) as transportation"),

                DB::raw("SUM(electricity) as electricity"),

                DB::raw("SUM(food) as food"),

                DB::raw("SUM(total_emission) as total")
            )
            ->groupBy(
                DB::raw("
                    FLOOR(
                        (EXTRACT(DAY FROM record_date) - 1) / 7
                    )
                ")
            )
            ->orderBy(
                DB::raw("
                    FLOOR(
                        (EXTRACT(DAY FROM record_date) - 1) / 7
                    )
                ")
            )
            ->get();


        /*
        / Monthly Trend
        */

        $monthlyTrend = CarbonRecord::query()
            ->whereYear(
                'record_date',
                $selectedYear
            )
            ->when(
                request('department'),
                function ($query) {

                    $query->whereIn(
                        'user_id',
                        User::where(
                            'department',
                            request('department')
                        )->pluck('id')
                    );

                }
            )
            ->select(

                DB::raw(
                    "EXTRACT(MONTH FROM record_date) as month"
                ),

                DB::raw(
                    "TO_CHAR(record_date, 'Mon') as label"
                ),

                DB::raw(
                    "SUM(transportation) as transportation"
                ),

                DB::raw(
                    "SUM(electricity) as electricity"
                ),

                DB::raw(
                    "SUM(food) as food"
                ),

                DB::raw(
                    "SUM(total_emission) as total"
                )
            )
            ->groupBy(
                DB::raw(
                    "EXTRACT(MONTH FROM record_date)"
                ),
                DB::raw(
                    "TO_CHAR(record_date, 'Mon')"
                )
            )
            ->orderBy(
                DB::raw(
                    "EXTRACT(MONTH FROM record_date)"
                )
            )
            ->get();


        /*
        / Yearly Trend
        */

        $yearlyTrend = CarbonRecord::query()
            ->when(
                request('department'),
                function ($query) {

                    $query->whereIn(
                        'user_id',
                        User::where(
                            'department',
                            request('department')
                        )->pluck('id')
                    );

                }
            )
            ->select(

                DB::raw(
                    "EXTRACT(YEAR FROM record_date) as year"
                ),

                DB::raw(
                    "EXTRACT(YEAR FROM record_date)::text as label"
                ),

                DB::raw(
                    "SUM(transportation) as transportation"
                ),

                DB::raw(
                    "SUM(electricity) as electricity"
                ),

                DB::raw(
                    "SUM(food) as food"
                ),

                DB::raw(
                    "SUM(total_emission) as total"
                )
            )
            ->groupBy(
                DB::raw(
                    "EXTRACT(YEAR FROM record_date)"
                )
            )
            ->orderBy(
                DB::raw(
                    "EXTRACT(YEAR FROM record_date)"
                )
            )
            ->get();


        /*
        / Department Breakdown
        */

        $departmentQuery = DB::table('carbon_records')
            ->join(
                'users',
                'carbon_records.user_id',
                '=',
                'users.id'
            );


        /*
        / Department Month Filter
        */

        if (request('month')) {

            $departmentQuery
                ->whereYear(
                    'carbon_records.record_date',
                    $selectedYear
                )
                ->whereMonth(
                    'carbon_records.record_date',
                    $selectedMonthNumber
                );
        }


        /*
        / Department Filter
        */

        if (request('department')) {

            $departmentQuery->where(
                'users.department',
                request('department')
            );
        }


        $departmentEmissions = $departmentQuery
            ->select(
                'users.department',
                DB::raw(
                    'SUM(carbon_records.total_emission) as total'
                )
            )
            ->whereNotNull('users.department')
            ->groupBy('users.department')
            ->orderByDesc('total')
            ->get();


        foreach ($departmentEmissions as $department) {

            $department->percentage =
                $totalEmissions > 0
                    ? ($department->total / $totalEmissions) * 100
                    : 0;
        }


        /*
        / Emissions Comparison
        */

        $currentMonth = CarbonRecord::query()
            ->whereYear(
                'record_date',
                $selectedMonth->year
            )
            ->whereMonth(
                'record_date',
                $selectedMonth->month
            );


        /*
        / Previous Month
        */

        $previousMonth = $selectedMonth
            ->copy()
            ->subMonth();


        $lastMonth = CarbonRecord::query()
            ->whereYear(
                'record_date',
                $previousMonth->year
            )
            ->whereMonth(
                'record_date',
                $previousMonth->month
            );


        /*
        / Department Filter for Comparison
        */

        if (request('department')) {

            $userIds = User::where(
                'department',
                request('department')
            )->pluck('id');


            $currentMonth->whereIn(
                'user_id',
                $userIds
            );

            $lastMonth->whereIn(
                'user_id',
                $userIds
            );
        }


        /*
        / Comparison Data
        */

        $comparisonData = [

            'current' => [

                (float) (clone $currentMonth)
                    ->sum('transportation'),

                (float) (clone $currentMonth)
                    ->sum('electricity'),

                (float) (clone $currentMonth)
                    ->sum('food'),

            ],

            'last' => [

                (float) (clone $lastMonth)
                    ->sum('transportation'),

                (float) (clone $lastMonth)
                    ->sum('electricity'),

                (float) (clone $lastMonth)
                    ->sum('food'),

            ]

        ];


        /*
        / Departments Dropdown
        */

        $departments = User::whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');


        /*
        / Render View
        */

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

                'yearlyTrend',

                'weeklyTrend',

                'monthlyTrend',

                'departmentEmissions',

                'departments',

                'comparisonData'

            )
        );
    }


    /*
    / Export Emissions Report
    */

    public function export()
    {
        return Excel::download(

            new EmissionOverviewExport(
                request('month'),
                request('department')
            ),

            'Emission_Overview_Report.xlsx'
        );
    }
}