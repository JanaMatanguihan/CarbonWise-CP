@extends('layouts.admin')

@section('page-title', 'Mitigation Strategies')
@section('page-subtitle', 'Manage and promote mitigation actions')

@section('content')
<div class="bg-[#f4f4f2] min-h-screen p-6 -mx-6 -mt-6 space-y-6">

    <!-- Header Actions -->
    <div class="flex justify-end">
        <button
            onclick="document.getElementById('addModal').classList.remove('hidden')"
            class="bg-[#2e7d32] text-white px-4 py-2 rounded-lg">

            + Add Strategy

            </button>
    </div>

    <!-- Strategy Main Container Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">

        
        <!-- Filter Tabs -->
            <div class="flex gap-8 px-6 pt-4 border-b border-gray-200">


            <a href="{{ route('admin.mitigation') }}"
            class="pb-3 text-sm 
            {{ !request('status') ? 'text-green-700 border-b-2 border-green-700' : '' }}">

            All Strategies

            </a>



            <a href="{{ route('admin.mitigation', ['status'=>'in_progress']) }}"
            class="pb-3 text-sm
            {{ request('status')=='in_progress' ? 'text-green-700 border-b-2 border-green-700' : '' }}">

            Active

            </a>



            <a href="{{ route('admin.mitigation', ['status'=>'completed']) }}"
            class="pb-3 text-sm
            {{ request('status')=='completed' ? 'text-green-700 border-b-2 border-green-700' : '' }}">

            Completed

            </a>


            </div>

        <!-- Data Table Container -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-gray-200 text-gray-800 font-medium bg-white">
                        <th class="p-4 font-semibold">Strategy</th>
                        <th class="p-4 font-semibold">Category</th>
                        <th class="p-4 font-semibold">Target Areas</th>
                        <th class="p-4 font-semibold">Participants</th>
                        <th class="p-4 font-semibold">Status</th>
                        <th class="p-4 font-semibold">Progress</th>
                        <th class="p-4 font-semibold text-center">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 text-gray-700">
                @foreach($strategies as $strategy)
                   @php

                        $progressValue = $strategy->progress;

                        // Dynamic badge customization based on strategy state
                        $isCompleted = $strategy->status === 'completed';
                        $badgeClasses = $isCompleted 
                            ? 'bg-[#bbf7d0] text-[#166534]' 
                            : 'bg-[#bbf7d0] text-[#166534]'; // matching the bright active/completed mint tone from screenshot
                    @endphp

                    <tr class="hover:bg-gray-50/70 transition-colors">
                        <!-- Strategy Title & Details -->
                        <td class="p-4 font-medium max-w-xs text-gray-900">
                            {{ $strategy->title }}
                            @if(isset($strategy->description) && $strategy->description)
                                <p class="text-xs text-gray-400 font-normal mt-0.5">{{ $strategy->description }}</p>
                            @endif
                        </td>

                        <!-- Category -->
                        <td class="p-4 text-gray-600">
                            {{ $strategy->category }}
                        </td>

                        <!-- Target Areas -->
                        <td class="p-4 text-gray-600">
                            {{ $strategy->target_areas }}
                        </td>

                        <!-- Participants Counter -->
                        <td class="p-4 text-gray-600">
                            {{ number_format($strategy->participants) }}
                        </td>

                        <!-- State Indicator Status -->
                        <td class="p-4">
                            <span class="px-3 py-1 rounded-md text-xs font-semibold tracking-wide {{ $badgeClasses }}">
                                {{ ucfirst(str_replace('_', ' ', $strategy->status)) }}
                            </span>
                        </td>

                        <!-- Advanced Progress Representation Component -->
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-28 h-2 bg-gray-100 border border-gray-200 rounded-full overflow-hidden">
                                    <div
                                        class="h-2 bg-green-700 rounded"
                                        @style([
                                            "width: {$progressValue}%"
                                        ])
                                    >
                                    </div>
                                </div>
                                <span class="text-xs font-medium text-gray-600 w-8">
                                    {{ $progressValue }}%
                                </span>
                            </div>
                        </td>

                        <!-- Actions Action Context Menu -->
                        <td class="p-4 text-center">
                            <button class="text-gray-400 hover:text-gray-700 text-lg p-1 transition rounded">
                                &#8942;
                            </button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Bottom padding strip matching table view end -->
        <div class="h-6 bg-white border-t border-gray-100"></div>
    </div>
</div>

                <!-- Add Strategy Modal -->

                <div id="addModal"
                class="hidden fixed inset-0 bg-black/40 flex items-center justify-center">


                <form method="POST"
                action="{{ route('admin.mitigation.store') }}"
                class="bg-white rounded-xl p-6 w-[450px] space-y-3">


                @csrf


                <h2 class="font-bold text-lg">
                Add Strategy
                </h2>



                <input name="title"
                placeholder="Strategy Title"
                class="border p-2 rounded w-full">


                <input name="category"
                placeholder="Category"
                class="border p-2 rounded w-full">


                <input name="target_areas"
                placeholder="Target Areas"
                class="border p-2 rounded w-full">


                <input 
                type="number"
                name="participants"
                placeholder="Participants"
                class="border p-2 rounded w-full">


                <input 
                type="number"
                name="carbon_reduced"
                placeholder="Carbon Reduced"
                class="border p-2 rounded w-full">


                <input 
                type="number"
                name="progress"
                placeholder="Progress %"
                class="border p-2 rounded w-full">


                <select name="status"
                class="border p-2 rounded w-full">

                <option value="pending">
                Pending
                </option>

                <option value="in_progress">
                Active
                </option>

                <option value="completed">
                Completed
                </option>

                </select>


                <textarea
                name="description"
                placeholder="Description"
                class="border p-2 rounded w-full">
                </textarea>



                <div class="flex justify-end gap-3">


                <button
                type="button"
                onclick="document.getElementById('addModal').classList.add('hidden')">

                Cancel

                </button>


                <button
                class="bg-green-700 text-white px-4 py-2 rounded">

                Save

                </button>


                </div>


                </form>


                </div>
@endsection