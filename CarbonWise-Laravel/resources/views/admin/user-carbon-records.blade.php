@extends('layouts.admin')

@section('page-title', 'User Details')
@section('page-subtitle', 'User Management > User Details')

@section('content')

<div class="grid grid-cols-12 gap-6 mt-6">

    {{-- Left Sidebar --}}
    @include('admin.partials.user-sidebar')

    {{-- Right Side --}}
    <div class="col-span-8 flex">

        <div class="bg-white rounded-xl shadow flex flex-col w-full h-full">

            {{-- Tabs --}}
            @include('admin.partials.user-tabs')

            <div class="p-8">

                <div class="flex items-center justify-between mb-6">

                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">
                            Carbon Records
                        </h2>

                        <p class="text-sm text-gray-500 mt-1">
                            View all carbon records submitted by this user.
                        </p>
                    </div>


                </div>

                <div class="bg-white border rounded-xl overflow-hidden flex flex-col h-[470px]">

                    <div class="flex-1 overflow-y-auto">

                        <table class="w-full">

                        <thead class="bg-gray-50">

                            <tr>
                                <th class="px-6 py-4 text-left">Date</th>
                                <th class="px-6 py-4 text-left">Transportation</th>
                                <th class="px-6 py-4 text-left">Electricity</th>
                                <th class="px-6 py-4 text-left">Food</th>
                                <th class="px-6 py-4 text-left">Total CO₂e</th>
                            </tr>

                        </thead>
                        <tbody>

                            @forelse($records as $record)

                                <tr class="border-t hover:bg-gray-50">

                                    <td class="px-6 py-4">
                                        {{ \Carbon\Carbon::parse($record->record_date)->format('M d, Y') }}
                                    </td>

                                    <td class="px-6 py-4">
                                        {{ number_format($record->transportation, 2) }}
                                    </td>

                                    <td class="px-6 py-4">
                                        {{ number_format($record->electricity, 2) }}
                                    </td>

                                    <td class="px-6 py-4">
                                        {{ number_format($record->food, 2) }}
                                    </td>

                                    <td class="px-6 py-4 font-semibold text-green-700">
                                        {{ number_format($record->total_emission, 2) }}
                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="5" class="text-center py-10 text-gray-500">
                                        No carbon records found.
                                    </td>

                                </tr>

                            @endforelse

                        </tbody>
                    </table>
                    </div>
                    
                     @if($records->hasPages())
                        <div class="border-t px-6 py-4 bg-white">
                            {{ $records->links() }}
                        </div>
                    @endif

                </div>

            </div>

        </div>

    </div>

</div>

@endsection