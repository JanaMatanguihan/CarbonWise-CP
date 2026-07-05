<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\MitigationStrategy;


class MitigationStrategyController extends Controller
{

    public function index()
    {


        /*
        |--------------------------------------------------------------------------
        | Get Strategies
        |--------------------------------------------------------------------------
        */


        $strategies = MitigationStrategy::latest()
            ->get();



        /*
        |--------------------------------------------------------------------------
        | Status Counts
        |--------------------------------------------------------------------------
        */


        $active = MitigationStrategy::where(
            'status',
            'in_progress'
        )
        ->count();



        $completed = MitigationStrategy::where(
            'status',
            'completed'
        )
        ->count();



        /*
        |--------------------------------------------------------------------------
        | Send Data
        |--------------------------------------------------------------------------
        */


        return view(

            'admin.mitigation-strategies',

            compact(

                'strategies',

                'active',

                'completed'

            )

        );


    }

}