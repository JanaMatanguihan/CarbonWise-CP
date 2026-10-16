
@extends('layouts.admin')

@section('page-title', 'Alerts & Notifications')
@section('page-subtitle', 'Monitor system alerts and notifications')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

    /* =========================================================
       CARBONWISE ALERTS — SHARED LIGHT/DARK THEME
       Uses the data-theme attribute from layouts/admin.blade.php
       ========================================================= */

    .alerts-page {
        --alerts-page-bg: #f1f1ee;
        --alerts-surface: #ffffff;
        --alerts-surface-alt: #f9fafb;
        --alerts-surface-hover: #f3f4f6;
        --alerts-border: #e5e7eb;
        --alerts-border-strong: #d1d5db;
        --alerts-text: #111827;
        --alerts-text-secondary: #4b5563;
        --alerts-text-muted: #6b7280;
        --alerts-input-bg: #ffffff;
        --alerts-green: #15803d;
        --alerts-green-hover: #166534;
        --alerts-shadow: 0 2px 8px rgb(0 0 0 / 5%);

        width: 100%;
        min-height: 100vh;
        margin: -1.5rem -1.5rem 0;
        padding: 1.5rem;
        background: var(--alerts-page-bg);
        color: var(--alerts-text);
        font-family: 'Poppins', sans-serif;
        font-size: 13px;
        line-height: 1.6;
    }

    /* Match the theme system used by the admin layout */
    html[data-theme="dark"] .alerts-page,
    body[data-theme="dark"] .alerts-page {
        --alerts-page-bg: #0a0f0d;
        --alerts-surface: #121a16;
        --alerts-surface-alt: #18221c;
        --alerts-surface-hover: #202e25;
        --alerts-border: #293a30;
        --alerts-border-strong: #3a4d40;
        --alerts-text: #f3f4f6;
        --alerts-text-secondary: #b4c0b7;
        --alerts-text-muted: #8c9b90;
        --alerts-input-bg: #0d1510;
        --alerts-green: #4ade80;
        --alerts-green-hover: #86efac;
        --alerts-shadow: 0 4px 16px rgb(0 0 0 / 15%);
    }

    .alerts-page *,
    .alerts-page *::before,
    .alerts-page *::after {
        box-sizing: border-box;
    }

    .alerts-page button,
    .alerts-page input,
    .alerts-page select {
        font-family: inherit;
    }

    /* Cards */
    .alerts-page .alerts-card {
        min-width: 0;
        overflow: hidden;
        border: 1px solid var(--alerts-border);
        border-radius: 12px;
        background: var(--alerts-surface);
        color: var(--alerts-text);
        box-shadow: var(--alerts-shadow);
    }

    /* Summary cards */
    .alerts-page .alerts-card-label {
        color: var(--alerts-text-secondary);
        font-size: 12px;
        line-height: 18px;
        font-weight: 500;
    }

    .alerts-page .alerts-card-value {
        font-size: 25px;
        line-height: 34px;
        font-weight: 700;
        letter-spacing: -.5px;
        font-variant-numeric: tabular-nums;
    }

    .alerts-page .alerts-card-subtext {
        color: var(--alerts-text-muted);
        font-size: 10px;
        line-height: 16px;
    }

    .alerts-page .alerts-icon {
        display: flex;
        width: 48px;
        height: 48px;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
    }

    .alerts-page .alerts-icon svg {
        width: 25px;
        height: 25px;
    }

    .alerts-page .alerts-icon-total {
        background: #dcfce7;
        color: #15803d;
    }

    .alerts-page .alerts-icon-unread {
        background: #dbeafe;
        color: #2563eb;
    }

    .alerts-page .alerts-icon-critical {
        background: #fee2e2;
        color: #dc2626;
    }

    .alerts-page .alerts-icon-resolved {
        background: #dcfce7;
        color: #16a34a;
    }

    .alerts-page .alerts-value-total,
    .alerts-page .alerts-value-unread {
        color: var(--alerts-text);
    }

    .alerts-page .alerts-value-critical {
        color: #dc2626;
    }

    .alerts-page .alerts-value-resolved {
        color: #16a34a;
    }

    /* Section headings */
    .alerts-page .alerts-section-title {
        color: var(--alerts-text);
        font-size: 16px;
        line-height: 25px;
        font-weight: 700;
    }

    .alerts-page .alerts-section-description {
        color: var(--alerts-text-muted);
        font-size: 11px;
        line-height: 18px;
    }

    .alerts-page .alerts-table-header {
        color: var(--alerts-text-secondary);
        font-size: 11px;
        line-height: 17px;
        font-weight: 600;
    }

    .alerts-page .alerts-table-text {
        color: var(--alerts-text-muted);
        font-size: 11px;
        line-height: 17px;
    }

    .alerts-page .alerts-table-title {
        color: var(--alerts-text);
        font-size: 12px;
        line-height: 19px;
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    .alerts-page .alerts-table-message {
        color: var(--alerts-text-secondary);
        font-size: 11px;
        line-height: 18px;
        overflow-wrap: anywhere;
    }

    /* Search and filters */
    .alerts-page .alerts-filter {
        display: block;
        width: 100%;
        min-width: 0;
        min-height: 42px;
        border: 1px solid var(--alerts-border-strong);
        border-radius: 8px;
        background: var(--alerts-input-bg);
        padding: 10px 13px;
        color: var(--alerts-text);
        font-size: 11px;
        line-height: 18px;
        outline: none;
        transition: border-color .15s, box-shadow .15s, background .15s;
    }

    .alerts-page .alerts-filter::placeholder {
        color: var(--alerts-text-muted);
        opacity: 1;
    }

    .alerts-page .alerts-filter:focus {
        border-color: #16a34a;
        box-shadow: 0 0 0 3px rgb(34 197 94 / 13%);
    }

    .alerts-page select.alerts-filter option {
        background: var(--alerts-surface);
        color: var(--alerts-text);
    }

    .alerts-page a.alerts-filter {
        text-decoration: none;
        cursor: pointer;
        transition: background .15s, border-color .15s;
    }

    .alerts-page a.alerts-filter:hover {
        border-color: var(--alerts-border-strong);
        background: var(--alerts-surface-hover);
    }

    /* Table and separators */
    .alerts-page .alerts-table-container {
        width: 100%;
        overflow-x: auto;
        scrollbar-width: thin;
        scrollbar-color: var(--alerts-border-strong) transparent;
    }

    .alerts-page .alerts-data-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        text-align: left;
    }

    .alerts-page .alerts-data-table thead {
        background: var(--alerts-surface-alt);
    }

    .alerts-page .alerts-data-table th {
        padding: 15px 20px;
        border-bottom: 1px solid var(--alerts-border);
        white-space: nowrap;
    }

    .alerts-page .alerts-data-table td {
        padding: 18px 20px;
        border-bottom: 1px solid var(--alerts-border);
        vertical-align: middle;
    }

    .alerts-page .alerts-data-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .alerts-page .alerts-data-table tbody tr {
        background: var(--alerts-surface);
        transition: background .15s;
    }

    .alerts-page .alerts-data-table tbody tr:hover {
        background: var(--alerts-surface-hover);
    }

    .alerts-page .alerts-data-table tbody tr:hover td {
        background: transparent;
    }

    .alerts-page .alerts-table-heading {
        border-bottom: 1px solid var(--alerts-border);
    }

    .alerts-page .alerts-pagination {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        border-top: 1px solid var(--alerts-border);
        padding: 18px 24px;
    }

    .alerts-page .alerts-pagination nav {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 5px;
    }

    .alerts-page .alerts-pagination nav a,
    .alerts-page .alerts-pagination nav span {
        display: inline-flex;
        min-width: 34px;
        min-height: 34px;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--alerts-border);
        border-radius: 7px;
        padding: 6px 10px;
        background: var(--alerts-surface);
        color: var(--alerts-text-secondary);
        font-size: 11px;
        text-decoration: none;
    }

    .alerts-page .alerts-pagination nav a:hover {
        border-color: #16a34a;
        background: var(--alerts-surface-hover);
        color: var(--alerts-green);
    }

    .alerts-page .alerts-pagination nav span[aria-current="page"] {
        border-color: #15803d;
        background: #15803d;
        color: #ffffff;
    }

    .alerts-page .alerts-pagination nav span[aria-disabled="true"] {
        opacity: .5;
    }

    /* Severity badges */
    .alerts-page .severity-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 5px 10px;
        font-size: 10px;
        font-weight: 600;
        line-height: 1.5;
        white-space: nowrap;
    }

    .alerts-page .severity-critical {
        background: #fee2e2;
        color: #b91c1c;
    }

    .alerts-page .severity-warning {
        background: #fef3c7;
        color: #92400e;
    }

    .alerts-page .severity-info {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .alerts-page .status-read {
        background: #e5e7eb;
        color: #374151;
    }

    .alerts-page .status-unread {
        background: #dcfce7;
        color: #166534;
    }

    html[data-theme="dark"] .alerts-page .status-read,
    body[data-theme="dark"] .alerts-page .status-read {
        background: #29352e;
        color: #d1d5db;
    }

    html[data-theme="dark"] .alerts-page .status-unread,
    body[data-theme="dark"] .alerts-page .status-unread {
        background: #153a25;
        color: #86efac;
    }

    /* Row action buttons */
    .alerts-page .alert-action {
        display: inline-flex;
        min-height: 32px;
        align-items: center;
        justify-content: center;
        border: 1px solid transparent;
        border-radius: 7px;
        padding: 6px 10px;
        font-size: 10px;
        font-weight: 600;
        white-space: nowrap;
        cursor: pointer;
        transition: background .15s, color .15s, border-color .15s;
    }

    .alerts-page .alert-mark-read {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .alerts-page .alert-mark-read:hover {
        background: #1d4ed8;
        color: #ffffff;
    }

    .alerts-page .alert-delete {
        background: #fee2e2;
        color: #b91c1c;
    }

    .alerts-page .alert-delete:hover {
        background: #b91c1c;
        color: #ffffff;
    }

    html[data-theme="dark"] .alerts-page .alert-mark-read,
    body[data-theme="dark"] .alerts-page .alert-mark-read {
        background: #172e49;
        color: #93c5fd;
    }

    html[data-theme="dark"] .alerts-page .alert-mark-read:hover,
    body[data-theme="dark"] .alerts-page .alert-mark-read:hover {
        background: #1d4ed8;
        color: #ffffff;
    }

    html[data-theme="dark"] .alerts-page .alert-delete,
    body[data-theme="dark"] .alerts-page .alert-delete {
        background: #3b2020;
        color: #fca5a5;
    }

    html[data-theme="dark"] .alerts-page .alert-delete:hover,
    body[data-theme="dark"] .alerts-page .alert-delete:hover {
        background: #b91c1c;
        color: #ffffff;
    }

    /* Empty state */
    .alerts-page .alerts-empty-state {
        padding: 64px 24px;
        text-align: center;
    }

    .alerts-page .alerts-empty-icon {
        display: inline-flex;
        width: 76px;
        height: 76px;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--alerts-border);
        border-radius: 50%;
        background: var(--alerts-surface-alt);
        color: var(--alerts-text-muted);
    }

    .alerts-page .alerts-empty-icon svg {
        width: 35px;
        height: 35px;
    }

    .alerts-page .alerts-empty-title {
        color: var(--alerts-text);
        font-size: 16px;
        line-height: 25px;
        font-weight: 600;
    }

    .alerts-page .alerts-empty-text {
        max-width: 420px;
        margin-right: auto;
        margin-left: auto;
        color: var(--alerts-text-muted);
        font-size: 11px;
        line-height: 19px;
    }

    /* Explicit overrides for existing Tailwind color utilities */
    .alerts-page .bg-white {
        background-color: var(--alerts-surface) !important;
    }

    .alerts-page .bg-gray-50 {
        background-color: var(--alerts-surface-alt) !important;
    }

    .alerts-page .hover\:bg-gray-50:hover {
        background-color: var(--alerts-surface-hover) !important;
    }

    .alerts-page .border-gray-100,
    .alerts-page .border-gray-200 {
        border-color: var(--alerts-border) !important;
    }

    .alerts-page .border-gray-300 {
        border-color: var(--alerts-border-strong) !important;
    }

    .alerts-page .divide-gray-100 > :not([hidden]) ~ :not([hidden]) {
        border-color: var(--alerts-border) !important;
    }

    .alerts-page .text-gray-300 {
        color: var(--alerts-text-muted) !important;
    }

    .alerts-page .text-gray-500,
    .alerts-page .text-gray-600 {
        color: var(--alerts-text-secondary) !important;
    }

    .alerts-page .text-gray-700 {
        color: var(--alerts-text-secondary) !important;
    }

    .alerts-page .text-gray-900 {
        color: var(--alerts-text) !important;
    }

    .alerts-page .focus\:ring-green-700:focus {
        --tw-ring-color: #15803d !important;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .alerts-page {
            margin-right: -1rem;
            margin-left: -1rem;
            padding: 1rem;
        }

        .alerts-page .alerts-icon {
            width: 43px;
            height: 43px;
        }

        .alerts-page .alerts-icon svg {
            width: 22px;
            height: 22px;
        }

        .alerts-page .alerts-card-value {
            font-size: 22px;
        }

        .alerts-page .alerts-data-table {
            min-width: 800px;
        }

        .alerts-page .alerts-data-table th,
        .alerts-page .alerts-data-table td {
            padding: 14px;
        }

        .alerts-page .alerts-pagination {
            padding: 16px;
        }
    }

    @media (max-width: 480px) {
        .alerts-page .alerts-empty-state {
            padding: 45px 16px;
        }

        .alerts-page .alerts-pagination {
            align-items: flex-start;
        }

        .alerts-page .alerts-pagination nav {
            max-width: 100%;
        }
    }
