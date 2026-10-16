<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MitigationAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MitigationStrategyController extends Controller
{
    public function index(Request $request)
    {
        $query = MitigationAction::with('user');

        // Apply the selected status filter.
        if (in_array($request->query('status'), [
            'pending',
            'in_progress',
            'completed',
        ], true)) {
            $query->where('status', $request->query('status'));
        }

        // Use paginate(), not get(), for the pagination footer.
        $strategies = $query
            ->latest()
            ->paginate(8)
            ->withQueryString();

        return view(
            'admin.mitigation-strategies',
            compact('strategies')
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'carbon_reduced' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:pending,in_progress,completed'],
            'completed_at' => [
                'nullable',
                'date',
                'required_if:status,completed',
            ],
        ]);

        MitigationAction::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'carbon_reduced' => $validated['carbon_reduced'],
            'status' => $validated['status'],
            'completed_at' => $validated['completed_at'] ?? null,
        ]);

        return redirect()
            ->route('admin.mitigation', array_filter([
                'status' => $request->query('status'),
            ]))
            ->with('success', 'Mitigation action added successfully.');
    }

    public function destroy(MitigationAction $strategy)
    {
        $strategy->delete();

        return back()->with(
            'success',
            'Mitigation strategy deleted successfully.'
        );
    }
}
