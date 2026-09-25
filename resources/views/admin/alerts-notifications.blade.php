@extends('layouts.admin')

@section('page-title', 'Alerts & Notifications')
@section('page-subtitle', 'Monitor system alerts and notifications')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

    .alerts-page {
        font-family: 'Poppins', sans-serif;
        color: #111111;
    }

    /* Cards */
    .alerts-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.12);
    }

    /* Summary Card Label */
    .alerts-card-label {
        font-family: 'Poppins', sans-serif;
        font-size: 12px;
        line-height: 18px;
        font-weight: 500;
        color: #111111;
    }

    /* Summary Card Number */
    .alerts-card-value {
        font-family: 'Poppins', sans-serif;
        font-size: 24px;
        line-height: 32px;
        font-weight: 700;
        letter-spacing: -0.5px;
    }

    /* Summary Card Description */
    .alerts-card-subtext {
        font-family: 'Poppins', sans-serif;
        font-size: 9px;
        line-height: 14px;
        font-weight: 400;
        color: #9ca3af;
    }

    /* Summary Card Icons */
    .alerts-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .alerts-icon svg {
        width: 25px;
        height: 25px;
    }

    /* Total Alerts */
    .alerts-icon-total {
        background: #dcfce7;
        color: #2f7d57;
    }

    .alerts-value-total {
        color: #111111;
    }

    /* Unread Alerts */
    .alerts-icon-unread {
        background: #dbeafe;
        color: #2563eb;
    }

    .alerts-value-unread {
        color: #111111;
    }

    /* Critical Alerts */
    .alerts-icon-critical {
        background: #fee2e2;
        color: #dc2626;
    }

    .alerts-value-critical {
        color: #dc2626;
    }

    /* Resolved */
    .alerts-icon-resolved {
        background: #dcfce7;
        color: #16a34a;
    }

    .alerts-value-resolved {
        color: #16a34a;
    }

    /* Section Titles */
    .alerts-section-title {
        font-family: 'Poppins', sans-serif;
        font-size: 16px;
        line-height: 24px;
        font-weight: 700;
        color: #111111;
    }

    .alerts-section-description {
        font-family: 'Poppins', sans-serif;
        font-size: 10px;
        line-height: 15px;
        font-weight: 400;
        color: #6b7280;
    }

    /* Table */
    .alerts-table-header {
        font-family: 'Poppins', sans-serif;
        font-size: 11px;
        line-height: 16px;
        font-weight: 600;
        color: #374151;
    }

    .alerts-table-text {
        font-family: 'Poppins', sans-serif;
        font-size: 10px;
        line-height: 15px;
        color: #6b7280;
    }

    .alerts-table-title {
        font-family: 'Poppins', sans-serif;
        font-size: 12px;
        line-height: 18px;
        font-weight: 600;
        color: #111111;
    }

    .alerts-table-message {
        font-family: 'Poppins', sans-serif;
        font-size: 10px;
        line-height: 15px;
        color: #6b7280;
    }

    /* Filters */
    .alerts-filter {
        font-family: 'Poppins', sans-serif;
        font-size: 11px;
        line-height: 16px;
        color: #374151;
    }

    .alerts-filter::placeholder {
        color: #9ca3af;
    }

    /* Empty State */
    .alerts-empty-title {
        font-family: 'Poppins', sans-serif;
        font-size: 16px;
        line-height: 24px;
        font-weight: 600;
        color: #374151;
    }

    .alerts-empty-text {
        font-family: 'Poppins', sans-serif;
        font-size: 10px;
        line-height: 15px;
        color: #6b7280;
    }

    /* Summary Card Responsive Spacing */
    @media (max-width: 768px) {
        .alerts-icon {
            width: 44px;
            height: 44px;
        }

        .alerts-icon svg {
            width: 23px;
            height: 23px;
        }

        .alerts-card-value {
            font-size: 22px;
        }
    }
</style>

