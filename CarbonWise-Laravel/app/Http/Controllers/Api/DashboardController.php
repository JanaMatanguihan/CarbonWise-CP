<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get campus carbon emission rankings for a specific month.
     */
    public function campusRankings(Request $request)
{
    try {
        $month = $request->query('month', now()->format('Y-m'));
        [$year, $monthNumber] = explode('-', $month);

        $rankings = DB::connection('neon')
            ->table('carbon_records')
            ->join('users', 'carbon_records.user_id', '=', 'users.id')
            ->select(
                'users.campus',
                DB::raw('SUM(carbon_records.total_emission) as total_emission'),
                DB::raw('COUNT(carbon_records.id) as total_records')
            )
            ->whereYear('carbon_records.record_date', $year)
            ->whereMonth('carbon_records.record_date', $monthNumber)
            ->whereNotNull('users.campus')
            ->where('users.campus', '!=', '')
            ->groupBy('users.campus')
            ->orderBy('total_emission', 'asc')
            ->get();

        return response()->json([
            'rankings' => $rankings,
        ]);
    } catch (\Throwable $e) {
    return response()->json([
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ], 500);
}
}

    /**
     * Get department carbon emission rankings for a specific month.
     */
    public function departmentRankings(Request $request)
{
    try {
        $month = $request->query('month', now()->format('Y-m'));
        [$year, $monthNumber] = explode('-', $month);

        $rankings = DB::connection('neon')
            ->table('carbon_records')
            ->join('users', 'carbon_records.user_id', '=', 'users.id')
            ->select(
                DB::raw("
                    CASE
                        WHEN users.faculty_type = 'Administrative Faculty'
                            THEN users.office
                        WHEN users.role = 'non-teaching staff'
                            THEN users.office
                        ELSE users.department
                    END as name
                "),

                DB::raw("
                    CASE
                        WHEN users.faculty_type = 'Administrative Faculty'
                            THEN 'Office'
                        WHEN users.role = 'non-teaching staff'
                            THEN 'Office'
                        ELSE 'Department'
                    END as type
                "),

                DB::raw('SUM(carbon_records.total_emission) as total_emission'),
                DB::raw('COUNT(carbon_records.id) as total_records')
            )

            ->whereYear('carbon_records.record_date', $year)
            ->whereMonth('carbon_records.record_date', $monthNumber)

            ->where(function ($query) {
                $query->where(function ($q) {
                    // Administrative Faculty → Office
                    $q->where('users.faculty_type', 'Administrative Faculty')
                        ->whereNotNull('users.office')
                        ->where('users.office', '!=', '');
                })

                ->orWhere(function ($q) {
                    // Non-Teaching Staff → Office
                    $q->where('users.role', 'non-teaching staff')
                        ->whereNotNull('users.office')
                        ->where('users.office', '!=', '');
                })

                ->orWhere(function ($q) {
                    // Students + Teaching Faculty → Department
                    $q->whereNotNull('users.department')
                        ->where('users.department', '!=', '');
                });
            })

            ->groupBy(
                DB::raw("
                    CASE
                        WHEN users.faculty_type = 'Administrative Faculty'
                            THEN users.office
                        WHEN users.role = 'non-teaching staff'
                            THEN users.office
                        ELSE users.department
                    END
                "),

                DB::raw("
                    CASE
                        WHEN users.faculty_type = 'Administrative Faculty'
                            THEN 'Office'
                        WHEN users.role = 'non-teaching staff'
                            THEN 'Office'
                        ELSE 'Department'
                    END
                ")
            )

            ->orderBy('total_emission', 'asc')
            ->get();

        return response()->json([
            'rankings' => $rankings,
        ]);

    } catch (\Throwable $e) {
        return response()->json([
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 500);
    }
}

}