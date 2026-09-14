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
        $strategies = MitigationAction::with('user');

        if ($request->status) {
            $strategies->where('status', $request->status);
        }

        $strategies = $strategies
            ->latest()
            ->get();

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
            'completed_at' => ['nullable', 'date'],
        ]);

        MitigationAction::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'carbon_reduced' => $validated['carbon_reduced'],
            'status' => $validated['status'],
            'completed_at' => $validated['completed_at'] ?? null,
        ]);

        return back()->with(
            'success',
            'Mitigation action added successfully.'
        );
    }
}