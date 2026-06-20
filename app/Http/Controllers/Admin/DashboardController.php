<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        // Total registered users
        $totalUsers = User::count();

        // These will use real tables once we create them
        $totalEmissions = 0;
        $averageEmission = 0;
        $mitigationCount = 0;
        $reportCount = 0;

        return view('admin.dashboard', compact(
            'totalUsers',
            'totalEmissions',
            'averageEmission',
            'mitigationCount',
            'reportCount'
        ));
    }
}