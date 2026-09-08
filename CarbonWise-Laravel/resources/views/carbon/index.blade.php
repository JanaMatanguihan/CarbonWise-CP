@extends('layouts.admin')

@section('content')

<div class="max-w-6xl mx-auto bg-white p-6 rounded-xl shadow">

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">
            My Carbon Records
        </h2>

        <a href="{{ route('carbon.create') }}"
           class="bg-green-700 text-white px-4 py-2 rounded-lg">
            + Add Record
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <table class="w-full border-collapse border">
        <thead class="bg-gray-100">
            <tr>
                <th class="border p-2">Date</th>
                <th class="border p-2">Transportation</th>
                <th class="border p-2">Electricity</th>
                <th class="border p-2">Food</th>
                <th class="border p-2">Waste</th>
                <th class="border p-2">Total</th>
            </tr>
        </thead>

        <tbody>
            @forelse($records as $record)
                <tr>
                    <td class="border p-2">{{ $record->record_date }}</td>
                    <td class="border p-2">{{ $record->transportation }}</td>
                    <td class="border p-2">{{ $record->electricity }}</td>
                    <td class="border p-2">{{ $record->food }}</td>
                    <td class="border p-2">{{ $record->waste }}</td>
                    <td class="border p-2 font-semibold">
                        {{ $record->total_emission }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="border p-4 text-center text-gray-500">
                        No carbon records found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</div>

@endsection