</style>

<div class="alerts-page space-y-6"
    data-success-message="{{ session('success') }}">

    {{-- Summary cards --}}
    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">

        <div class="alerts-card px-6 py-6">
            <div class="flex items-center gap-4">
                <div class="alerts-icon alerts-icon-total">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9a6 6 0 00-12 0v.75a8.967 8.967 0 01-2.311 6.022c1.733.64 3.557 1.082 5.454 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                    </svg>
                </div>
                <div>
                    <p class="alerts-card-label">Total Alerts</p>
                    <h2 class="alerts-card-value alerts-value-total mt-2">
                        {{ number_format($totalAlerts) }}
                    </h2>
                    <p class="alerts-card-subtext mt-1">All system alerts</p>
                </div>
            </div>
        </div>

        <div class="alerts-card px-6 py-6">
            <div class="flex items-center gap-4">
                <div class="alerts-icon alerts-icon-unread">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25H4.5a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0l-7.5-4.615a2.25 2.25 0 01-1.07-1.916V6.75" />
                    </svg>
                </div>
                <div>
                    <p class="alerts-card-label">Unread Alerts</p>
                    <h2 class="alerts-card-value alerts-value-unread mt-2">
                        {{ number_format($unreadAlerts) }}
                    </h2>
                    <p class="alerts-card-subtext mt-1">New notifications</p>
                </div>
            </div>
        </div>

        <div class="alerts-card px-6 py-6">
            <div class="flex items-center gap-4">
                <div class="alerts-icon alerts-icon-critical">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 9v3.75m0 3.75h.007v.008H12v-.008z" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                </div>
                <div>
                    <p class="alerts-card-label">Critical Alerts</p>
                    <h2 class="alerts-card-value alerts-value-critical mt-2">
                        {{ number_format($criticalAlerts) }}
                    </h2>
                    <p class="alerts-card-subtext mt-1">Requires attention</p>
                </div>
            </div>
        </div>

        <div class="alerts-card px-6 py-6">
            <div class="flex items-center gap-4">
                <div class="alerts-icon alerts-icon-resolved">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="alerts-card-label">Resolved</p>
                    <h2 class="alerts-card-value alerts-value-resolved mt-2">
                        {{ number_format($resolvedAlerts) }}
                    </h2>
                    <p class="alerts-card-subtext mt-1">Successfully resolved</p>
                </div>
            </div>
        </div>

    </div>

    {{-- Search and filters --}}
    <div class="alerts-card p-5">
        <form method="GET" action="{{ route('admin.alerts') }}">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">

                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search alerts..."
                    aria-label="Search alerts"
                    class="alerts-filter"
                >

                <select name="severity" aria-label="Filter by severity"
                        onchange="this.form.submit()" class="alerts-filter">
                    <option value="">All Severity</option>
                    <option value="info" @selected($severity == 'info')>Information</option>
                    <option value="warning" @selected($severity == 'warning')>Warning</option>
                    <option value="critical" @selected($severity == 'critical')>Critical</option>
                </select>

                <select name="status" aria-label="Filter by status"
                        onchange="this.form.submit()" class="alerts-filter">
                    <option value="">All Status</option>
                    <option value="unread" @selected($status == 'unread')>Unread</option>
                    <option value="read" @selected($status == 'read')>Read</option>
                </select>

                <a href="{{ route('admin.alerts') }}"
                   class="alerts-filter flex items-center justify-center">
                    Clear Filters
                </a>

            </div>
        </form>
    </div>

    {{-- Recent alerts --}}
    <section class="alerts-card">

        <div class="alerts-table-heading flex flex-wrap items-center justify-between gap-3 px-6 py-5">
            <div>
                <h3 class="alerts-section-title">Recent Alerts</h3>
                <p class="alerts-section-description mt-1">
                    Latest notifications generated by the CarbonWise system.
                </p>
            </div>
            <span class="alerts-table-text">
                {{ $recentAlerts->total() }} Alert(s)
            </span>
        </div>

        @if($recentAlerts->count())

            <div class="alerts-table-container">
                <table class="alerts-data-table min-w-full">
                    <thead>
                        <tr>
                            <th scope="col" class="alerts-table-header">Alert</th>
                            <th scope="col" class="alerts-table-header">Severity</th>
                            <th scope="col" class="alerts-table-header">Status</th>
                            <th scope="col" class="alerts-table-header">Date</th>
                            <th scope="col" class="alerts-table-header text-center">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($recentAlerts as $alert)
                            <tr>
                                {{-- Alert details --}}
                                <td>
                                    <div class="flex items-start gap-3">
                                        <div class="mt-1 flex-shrink-0">
                                            @if($alert->severity === 'critical')
                                                <div class="h-10 w-1.5 rounded-full bg-red-500"></div>
                                            @elseif($alert->severity === 'warning')
                                                <div class="h-10 w-1.5 rounded-full bg-yellow-500"></div>
                                            @else
                                                <div class="h-10 w-1.5 rounded-full bg-blue-500"></div>
                                            @endif
                                        </div>

                                        <div class="min-w-0">
                                            <p class="alerts-table-title">
                                                {{ $alert->title }}
                                            </p>
                                            <p class="alerts-table-message mt-1">
                                                {{ $alert->message }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Severity --}}
                                <td>
                                    @if($alert->severity === 'critical')
                                        <span class="severity-badge severity-critical">Critical</span>
                                    @elseif($alert->severity === 'warning')
                                        <span class="severity-badge severity-warning">Warning</span>
                                    @else
                                        <span class="severity-badge severity-info">Information</span>
                                    @endif
                                </td>

                                {{-- Read status --}}
                                <td>
                                    @if($alert->is_read)
                                        <span class="severity-badge status-read">Read</span>
                                    @else
                                        <span class="severity-badge status-unread">New</span>
                                    @endif
                                </td>

                                {{-- Date --}}
                                <td class="alerts-table-text whitespace-nowrap">
                                    {{ optional($alert->created_at)->format('M d, Y h:i A') ?? 'N/A' }}
                                </td>

                                {{-- Actions --}}
                                <td>
                                    <div class="flex flex-wrap justify-center gap-2">

                                        @if(!$alert->is_read)
                                            <form method="POST"
                                                  action="{{ route('admin.alerts.read', $alert) }}">
                                                @csrf
                                                @method('PATCH')

                                                <button type="submit" class="alert-action alert-mark-read">
                                                    Mark Read
                                                </button>
                                            </form>
                                        @endif

                                        <form method="POST"
                                              action="{{ route('admin.alerts.destroy', $alert) }}"
                                              class="delete-alert-form">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="alert-action alert-delete">
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
            <div class="alerts-pagination">
                <p class="alerts-table-text">
                    Showing
                    <strong>{{ $recentAlerts->firstItem() ?? 0 }}</strong>
                    to
                    <strong>{{ $recentAlerts->lastItem() ?? 0 }}</strong>
                    of
                    <strong>{{ $recentAlerts->total() }}</strong>
                    alerts
                </p>

                <div>
                    {{ $recentAlerts->appends(request()->query())->links() }}
                </div>
            </div>

        @else

            {{-- Empty state --}}
            <div class="alerts-empty-state">
                <div class="alerts-empty-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"
                         aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0h6z"/>
                    </svg>
                </div>

                <h3 class="alerts-empty-title mt-5">No alerts found</h3>
                <p class="alerts-empty-text mt-2">
                    Alerts generated by CarbonWise will automatically appear here.
                    Try adjusting your search or filters.
                </p>

                @if(request()->hasAny(['search', 'severity', 'status']))
                    <a href="{{ route('admin.alerts') }}"
                       class="mt-4 inline-flex items-center justify-center rounded-lg
                              bg-green-700 px-4 py-2 text-xs font-semibold text-white
                              transition hover:bg-green-800">
                        Clear Filters
                    </a>
                @endif
            </div>

        @endif

    </section>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Delete confirmation
    document.querySelectorAll('.delete-alert-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            if (typeof Swal === 'undefined') {
                if (window.confirm('This alert will be permanently deleted. Continue?')) {
                    form.submit();
                }
                return;
            }

            Swal.fire({
                title: 'Delete Alert?',
                text: 'This alert will be permanently deleted.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#15803d',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });

    const successMessage = document.querySelector('.alerts-page')
        ?.dataset.successMessage;

    if (successMessage && typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: successMessage,
            confirmButtonColor: '#15803d',
            timer: 2200,
            showConfirmButton: false
        });
    }
});
</script>
@endpush

@endsection