<div class="alerts-page bg-[#f1f1ee] min-h-screen p-6 -mx-6 -mt-6 space-y-6">

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">

        {{-- Total Alerts --}}
        <div class="alerts-card px-6 py-6">
            <div class="flex items-center gap-4">

                <div class="alerts-icon alerts-icon-total">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor"
                         stroke-width="1.8">

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9a6 6 0 00-12 0v.75a8.967 8.967 0 01-2.311 6.022c1.733.64 3.557 1.082 5.454 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />

                    </svg>
                </div>

                <div>
                    <p class="alerts-card-label">
                        Total Alerts
                    </p>

                    <h2 class="alerts-card-value alerts-value-total mt-2">
                        {{ number_format($totalAlerts) }}
                    </h2>

                    <p class="alerts-card-subtext mt-1">
                        All system alerts
                    </p>
                </div>

            </div>
        </div>


        {{-- Unread Alerts --}}
        <div class="alerts-card px-6 py-6">
            <div class="flex items-center gap-4">

                <div class="alerts-icon alerts-icon-unread">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor"
                         stroke-width="1.8">

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25H4.5a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0l-7.5-4.615a2.25 2.25 0 01-1.07-1.916V6.75" />

                    </svg>
                </div>

                <div>
                    <p class="alerts-card-label">
                        Unread Alerts
                    </p>

                    <h2 class="alerts-card-value alerts-value-unread mt-2">
                        {{ number_format($unreadAlerts) }}
                    </h2>

                    <p class="alerts-card-subtext mt-1">
                        New notifications
                    </p>
                </div>

            </div>
        </div>


        {{-- Critical Alerts --}}
        <div class="alerts-card px-6 py-6">
            <div class="flex items-center gap-4">

                <div class="alerts-icon alerts-icon-critical">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor"
                         stroke-width="1.8">

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M12 9v3.75m0 3.75h.007v.008H12v-.008z" />

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />

                    </svg>
                </div>

                <div>
                    <p class="alerts-card-label">
                        Critical Alerts
                    </p>

                    <h2 class="alerts-card-value alerts-value-critical mt-2">
                        {{ number_format($criticalAlerts) }}
                    </h2>

                    <p class="alerts-card-subtext mt-1">
                        Requires attention
                    </p>
                </div>

            </div>
        </div>


        {{-- Resolved --}}
        <div class="alerts-card px-6 py-6">
            <div class="flex items-center gap-4">

                <div class="alerts-icon alerts-icon-resolved">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor"
                         stroke-width="1.8">

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />

                    </svg>
                </div>

                <div>
                    <p class="alerts-card-label">
                        Resolved
                    </p>

                    <h2 class="alerts-card-value alerts-value-resolved mt-2">
                        {{ number_format($resolvedAlerts) }}
                    </h2>

                    <p class="alerts-card-subtext mt-1">
                        Successfully resolved
                    </p>
                </div>

            </div>
        </div>

    </div>


    {{-- Search / Filters --}}
    <div class="alerts-card p-5">

        <form method="GET">

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">

                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search alerts..."
                    class="alerts-filter border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-700 focus:outline-none"
                >

                <select
                    name="severity"
                    onchange="this.form.submit()"
                    class="alerts-filter border border-gray-300 rounded-lg px-4 py-2"
                >

                    <option value="">
                        All Severity
                    </option>

                    <option value="info"
                        {{ $severity == 'info' ? 'selected' : '' }}>
                        Information
                    </option>

                    <option value="warning"
                        {{ $severity == 'warning' ? 'selected' : '' }}>
                        Warning
                    </option>

                    <option value="critical"
                        {{ $severity == 'critical' ? 'selected' : '' }}>
                        Critical
                    </option>

                </select>


                <select
                    name="status"
                    onchange="this.form.submit()"
                    class="alerts-filter border border-gray-300 rounded-lg px-4 py-2"
                >

                    <option value="">
                        All Status
                    </option>

                    <option value="unread"
                        {{ $status == 'unread' ? 'selected' : '' }}>
                        Unread
                    </option>

                    <option value="read"
                        {{ $status == 'read' ? 'selected' : '' }}>
                        Read
                    </option>

                </select>


                <div>
                    <a
                        href="{{ route('admin.alerts') }}"
                        class="alerts-filter w-full flex items-center justify-center px-5 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition"
                    >
                        Clear Filters
                    </a>
                </div>

            </div>

        </form>

    </div>


    {{-- Alerts Table --}}
    <div class="alerts-card overflow-hidden">

        {{-- Table Header --}}
        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-200">

            <div>

                <h3 class="alerts-section-title">
                    Recent Alerts
                </h3>

                <p class="alerts-section-description mt-1">
                    Latest notifications generated by the CarbonWise system.
                </p>

            </div>

            <span class="alerts-table-text">
                {{ $recentAlerts->total() }} Alert(s)
            </span>

        </div>


        @if($recentAlerts->count())

            {{-- Table --}}
            <div class="overflow-x-auto">

                <table class="min-w-full">

                    <thead class="bg-gray-50 border-b border-gray-200">

                        <tr>

                            <th class="px-6 py-4 text-left alerts-table-header">
                                Alert
                            </th>

                            <th class="px-6 py-4 text-left alerts-table-header">
                                Severity
                            </th>

                            <th class="px-6 py-4 text-left alerts-table-header">
                                Status
                            </th>

                            <th class="px-6 py-4 text-left alerts-table-header">
                                Date
                            </th>

                            <th class="px-6 py-4 text-center alerts-table-header">
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-gray-100">

                        @foreach($recentAlerts as $alert)

                            <tr class="hover:bg-gray-50 transition">

                                {{-- Alert --}}
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

                                            <div class="alerts-table-title">
                                                {{ $alert->title }}
                                            </div>

                                            <div class="alerts-table-message mt-1">
                                                {{ $alert->message }}
                                            </div>

                                        </div>

                                    </div>

                                </td>


                                {{-- Severity --}}
                                <td class="px-6">

                                    @if($alert->severity == 'critical')

                                        <span class="px-3 py-1 rounded-full text-xs bg-red-100 text-red-700 font-medium">
                                            Critical
                                        </span>

                                    @elseif($alert->severity == 'warning')

                                        <span class="px-3 py-1 rounded-full text-xs bg-yellow-100 text-yellow-700 font-medium">
                                            Warning
                                        </span>

                                    @else

                                        <span class="px-3 py-1 rounded-full text-xs bg-blue-100 text-blue-700 font-medium">
                                            Information
                                        </span>

                                    @endif

                                </td>


                                {{-- Status --}}
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


                                {{-- Date --}}
                                <td class="px-6 alerts-table-text">

                                    {{ optional($alert->created_at)->format('M d, Y h:i A') ?? 'N/A' }}

                                </td>


                                {{-- Actions --}}
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


            {{-- Pagination --}}
            <div class="border-t border-gray-200 px-6 py-4">

                <div class="flex items-center justify-between">

                    <div class="alerts-table-text">

                        Showing
                        {{ $recentAlerts->firstItem() }}
                        to
                        {{ $recentAlerts->lastItem() }}
                        of
                        {{ $recentAlerts->total() }}
                        alerts

                    </div>

                    <div>
                        {{ $recentAlerts->links() }}
                    </div>

                </div>

            </div>

        @else

            {{-- Empty State --}}
            <div class="py-20">

                <div class="flex flex-col items-center justify-center">

                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-20 h-20 text-gray-300"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor">

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0h6z"/>

                    </svg>


                    <h3 class="alerts-empty-title mt-5">
                        No alerts found
                    </h3>


                    <p class="alerts-empty-text mt-2">
                        Alerts generated by CarbonWise will automatically appear here.
                    </p>

                </div>

            </div>

        @endif

    </div>

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


// Success message
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