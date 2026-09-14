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
               {{ !request('status') ? 'text-green-700 border-b-2 border-green-700' : 'text-gray-500' }}">
                All Strategies
            </a>

            <a href="{{ route('admin.mitigation', ['status' => 'in_progress']) }}"
               class="pb-3 text-sm
               {{ request('status') == 'in_progress' ? 'text-green-700 border-b-2 border-green-700' : 'text-gray-500' }}">
                Active
            </a>

            <a href="{{ route('admin.mitigation', ['status' => 'completed']) }}"
               class="pb-3 text-sm
               {{ request('status') == 'completed' ? 'text-green-700 border-b-2 border-green-700' : 'text-gray-500' }}">
                Completed
            </a>

        </div>

        <!-- Data Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">

                <thead>
                    <tr class="border-b border-gray-200 text-gray-800 font-medium bg-white">
                        <th class="p-4 font-semibold">Strategy</th>
                        <th class="p-4 font-semibold">Created By</th>
                        <th class="p-4 font-semibold">Carbon Reduced</th>
                        <th class="p-4 font-semibold">Status</th>
                        <th class="p-4 font-semibold">Completed</th>
                        <th class="p-4 font-semibold text-center">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 text-gray-700">

                @forelse($strategies as $strategy)

                    @php
                        $isCompleted = $strategy->status === 'completed';

                        $badgeClasses = $isCompleted
                            ? 'bg-[#bbf7d0] text-[#166534]'
                            : 'bg-[#fef3c7] text-[#92400e]';
                    @endphp

                    <tr class="hover:bg-gray-50/70 transition-colors">

                        <!-- Strategy -->
                        <td class="p-4 font-medium max-w-md text-gray-900">
                            {{ $strategy->title }}

                            @if($strategy->description)
                                <p class="text-xs text-gray-400 font-normal mt-1">
                                    {{ $strategy->description }}
                                </p>
                            @endif
                        </td>

                        <!-- Created By -->
                        <td class="p-4 text-gray-600">
                            @if($strategy->user)
                                <div>
                                    <p class="font-medium text-gray-800">
                                        {{ $strategy->user->name }}
                                    </p>

                                    <p class="text-xs text-gray-400">
                                        {{ $strategy->user->email }}
                                    </p>
                                </div>
                            @else
                                <span class="text-gray-400">
                                    Unknown user
                                </span>
                            @endif
                        </td>

                        <!-- Carbon Reduced -->
                        <td class="p-4 text-gray-600">
                            {{ number_format((float) $strategy->carbon_reduced, 2) }}
                        </td>

                        <!-- Status -->
                        <td class="p-4">
                            <span class="px-3 py-1 rounded-md text-xs font-semibold tracking-wide {{ $badgeClasses }}">
                                {{ ucfirst(str_replace('_', ' ', $strategy->status)) }}
                            </span>
                        </td>

                        <!-- Completed Date -->
                        <td class="p-4 text-gray-600">
                            @if($strategy->completed_at)
                                {{ $strategy->completed_at->format('M d, Y') }}
                            @else
                                <span class="text-gray-400">
                                    —
                                </span>
                            @endif
                        </td>

                        <!-- Actions -->
                        <td class="p-4 text-center">
                            <button
                                type="button"
                                class="text-gray-400 hover:text-gray-700 text-lg p-1 transition rounded">
                                &#8942;
                            </button>
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="6" class="p-10 text-center text-gray-400">
                            No mitigation actions found.
                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>
        </div>

        <div class="h-6 bg-white border-t border-gray-100"></div>

    </div>
</div>


<!-- Add Strategy Modal -->
<div
    id="addModal"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">

    <form
        method="POST"
        action="{{ route('admin.mitigation.store') }}"
        class="bg-white rounded-xl p-6 w-[450px] space-y-4">

        @csrf

        <h2 class="font-bold text-lg">
            Add Strategy
        </h2>

        <!-- Title -->
        <input
            name="title"
            placeholder="Strategy Title"
            required
            class="border p-2 rounded w-full">

        <!-- Description -->
        <textarea
            name="description"
            placeholder="Description"
            rows="4"
            class="border p-2 rounded w-full"></textarea>

        <!-- Carbon Reduced -->
        <input
            type="number"
            name="carbon_reduced"
            placeholder="Carbon Reduced"
            step="0.01"
            min="0"
            required
            class="border p-2 rounded w-full">

        <!-- Status -->
        <select
            name="status"
            required
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

        <!-- Completed Date -->
        <input
            type="date"
            name="completed_at"
            class="border p-2 rounded w-full">

        <!-- Buttons -->
        <div class="flex justify-end gap-3">

            <button
                type="button"
                onclick="document.getElementById('addModal').classList.add('hidden')"
                class="px-4 py-2 text-gray-600">

                Cancel

            </button>

            <button
                type="submit"
                class="bg-green-700 text-white px-4 py-2 rounded">

                Save

            </button>

        </div>

    </form>
</div>

@endsection