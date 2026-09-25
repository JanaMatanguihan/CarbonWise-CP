@extends('layouts.admin')

@section('page-title', 'User Details')
@section('page-subtitle', 'User Management > User Details')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

    .records-page {
        font-family: 'Poppins', sans-serif;
        color: #111827;
    }

    .records-page,
    .records-page * {
        font-family: 'Poppins', sans-serif;
    }

    .records-layout {
        display: grid;
        grid-template-columns: 360px minmax(0, 1fr);
        gap: 24px;
        align-items: stretch;
        margin-top: 24px;
    }

    .records-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .records-right-card {
        min-width: 0;
        display: flex;
        flex-direction: column;
    }

    .records-back-button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #ffffff;
        color: #374151;
        text-decoration: none;
        font-size: 12px;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .records-back-button:hover {
        background: #f9fafb;
        border-color: #9ca3af;
        color: #111827;
    }

    .records-content {
        padding: 28px;
    }

    .records-title {
        margin: 0;
        font-size: 18px;
        line-height: 1.4;
        font-weight: 700;
        color: #111827;
    }

    .records-description {
        margin-top: 5px;
        font-size: 13px;
        line-height: 1.5;
        color: #6b7280;
    }

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
        padding: 15px 18px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
        font-size: 13px;
        line-height: 1.4;
        font-weight: 600;
        color: #111827;
        white-space: nowrap;
    }

    .records-table td {
        padding: 16px 18px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13px;
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
        font-size: 12px !important;
    }

    @media (max-width: 1100px) {
        .records-layout {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="records-page">

    {{-- Back Button --}}
    <div>
        <a
            href="{{ route('admin.users') }}"
            class="records-back-button"
        >
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

            {{-- Tabs --}}
            @include('admin.partials.user-tabs')


            <div class="records-content">

                <div>
                    <h2 class="records-title">
                        Carbon Records
                    </h2>

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
                                        <td
                                            colspan="5"
                                            style="text-align:center; padding:45px 20px; color:#9ca3af;"
                                        >
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