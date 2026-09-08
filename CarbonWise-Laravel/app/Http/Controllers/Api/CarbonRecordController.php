<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CarbonRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CarbonRecordController extends Controller
{
    // Get all carbon records of the logged-in user
    public function index(Request $request)
{
    Log::info('CARBON RECORD INDEX START');

    $user = $request->user();

    Log::info('CARBON RECORD USER', [
        'user_id' => $user?->id,
    ]);

    $records = CarbonRecord::where('user_id', $user->id)
        ->latest('record_date')
        ->get();

    Log::info('CARBON RECORD QUERY FINISHED', [
        'count' => $records->count(),
    ]);

    return response()->json([
        'records' => $records,
    ]);
}

    // Add a new carbon record
    public function store(Request $request)
    {
        $validated = $request->validate([
            'transportation' => ['required', 'numeric', 'min:0'],
            'electricity' => ['required', 'numeric', 'min:0'],
            'food' => ['required', 'numeric', 'min:0'],
            'record_date' => ['required', 'date'],
            'transport_item' => ['nullable', 'string'],
            'office_item' => ['nullable', 'string'],
            'food_item' => ['nullable', 'string'],
            'food_meal_period' => ['nullable', 'string', 'max:50'],
            'food_consumed_at' => ['nullable', 'date'],
        ]);

        $validated['user_id'] = $request->user()->id;

        $validated['total_emission'] =
            $validated['transportation'] +
            $validated['electricity'] +
            $validated['food'];

        $record = CarbonRecord::create($validated);

        return response()->json([
            'message' => 'Carbon record added successfully.',
            'record' => $record,
        ], 201);
    }

    // Get one carbon record
    public function show(Request $request, $id)
    {
        $record = CarbonRecord::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'record' => $record,
        ]);
    }

    // Update a carbon record
    public function update(Request $request, $id)
    {
        $record = CarbonRecord::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'transportation' => ['required', 'numeric', 'min:0'],
            'electricity' => ['required', 'numeric', 'min:0'],
            'food' => ['required', 'numeric', 'min:0'],
            'record_date' => ['required', 'date'],
            'transport_item' => ['nullable', 'string'],
            'office_item' => ['nullable', 'string'],
            'food_item' => ['nullable', 'string'],
            'food_meal_period' => ['nullable', 'string', 'max:50'],
            'food_consumed_at' => ['nullable', 'date'],
        ]);

        $validated['total_emission'] =
            $validated['transportation'] +
            $validated['electricity'] +
            $validated['food'];

        $record->update($validated);

        return response()->json([
            'message' => 'Carbon record updated successfully.',
            'record' => $record,
        ]);
    }

    // Delete a carbon record
    public function destroy(Request $request, $id)
    {
        $record = CarbonRecord::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $record->delete();

        return response()->json([
            'message' => 'Carbon record deleted successfully.',
        ]);
    }
}
