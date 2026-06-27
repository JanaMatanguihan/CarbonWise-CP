<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserInfo;
use App\Models\CarbonRecord;
use App\Models\MitigationAction;

class UserManagementController extends Controller
{
    public function index()
    {
        $query = UserInfo::query();

        // Get available roles
        $roles = UserInfo::select('role')
            ->whereNotNull('role')
            ->where('role', '!=', 'admin')
            ->distinct()
            ->orderBy('role')
            ->pluck('role');

        // Get available departments
        $departments = UserInfo::select('department')
            ->whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        // Search
        if (request('search')) {
            $query->where(function ($q) {
                $q->where('full_name', 'like', '%' . request('search') . '%')
                  ->orWhere('g_suite', 'like', '%' . request('search') . '%')
                  ->orWhere('sr_code', 'like', '%' . request('search') . '%');
            });
        }

        // Filter by Role
        if (request('role')) {
            $query->where('role', request('role'));
        }

        // Filter by Department
        if (request('department')) {
            $query->where('department', request('department'));
        }

        // Filter by Campus
        if (request('campus')) {
            $query->where('campus', request('campus'));
        }

        // Filter by Status
        if (request('status')) {
            $query->where('status', request('status'));
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        return view('admin.user-management', compact('users', 'roles', 'departments'));
    }

    // NEW METHOD
                    public function show($g_suite)
            {
                
                $user = UserInfo::where('g_suite', $g_suite)->firstOrFail();

                // Total emissions
                $totalEmissions = CarbonRecord::where('g_suite', $g_suite)
                    ->sum('total_emission');

                // Total records
                $totalRecords = CarbonRecord::where('g_suite', $g_suite)
                    ->count();

                // This month's emissions
                $thisMonthEmission = CarbonRecord::where('g_suite', $g_suite)
                    ->whereYear('record_date', now()->year)
                    ->whereMonth('record_date', now()->month)
                    ->sum('total_emission');

                // Average emission per day
                $daysTracked = CarbonRecord::where('g_suite', $g_suite)
                    ->distinct('record_date')
                    ->count('record_date');

                $averagePerDay = $daysTracked > 0
                    ? round($totalEmissions / $daysTracked, 2)
                    : 0;

                $mitigationActions = MitigationAction::where('g_suite', $g_suite)
                ->where('status', 'completed')
                ->count();

                // Line Chart (Emission History)
                    $history = CarbonRecord::where('g_suite', $g_suite)
                            ->orderBy('record_date')
                            ->get();

                        $emissionHistory = [];

                        if ($history->count() > 0) {

                            foreach ($history as $record) {

                                $emissionHistory[] = [
                                    'date' => \Carbon\Carbon::parse($record->record_date)->format('M j'),
                                    'value' => $record->total_emission
                                ];

                            }

                        } else {

                            for ($i = 6; $i >= 0; $i--) {

                                $emissionHistory[] = [
                                    'date' => now()->subDays($i)->format('M j'),
                                    'value' => 0
                                ];

                            }

                        }

                    // Donut Chart (Emission Categories)
                    $transportation = CarbonRecord::where('g_suite', $g_suite)
                        ->sum('transportation');

                    $electricity = CarbonRecord::where('g_suite', $g_suite)
                        ->sum('electricity');

                    $food = CarbonRecord::where('g_suite', $g_suite)
                        ->sum('food');

                    $waste = CarbonRecord::where('g_suite', $g_suite)
                        ->sum('waste');

                return view(
                'admin.user-profile',
                compact(
                    'user',
                    'totalEmissions',
                    'totalRecords',
                    'thisMonthEmission',
                    'averagePerDay',
                    'mitigationActions',
                    'emissionHistory',
                    'transportation',
                    'electricity',
                    'food',
                    'waste'
                )
            );
            }

            public function edit($g_suite)
            {
                $user = UserInfo::where('g_suite', $g_suite)->firstOrFail();

                return view('admin.edit-user', compact('user'));
            }
}