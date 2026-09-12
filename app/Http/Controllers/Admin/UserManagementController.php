<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CarbonRecord;
use App\Models\MitigationStrategy;
use App\Services\AlertService;
use App\Services\GreenPointService;
use App\Services\StreakService;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    // User Management

    public function index()
    {
        $query = User::query();

        // Get available roles
        $roles = User::select('role')
            ->whereNotNull('role')
            ->where('role', '!=', 'admin')
            ->distinct()
            ->orderBy('role')
            ->pluck('role');

        // Get available departments
        $departments = User::select('department')
            ->whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        // Search
        if (request('search')) {
            $search = request('search');

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%');
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

        // Filter by Status
        if (request('status')) {
            $query->where('status', request('status'));
        }

        $users = $query
            ->orderBy('created_at', 'desc')
            ->paginate(8)
            ->withQueryString();

        return view(
            'admin.user-management',
            compact('users', 'roles', 'departments')
        );
    }

    // Show User

    public function show(string $g_suite)
    {
        $user = User::where('email', $g_suite)->firstOrFail();

        // Total emissions
        $totalEmissions = CarbonRecord::where('user_id', $user->id)
            ->sum('total_emission');

        // Total records
        $totalRecords = CarbonRecord::where('user_id', $user->id)
            ->count();

        // This month's emissions
        $thisMonthEmission = CarbonRecord::where('user_id', $user->id)
            ->whereYear('record_date', now()->year)
            ->whereMonth('record_date', now()->month)
            ->sum('total_emission');

        // Average emission per day
        $daysTracked = CarbonRecord::where('user_id', $user->id)
            ->distinct('record_date')
            ->count('record_date');

        $averagePerDay = $daysTracked > 0
            ? round($totalEmissions / $daysTracked, 2)
            : 0;

        // Completed mitigation actions
        $mitigationActions = MitigationStrategy::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        // Emission history
        $history = CarbonRecord::where('user_id', $user->id)
            ->orderBy('record_date')
            ->get();

        $emissionHistory = [];

        if ($history->count() > 0) {
            foreach ($history as $record) {
                $emissionHistory[] = [
                    'date' => \Carbon\Carbon::parse($record->record_date)
                        ->format('M j'),
                    'value' => $record->total_emission,
                ];
            }
        } else {
            for ($i = 6; $i >= 0; $i--) {
                $emissionHistory[] = [
                    'date' => now()->subDays($i)->format('M j'),
                    'value' => 0,
                ];
            }
        }

        // Emission Categories
        $transportation = CarbonRecord::where('user_id', $user->id)
            ->sum('transportation');

        $electricity = CarbonRecord::where('user_id', $user->id)
            ->sum('electricity');

        $food = CarbonRecord::where('user_id', $user->id)
            ->sum('food');

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
                'food'
            )
        );
    }

    // Edit User

    public function edit(string $g_suite)
    {
        $user = User::where('email', $g_suite)->firstOrFail();

        return view(
            'admin.edit-user',
            compact('user')
        );
    }

    // Update User

    public function update(Request $request, string $g_suite)
    {
        $user = User::where('email', $g_suite)->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'department' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'status' => 'required|string|max:255',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'department' => $request->department,
            'role' => $request->role,
            'status' => $request->status,
        ]);

        AlertService::create(
            'User Updated',
            $user->name . ' profile has been updated.',
            'info'
        );

        return redirect()
            ->route('admin.users.show', $user->g_suite)
            ->with('success', 'User updated successfully.');
    }

    // Delete User

    public function destroy(string $g_suite)
    {
        $user = User::where('email', $g_suite)->firstOrFail();

        $user->delete();

        return redirect()
            ->route('admin.users')
            ->with('success', 'User deleted successfully.');
    }

    // Carbon Records

    public function carbonRecords(string $g_suite)
    {
        $user = User::where('email', $g_suite)->firstOrFail();

        $records = CarbonRecord::where('user_id', $user->id)
            ->orderBy('record_date', 'desc')
            ->simplePaginate(5);

        return view(
            'admin.user-carbon-records',
            compact(
                'user',
                'records'
            )
        );
    }

    // User Badges

    public function badges(
        GreenPointService $greenPointService,
        StreakService $streakService,
        string $g_suite
    ) {
        $user = User::where('email', $g_suite)->firstOrFail();

        $greenPoints = $greenPointService->calculate($g_suite);

        $currentStreak = $streakService->calculate($g_suite);

        $weekActivity = $streakService->getCurrentWeekActivity($g_suite);

        return view(
            'admin.user-badges',
            compact(
                'user',
                'greenPoints',
                'currentStreak',
                'weekActivity'
            )
        );
    }
}