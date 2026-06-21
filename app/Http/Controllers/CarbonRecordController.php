<?php

namespace App\Http\Controllers;

use App\Models\CarbonRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CarbonRecordController extends Controller
{
    // Show all carbon records of the logged-in user
    public function index()
    {
        $records = CarbonRecord::where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('carbon.index', compact('records'));
    }

    // Show the Add Carbon Record form
    public function create()
    {
        return view('carbon.create');
    }

    // Save a new carbon record
    public function store(Request $request)
    {
        $validated = $request->validate([
            'transportation' => 'required|numeric|min:0',
            'electricity' => 'required|numeric|min:0',
            'food' => 'required|numeric|min:0',
            'waste' => 'required|numeric|min:0',
            'record_date' => 'required|date',
        ]);

        $validated['user_id'] = Auth::id();

        $validated['total_emission'] =
            $validated['transportation'] +
            $validated['electricity'] +
            $validated['food'] +
            $validated['waste'];

        CarbonRecord::create($validated);

        return redirect()
            ->route('carbon.index')
            ->with('success', 'Carbon record added successfully.');
    }
}