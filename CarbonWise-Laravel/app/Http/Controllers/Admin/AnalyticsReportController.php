<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CarbonRecord;
use App\Models\UserInfo;
use App\Models\MitigationStrategy;
use App\Exports\AnalyticsReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class AnalyticsReportController extends Controller
{
    public function index()
    {

        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        $selectedMonth = request('month');

        $records = CarbonRecord::query();


        // Month Filter
        if ($selectedMonth) {


            $date = Carbon::parse(
                $selectedMonth . '-01'
            );


            $records
                ->whereYear(
                    'record_date',
                    $date->year
                )

                ->whereMonth(
                    'record_date',
                    $date->month
                );

        }



        /*
        |--------------------------------------------------------------------------
        | Summary Report Cards
        |--------------------------------------------------------------------------
        */


        // Total Users
        $totalUsers = UserInfo::count();



        // Total Emissions
        $totalEmissions = (clone $records)
            ->sum('total_emission');



        // Average Emission
        $averageEmission = $totalUsers > 0

            ? $totalEmissions / $totalUsers

            : 0;



        // Active Users
        $activeUsers = (clone $records)

            ->distinct('g_suite')

            ->count('g_suite');



        // Mitigation Strategies Count
        $mitigationActions = MitigationStrategy::count();





        /*
        |--------------------------------------------------------------------------
        | Top Emitting Sources
        |--------------------------------------------------------------------------
        */


        // Transportation Total
        $transportation = (clone $records)
            ->sum('transportation');



        // Electricity Total
        $electricity = (clone $records)
            ->sum('electricity');



        // Food Total
        $food = (clone $records)
            ->sum('food');




        // Source Data For Progress Bars
        $sources = [

            'Transportation' => $transportation,

            'Electricity' => $electricity,

            'Food Consumption' => $food

        ];



        // Highest value for percentage calculation
        $highestSource = max($sources);





        /*
        |--------------------------------------------------------------------------
        | Emissions Comparison Chart
        |--------------------------------------------------------------------------
        */


        // Current Month
        $currentMonth = CarbonRecord::query()

            ->whereYear(
                'record_date',
                now()->year
            )

            ->whereMonth(
                'record_date',
                now()->month
            );



        // Previous Month
        $previousMonth = now()->subMonth();



        $lastMonth = CarbonRecord::query()

            ->whereYear(
                'record_date',
                $previousMonth->year
            )

            ->whereMonth(
                'record_date',
                $previousMonth->month
            );



        // Chart Values
        $comparisonData = [


            'current' => [

                (clone $currentMonth)
                    ->sum('transportation'),


                (clone $currentMonth)
                    ->sum('electricity'),


                (clone $currentMonth)
                    ->sum('food'),

            ],



            'last' => [

                (clone $lastMonth)
                    ->sum('transportation'),


                (clone $lastMonth)
                    ->sum('electricity'),


                (clone $lastMonth)
                    ->sum('food'),



            ]

        ];





        /*
        |--------------------------------------------------------------------------
        | Return Analytics Page
        |--------------------------------------------------------------------------
        */


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





    /*
    |--------------------------------------------------------------------------
    | Export Excel Report
    |--------------------------------------------------------------------------
    */


    public function exportExcel()
    {

        return Excel::download(

            new AnalyticsReportExport,

            'carbonwise-analytics-report.xlsx'

        );

    }





    /*
    |--------------------------------------------------------------------------
    | Export PDF Report
    |--------------------------------------------------------------------------
    */


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