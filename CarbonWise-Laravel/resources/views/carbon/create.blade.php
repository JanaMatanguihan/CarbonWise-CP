@extends('layouts.admin')

@section('content')

<div class="max-w-3xl mx-auto bg-white p-6 rounded-xl shadow">

    <h2 class="text-2xl font-bold mb-6">
        Add Carbon Record
    </h2>

    <form action="{{ route('carbon.store') }}" method="POST">
        @csrf

        <div class="mb-4">
            <label class="block mb-1">Transportation (kg CO₂e)</label>
            <input type="number" step="0.01" name="transportation"
                class="w-full border rounded p-2" required>
        </div>

        <div class="mb-4">
            <label class="block mb-1">Electricity (kg CO₂e)</label>
            <input type="number" step="0.01" name="electricity"
                class="w-full border rounded p-2" required>
        </div>

        <div class="mb-4">
            <label class="block mb-1">Food (kg CO₂e)</label>
            <input type="number" step="0.01" name="food"
                class="w-full border rounded p-2" required>
        </div>

        <div class="mb-4">
            <label class="block mb-1">Waste (kg CO₂e)</label>
            <input type="number" step="0.01" name="waste"
                class="w-full border rounded p-2" required>
        </div>

        <div class="mb-6">
            <label class="block mb-1">Record Date</label>
            <input type="date" name="record_date"
                class="w-full border rounded p-2" required>
        </div>

        <button
            type="submit"
            class="bg-green-700 text-white px-6 py-2 rounded-lg hover:bg-green-800">
            Save Carbon Record
        </button>

    </form>

</div>

@endsection