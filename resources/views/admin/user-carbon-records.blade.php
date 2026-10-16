
@extends('layouts.admin')

@section('page-title', 'User Details')
@section('page-subtitle', 'User Management > User Details')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

    .records-page,
    .records-page * {
        font-family: 'Poppins', sans-serif;
        box-sizing: border-box;
    }

    .records-page {
        color: #111827;
        width: 100%;
    }

    /* =========================================
       MAIN LAYOUT
       Sized to match the User Details Overview
       ========================================= */

    .records-layout {
        display: grid;
        grid-template-columns: 380px minmax(0, 1fr);
        gap: 28px;
        align-items: stretch;
        margin-top: 24px;
        width: 100%;
    }

    .records-card {
        min-width: 0;
        background: #ffffff;
        color: #111827;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    /* Left profile card */
    .records-layout > .records-card:first-child {
        width: 100%;
        min-width: 0;
        align-self: stretch;
        height: 100%;
    }

    .records-layout > .records-card:first-child .user-sidebar {
        width: 100%;
    }

    .records-layout > .records-card:first-child .user-sidebar-inner {
        padding: 28px 22px;
    }

    /* Match the Overview avatar size */
    .records-layout > .records-card:first-child .user-profile-photo,
    .records-layout > .records-card:first-child .user-profile-initials {
        width: 128px;
        height: 128px;
    }

    /* Right panel */
    .records-right-card {
        width: 100%;
        min-width: 0;
        min-height: 590px;
        display: flex;
        flex-direction: column;
    }

    /* =========================================
       BACK BUTTON
       ========================================= */

    .records-back-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #ffffff;
        color: #374151;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .records-back-button:hover {
        background: #f9fafb;
        border-color: #9ca3af;
        color: #111827;
    }

    /* =========================================
       RIGHT CONTENT
       ========================================= */

    .records-content {
        min-width: 0;
        flex: 1;
        padding: 28px;
    }

    .records-title {
        margin: 0;
        font-size: 20px;
        line-height: 1.4;
        font-weight: 700;
        color: #111827;
    }

    .records-description {
        margin-top: 6px;
        font-size: 14px;
        line-height: 1.6;
        color: #6b7280;
    }

    /* =========================================
       RECORDS TABLE
       ========================================= */

    .records-table-wrapper {
        height: 470px;
        margin-top: 22px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .records-scroll {
        flex: 1;
        overflow-y: auto;
        overflow-x: auto;
    }

    .records-scroll::-webkit-scrollbar {
        width: 7px;
        height: 7px;
    }

    .records-scroll::-webkit-scrollbar-track {
        background: #f9fafb;
    }

    .records-scroll::-webkit-scrollbar-thumb {
        background: #9ca3af;
        border-radius: 10px;
    }

    .records-table {
        width: 100%;
        min-width: 650px;
        border-collapse: collapse;
    }

    .records-table thead {
        background: #f9fafb;
    }

    .records-table th {
        padding: 16px 18px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
        font-size: 14px;
        line-height: 1.4;
        font-weight: 600;
        color: #111827;
        white-space: nowrap;
    }

    .records-table td {
        padding: 16px 18px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 14px;
        line-height: 1.5;
        color: #374151;
        white-space: nowrap;
    }

    .records-table tbody tr:hover {
        background: #f9fafb;
    }

    .records-total {
        color: #15803d !important;
        font-weight: 600 !important;
    }

    .records-empty-cell {
        padding: 45px 20px !important;
        text-align: center;
        color: #9ca3af !important;
        white-space: normal !important;
    }

    /* =========================================
       PAGINATION
       ========================================= */

    .records-pagination {
        min-height: 58px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding: 10px 18px;
        border-top: 1px solid #e5e7eb;
        background: #ffffff;
    }

    .records-pagination nav {
        font-family: 'Poppins', sans-serif !important;
        font-size: 13px !important;
    }

    /* =========================================
       DARK MODE
       ========================================= */

    html[data-theme="dark"] .records-page {
        color: #F3F4F6;
        color-scheme: dark;
    }

    html[data-theme="dark"] .records-card {
        background: #121A16;
        color: #F3F4F6;
        border-color: #1B3A2B;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.14);
    }

    html[data-theme="dark"] .records-back-button {
        background: #18241F;
        color: #76C893;
        border-color: #2D6A4F;
    }

    html[data-theme="dark"] .records-back-button:hover {
        background: #162E24;
        color: #B7E4C7;
        border-color: #52B788;
    }

    html[data-theme="dark"] .records-title,
    html[data-theme="dark"] .records-table th {
        color: #F3F4F6;
    }

    html[data-theme="dark"] .records-description {
        color: #9CA3AF;
    }

    html[data-theme="dark"] .records-table-wrapper {
        background: #121A16;
        border-color: #1B3A2B;
    }

    html[data-theme="dark"] .records-table thead {
        background: #18241F;
    }

    html[data-theme="dark"] .records-table th {
        border-bottom-color: #1B3A2B;
    }

    html[data-theme="dark"] .records-table td {
        color: #D1D5DB;
        border-bottom-color: #1B3A2B;
    }

    html[data-theme="dark"] .records-table tbody tr:hover {
        background: #18241F;
    }

    html[data-theme="dark"] .records-total {
        color: #52B788 !important;
    }

    html[data-theme="dark"] .records-empty-cell {
        color: #9CA3AF !important;
    }

    html[data-theme="dark"] .records-scroll::-webkit-scrollbar-track {
        background: #121A16;
    }

    html[data-theme="dark"] .records-scroll::-webkit-scrollbar-thumb {
        background: #2D6A4F;
    }

    html[data-theme="dark"] .records-pagination {
        background: #121A16;
        border-top-color: #1B3A2B;
    }

    html[data-theme="dark"] .records-pagination nav a,
    html[data-theme="dark"] .records-pagination nav span {
        color: #D1D5DB;
        border-color: #2D6A4F;
    }

    html[data-theme="dark"] .records-pagination nav a {
        background: #18241F;
    }

    html[data-theme="dark"] .records-pagination nav a:hover {
        background: #162E24;
        color: #76C893;
    }

    /* =========================================
       LEFT PROFILE SIDEBAR
       Use actual classes from user-sidebar
       ========================================= */

    html[data-theme="dark"] .records-page .user-sidebar,
    html[data-theme="dark"] .records-page .user-sidebar-inner {
        background: #121A16 !important;
        color: #F3F4F6 !important;
    }

    html[data-theme="dark"] .records-page .user-information {
        background: #121A16 !important;
        border-color: #1B3A2B !important;
    }

    html[data-theme="dark"] .records-page .user-information-label,
    html[data-theme="dark"] .records-page .user-email {
        color: #9CA3AF !important;
    }

    html[data-theme="dark"] .records-page .user-information-value {
        color: #F3F4F6 !important;
    }

    html[data-theme="dark"] .records-page .user-information-status {
        color: #76C893 !important;
    }

    html[data-theme="dark"] .records-page .user-role {
        background: #163D2A !important;
        color: #76C893 !important;
    }

    html[data-theme="dark"] .records-page .user-profile-photo,
    html[data-theme="dark"] .records-page .user-profile-initials {
        border-color: #1B3A2B !important;
    }

    html[data-theme="dark"] .records-page .user-edit-button {
        background: #18241F !important;
        color: #76C893 !important;
        border-color: #2D6A4F !important;
    }

    html[data-theme="dark"] .records-page .user-edit-button:hover {
        background: #162E24 !important;
        color: #B7E4C7 !important;
        border-color: #52B788 !important;
    }

    /* =========================================
       RESPONSIVE LAYOUT
       ========================================= */

    @media (max-width: 1200px) {
        .records-layout {
            grid-template-columns: 320px minmax(0, 1fr);
            gap: 24px;
        }
    }

    @media (max-width: 1000px) {
        .records-layout {
            grid-template-columns: minmax(0, 1fr);
            gap: 20px;
        }

        .records-layout > .records-card:first-child {
            height: auto;
        }

        .records-right-card {
            min-height: 0;
        }
    }

    @media (max-width: 640px) {
        .records-content {
            padding: 18px;
        }

        .records-table-wrapper {
            height: 420px;
        }

        .records-back-button {
            max-width: 100%;
        }

        .records-title {
            font-size: 18px;
        }

        .records-description {
            font-size: 13px;
        }
    }
</style>

<div class="records-page">

    {{-- Back Button --}}
    <div>
        <a href="{{ route('admin.users') }}" class="records-back-button">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="15"
                height="15"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <path d="M19 12H5"></path>
                <path d="M12 19l-7-7 7-7"></path>
            </svg>
            Back to User Management
        </a>
    </div>

    {{-- Main Layout --}}
    <div class="records-layout">

        {{-- Left User Profile --}}
        <div class="records-card">
            @include('admin.partials.user-sidebar')
        </div>

        {{-- Right Carbon Records --}}
        <div class="records-card records-right-card">

            {{-- Overview and Carbon Records Tabs --}}
            @include('admin.partials.user-tabs')

            <div class="records-content">

                <div>
                    <h2 class="records-title">Carbon Records</h2>

                    <p class="records-description">
                        View all carbon records submitted by this user.
                    </p>
                </div>

                {{-- Carbon Records Table --}}
                <div class="records-table-wrapper">

                    <div class="records-scroll">
                        <table class="records-table">

                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Transportation</th>
                                    <th>Electricity</th>
                                    <th>Food</th>
                                    <th>Total CO₂e</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($records as $record)
                                    <tr>
                                        <td>
                                            {{ \Carbon\Carbon::parse($record->record_date)->format('M d, Y') }}
                                        </td>

                                        <td>
                                            {{ number_format($record->transportation, 2) }}
                                        </td>

                                        <td>
                                            {{ number_format($record->electricity, 2) }}
                                        </td>

                                        <td>
                                            {{ number_format($record->food, 2) }}
                                        </td>

                                        <td class="records-total">
                                            {{ number_format($record->total_emission, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="records-empty-cell">
                                            No carbon records found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if($records->hasPages())
                        <div class="records-pagination">
                            {{ $records->links() }}
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>

@endsection
