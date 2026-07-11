<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MitigationStrategy;
use Illuminate\Http\Request;


class MitigationStrategyController extends Controller
{

    public function index(Request $request)
    {


        /*
        |--------------------------------------------------------------------------
        | Strategy Filter Tabs
        |--------------------------------------------------------------------------
        */


        $strategies = MitigationStrategy::query();


        if ($request->status) {


            $strategies->where(

                'status',

                $request->status

            );


        }



        $strategies = $strategies

            ->latest()

            ->get();



        return view(

            'admin.mitigation-strategies',

            compact(

                'strategies'

            )

        );


    }





    /*
    |--------------------------------------------------------------------------
    | Store New Strategy
    |--------------------------------------------------------------------------
    */


    public function store(Request $request)
    {


        MitigationStrategy::create([


            'title' => $request->title,


            'description' => $request->description,


            'category' => $request->category,


            'target_areas' => $request->target_areas,


            'participants' => $request->participants,


            'progress' => $request->progress,


            'carbon_reduced' => $request->carbon_reduced,


            'status' => $request->status,


        ]);



        return back();


    }

}