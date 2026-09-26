<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /* Get campus carbon emission rankings for a specific month. */
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

    /* Get department carbon emission rankings for a specific month. */
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

    /* Get the logged-in user's eco-friendly standing. */
public function myPeerComparison(Request $request)
{
    try {
        $user = $request->user();

        // Match the web calculation:
        // Current date + previous 6 days = 7-day period.
        $startDate = now()->subDays(6)->toDateString();
        $endDate = now()->toDateString();

        // Get total emissions for every user during the last 7 days.
        $userTotals = DB::connection('neon')
            ->table('carbon_records')
            ->select(
                'user_id',
                DB::raw('SUM(total_emission) as total')
            )
            ->whereBetween('record_date', [$startDate, $endDate])
            ->groupBy('user_id')
            ->get();

        // Calculate the logged-in user's total emissions.
        $userTotal = $userTotals
            ->firstWhere('user_id', $user->id);

        $userTotalWeekEmissions = $userTotal
            ? (float) $userTotal->total
            : 0.0;

        // If the logged-in user has no records yet,
        // their total is treated as zero, matching the
        // web-side fallback behavior.
        if (!$userTotal) {
            $userTotals->push((object) [
                'user_id' => $user->id,
                'total' => $userTotalWeekEmissions,
            ]);
        }

        $totalTrackedUsers = $userTotals->count();

        // Only one tracked user.
        if ($totalTrackedUsers <= 1) {
            return response()->json([
                'period' => 'weekly',
                'participant_count' => $totalTrackedUsers,
                'weekly_emissions' => $userTotalWeekEmissions,
                'top_percentage' => 10,
                'standing' => 'Top 10%',
            ]);
        }

        // Count users with a HIGHER carbon footprint.
        // Lower emissions = better eco-friendly standing.
        $higherFootprintCount = 0;

        foreach ($userTotals as $row) {
            if (
                (int) $row->user_id !== (int) $user->id &&
                (float) $row->total > $userTotalWeekEmissions
            ) {
                $higherFootprintCount++;
            }
        }

        // Match the web calculation exactly.
        $percentile = (
            $higherFootprintCount /
            ($totalTrackedUsers - 1)
        ) * 100;

        if ($percentile >= 90) {
            $standing = 'Top 10%';
            $topPercentage = 10;
        } elseif ($percentile >= 75) {
            $standing = 'Top 25%';
            $topPercentage = 25;
        } elseif ($percentile >= 50) {
            $standing = 'Top 50%';
            $topPercentage = 50;
        } else {
            $standing = 'Top 75%';
            $topPercentage = 75;
        }

        return response()->json([
            'period' => 'weekly',
            'participant_count' => $totalTrackedUsers,
            'weekly_emissions' => $userTotalWeekEmissions,
            'top_percentage' => $topPercentage,
            'standing' => $standing,
        ]);

    } catch (\Throwable $e) {
        return response()->json([
            'message' => 'Failed to load eco-friendly standing.',
        ], 500);
    }
}

}