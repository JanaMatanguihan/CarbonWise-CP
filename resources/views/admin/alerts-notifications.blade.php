@extends('layouts.admin')

@section('page-title', 'Alerts & Notifications')
@section('page-subtitle', 'Monitor system alerts and notifications')

@section('content')

<div class="bg-[#f1f1ee] min-h-screen p-6 -mx-6 -mt-6 space-y-6">

    {{-- Summary Cards --}}
    <div class="grid grid-cols-4 gap-6">

       <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 flex items-center gap-4">

                <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center">

                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="w-7 h-7 text-green-700"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor">

                        <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118
                                14.158V11a6.002 6.002 0 00-4-5.659V4a2
                                2 0 10-4 0v1.341C7.67 6.165 6
                                8.388 6 11v3.159c0 .538-.214
                                1.055-.595 1.436L4 17h5m6
                                0a3 3 0 11-6 0h6z"/>
                    </svg>

                </div>

                <div>
                    <p class="text-sm text-gray-500">Total Alerts</p>

                    <h2 class="text-4xl font-bold">
                        {{ number_format($totalAlerts) }}
                    </h2>
                </div>

            </div>

              <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 flex items-center gap-4">

                    <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center">

                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="w-7 h-7 text-blue-700"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor">

                            <path stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 8l9 6 9-6M5 19h14a2 2 0
                                002-2V7a2 2 0 00-2-2H5a2 2 0
                                00-2 2v10a2 2 0 002 2z"/>
                        </svg>

                    </div>

                    <div>

                        <p class="text-sm text-gray-500">Unread Alerts</p>

                        <h2 class="text-4xl font-bold text-blue-600">

                            {{ number_format($unreadAlerts) }}

                        </h2>

                    </div>

                </div>

                   <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 flex items-center gap-4">

                <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center">

                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="w-7 h-7 text-red-600"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor">

                        <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>

                    </svg>

                </div>

                <div>

                    <p class="text-sm text-gray-500">
                        Critical Alerts
                    </p>

                    <h2 class="text-4xl font-bold text-red-600">
                        {{ number_format($criticalAlerts) }}
                    </h2>

                </div>

            </div>

                   <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 flex items-center gap-4">

                        <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-7 h-7 text-green-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor">

                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>

                            </svg>

                        </div>

                        <div>

                            <p class="text-sm text-gray-500">
                                Resolved
                            </p>

                            <h2 class="text-4xl font-bold text-green-600">
                                {{ number_format($resolvedAlerts) }}
                            </h2>

                        </div>

                    </div>

                </div>

    {{-- Search --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">

        <form method="GET">

            <div class="grid grid-cols-4 gap-4">

                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search alerts..."
                    class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-700 focus:outline-none"
                >
                <select
                    name="severity"
                    onchange="this.form.submit()"
                    class="border border-gray-300 rounded-lg px-4 py-2"
                >

                    <option value="">All Severity</option>

                    <option value="info"
                        {{ $severity=='info' ? 'selected' : '' }}>
                        Information
                    </option>

                    <option value="warning"
                        {{ $severity=='warning' ? 'selected' : '' }}>
                        Warning
                    </option>

                    <option value="critical"
                        {{ $severity=='critical' ? 'selected' : '' }}>
                        Critical
                    </option>

                </select>

               <select
                    name="status"
                    onchange="this.form.submit()"
                    class="border border-gray-300 rounded-lg px-4 py-2"
                >

                    <option value="">All Status</option>

                    <option value="unread"
                        {{ $status=='unread' ? 'selected' : '' }}>
                        Unread
                    </option>

                    <option value="read"
                        {{ $status=='read' ? 'selected' : '' }}>
                        Read
                    </option>

                </select>
                        <div>

                            <a
                                href="{{ route('admin.alerts') }}"
                                class="w-full flex items-center justify-center px-5 py-2 border rounded-lg hover:bg-gray-100"
                            >
                                Clear Filters
                            </a>

                        </div>

                                    </div>

                                </form>

                            </div>

            {{-- Alerts Table --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

            <div class="flex items-center justify-between px-6 py-5 border-b border-gray-200">

                <div>

                    <h3 class="text-xl font-semibold text-gray-800">
                        Recent Alerts
                    </h3>

            <p class="text-sm text-gray-500 mt-1">
                Latest notifications generated by the CarbonWise system.
            </p>

        </div>

        <span class="text-sm text-gray-500">
            {{ $recentAlerts->total() }} Alert(s)
        </span>

    </div>

    @if($recentAlerts->count())

        <div class="overflow-x-auto">

            <table class="min-w-full">

                <thead class="bg-gray-50 border-b border-gray-200">

                    <tr>

                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">
                            Alert
                        </th>

                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">
                            Severity
                        </th>

                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">
                            Status
                        </th>

                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">
                            Date
                        </th>

                        <th class="px-6 py-4 text-center text-sm font-semibold text-gray-700">
                            Actions
                        </th>

                    </tr>

                </thead>

                <tbody class="divide-y divide-gray-100">

                    @foreach($recentAlerts as $alert)

                        <tr class="hover:bg-gray-50 transition">

                            <td class="px-6 py-5">

                                <div class="flex items-start gap-4">

                                    <div class="mt-1">

                                        @if($alert->severity == 'critical')

                                            <div class="w-3 h-12 rounded-full bg-red-500"></div>

                                        @elseif($alert->severity == 'warning')

                                            <div class="w-3 h-12 rounded-full bg-yellow-500"></div>

                                        @else

                                            <div class="w-3 h-12 rounded-full bg-blue-500"></div>

                                        @endif

                                    </div>

                                    <div>

                                        <div class="font-semibold text-gray-800">

                                            {{ $alert->title }}

                                        </div>

                                        <div class="text-sm text-gray-500 mt-1">

                                            {{ $alert->message }}

                                        </div>

                                    </div>

                                </div>

                            </td>

                            <td class="px-6">

                                @if($alert->severity=='critical')

                                    <span class="px-3 py-1 rounded-full text-xs bg-red-100 text-red-700 font-medium">

                                        Critical

                                    </span>

                                @elseif($alert->severity=='warning')

                                    <span class="px-3 py-1 rounded-full text-xs bg-yellow-100 text-yellow-700 font-medium">

                                        Warning

                                    </span>

                                @else

                                    <span class="px-3 py-1 rounded-full text-xs bg-blue-100 text-blue-700 font-medium">

                                        Information

                                    </span>

                                @endif

                            </td>

                            <td class="px-6">

                                @if($alert->is_read)

                                    <span class="px-3 py-1 rounded-full text-xs bg-gray-100 text-gray-700">

                                        Read

                                    </span>

                                @else

                                    <span class="px-3 py-1 rounded-full text-xs bg-green-100 text-green-700">

                                        New

                                    </span>

                                @endif

                            </td>

                            <td class="px-6 text-sm text-gray-500">

                                {{ optional($alert->created_at)->format('M d, Y h:i A') ?? 'N/A' }}

                            </td>

                            <td class="px-6">

                                    <div class="flex justify-center gap-2">

                                        @if(!$alert->is_read)

                                            <form
                                                method="POST"
                                                action="{{ route('admin.alerts.read', $alert) }}"
                                            >

                                                @csrf
                                                @method('PATCH')

                                                <button
                                                    type="submit"
                                                    class="px-3 py-1 text-xs rounded-md bg-blue-100 text-blue-700 hover:bg-blue-700 hover:text-white transition"
                                                >
                                                    Mark Read
                                                </button>

                                            </form>

                                        @endif

                                        <form
                                            method="POST"
                                            action="{{ route('admin.alerts.destroy', $alert) }}"
                                            class="delete-alert-form"
                                            >

                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="px-3 py-1 text-xs rounded-md bg-red-100 text-red-700 hover:bg-red-700 hover:text-white transition"
                                            >
                                                Delete
                                            </button>

                                        </form>

                                    </div>

                                </td>
                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        <div class="border-t border-gray-200 px-6 py-4">

            {{ $recentAlerts->links() }}

        </div>

    @else

        <div class="py-20">

            <div class="flex flex-col items-center justify-center">

                <svg xmlns="http://www.w3.org/2000/svg"
                     class="w-16 h-16 text-gray-300"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke="currentColor">

                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0h6z"/>

                </svg>

                <h3 class="mt-5 text-xl font-semibold text-gray-700">

                    No alerts found

                </h3>

                <p class="mt-2 text-gray-500">

                    Alerts generated by CarbonWise will automatically appear here.

                </p>

            </div>

        </div>

    @endif

</div>

@push('scripts')

<script>

// Delete confirmation
document.querySelectorAll('.delete-alert-form').forEach(form => {

    form.addEventListener('submit', function (e) {

        e.preventDefault();

        Swal.fire({

            title: 'Delete Alert?',

            text: 'This alert will be permanently deleted.',

            icon: 'warning',

            showCancelButton: true,

            confirmButtonColor: '#15803d',

            cancelButtonColor: '#6b7280',

            confirmButtonText: 'Yes, Delete',

            cancelButtonText: 'Cancel'

        }).then((result) => {

            if (result.isConfirmed) {

                form.submit();

            }

        });

    });

});

const successMessage = "{{ session('success') }}";

if (successMessage) {
    Swal.fire({
        icon: 'success',
        title: 'Success',
        text: successMessage,
        confirmButtonColor: '#15803d',
        timer: 2200,
        showConfirmButton: false
    });
}

</script>

@endpush
@endsection