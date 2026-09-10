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
                'users.department',
                DB::raw('SUM(carbon_records.total_emission) as total_emission'),
                DB::raw('COUNT(carbon_records.id) as total_records')
            )
            ->whereYear('carbon_records.record_date', $year)
            ->whereMonth('carbon_records.record_date', $monthNumber)
            ->whereNotNull('users.department')
            ->where('users.department', '!=', '')
            ->groupBy('users.department')
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
     * Get the logged-in user's peer comparison.
     */
    public function myPeerComparison(Request $request)
    {
        try {
            $user = $request->user();

            $period = $request->query('period', 'monthly');

            if (!in_array($period, ['weekly', 'monthly'])) {
                return response()->json([
                    'message' => 'Invalid period. Use weekly or monthly.',
                ], 400);
            }

            // Only compare users who have carbon records
            // during the selected period.
            if ($period === 'weekly') {
                $startDate = now()->startOfWeek()->toDateString();
                $endDate = now()->endOfWeek()->toDateString();
            } else {
                $startDate = now()->startOfMonth()->toDateString();
                $endDate = now()->endOfMonth()->toDateString();
            }

            // Get all participating users who have the
            // same campus AND same role as the logged-in user.
            $rankings = DB::connection('neon')
                ->table('carbon_records')
                ->join(
                    'users',
                    'carbon_records.user_id',
                    '=',
                    'users.id'
                )
                ->select(
                    'users.id',
                    DB::raw('SUM(carbon_records.total_emission) as total_emission')
                )
                ->where('users.campus', $user->campus)
                ->where('users.role', $user->role)
                ->whereBetween(
                    'carbon_records.record_date',
                    [$startDate, $endDate]
                )
                ->groupBy('users.id')
                ->orderBy('total_emission', 'asc')
                ->get();

            $totalUsers = $rankings->count();

            // The user has no records during this period.
            if ($totalUsers === 0) {
                return response()->json([
                    'period' => $period,
                    'campus' => $user->campus,
                    'role' => $user->role,
                    'participant_count' => 0,
                    'top_percentage' => null,
                    'message' => 'No peer comparison is available yet.',
                ]);
            }

            // Find the logged-in user's position.
            $userIndex = $rankings->search(function ($ranking) use ($user) {
                return (int) $ranking->id === (int) $user->id;
            });

            // This should normally never happen because the query
            // only contains users with carbon records.
            if ($userIndex === false) {
                return response()->json([
                    'period' => $period,
                    'campus' => $user->campus,
                    'role' => $user->role,
                    'participant_count' => $totalUsers,
                    'top_percentage' => null,
                    'message' => 'Record emissions to see your peer comparison.',
                ]);
            }

            // Position starts at 1.
            $rank = $userIndex + 1;

            // Lower emissions = better.
            // Convert the user's position into a "top X%" value.
            $topPercentage = (int) ceil(
                ($rank / $totalUsers) * 100
            );

            return response()->json([
                'period' => $period,
                'campus' => $user->campus,
                'role' => $user->role,
                'participant_count' => $totalUsers,
                'top_percentage' => $topPercentage,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to load peer comparison.',
            ], 500);
        }
    }

}