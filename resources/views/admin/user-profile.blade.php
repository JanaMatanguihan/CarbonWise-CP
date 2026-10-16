@extends('layouts.admin')

@section('page-title', 'User Details')
@section('page-subtitle', 'User Management > User Details')

@section('content')

@php
    $profileImage = null;

    if (!empty($user->profile_photo)) {
        $profileImage = asset('storage/' . $user->profile_photo);
    } elseif (!empty($user->profile_picture)) {
        $profileImage = asset('storage/' . $user->profile_picture);
    }

    $nameParts = preg_split('/\s+/', trim($user->name));

    $initials = '';

    if (count($nameParts) >= 2) {
        $initials = strtoupper(
            substr($nameParts[0], 0, 1) .
            substr($nameParts[count($nameParts) - 1], 0, 1)
        );
    } elseif (!empty($user->name)) {
        $initials = strtoupper(substr($user->name, 0, 2));
    } else {
        $initials = 'U';
    }

    $historyLabels = collect($emissionHistory ?? [])
        ->pluck('date')
        ->values()
        ->all();

    $historyValues = collect($emissionHistory ?? [])
        ->pluck('value')
        ->map(function ($value) {
            return (float) $value;
        })
        ->values()
        ->all();

    $chartData = [
        'transportation' => (float) ($transportation ?? 0),
        'electricity' => (float) ($electricity ?? 0),
        'food' => (float) ($food ?? 0),
        'total' => (float) ($totalEmissions ?? 0),
        'historyLabels' => $historyLabels,
        'historyValues' => $historyValues,
    ];
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

    .profile-page {
        font-family: 'Poppins', sans-serif;
        color: #111827;
    }

    .profile-page * {
        font-family: 'Poppins', sans-serif;
    }

    .profile-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
    }

    .profile-muted {
        color: #6b7280;
    }

    .profile-title {
        color: #111827;
        font-weight: 700;
    }

    .profile-section-title {
        color: #111827;
        font-size: 16px;
        font-weight: 700;
    }

    .profile-small {
        font-size: 13px;
        line-height: 1.6;
    }

    .profile-label {
        font-size: 14px;
        color: #6b7280;
        font-weight: 500;
    }

    .profile-value {
        font-size: 14px;
        color: #111827;
        font-weight: 600;
    }

    .profile-avatar {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #ffffff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .profile-avatar-initials {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        background: #2f9d68;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 42px;
        font-weight: 700;
        border: 4px solid #ffffff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .profile-role {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 22px;
        border-radius: 999px;
        background: #dcfce7;
        color: #15803d;
        font-size: 13px;
        font-weight: 600;
    }

    .profile-info-box {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 22px;
        background: #ffffff;
    }

    .profile-info-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 8px 0;
    }

    .profile-status {
        color: #16a34a;
        font-weight: 600;
    }

    .profile-edit-button {
        width: 100%;
        border: 1px solid #d1d5db;
        background: #ffffff;
        color: #15803d;
        border-radius: 8px;
        padding: 12px 16px;
        font-size: 13px;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .profile-edit-button:hover {
        background: #f0fdf4;
        border-color: #86efac;
    }

    .profile-back-button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #ffffff;
        color: #374151;
        font-size: 12px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .profile-back-button:hover {
        background: #f9fafb;
        color: #15803d;
        border-color: #86efac;
    }

    .profile-tabs {
        display: flex;
        align-items: center;
        gap: 34px;
        padding: 0 28px;
        border-bottom: 1px solid #e5e7eb;
    }

    .profile-tab {
        position: relative;
        display: inline-flex;
        align-items: center;
        padding: 20px 0 17px;
        color: #4b5563;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
    }

    .profile-tab:hover {
        color: #15803d;
    }

    .profile-tab.active {
        color: #15803d;
        font-weight: 600;
    }

    .profile-tab.active::after {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        bottom: -1px;
        height: 2px;
        background: #22c55e;
        border-radius: 2px 2px 0 0;
    }

    .profile-stat-card {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #ffffff;
        padding: 20px;
        min-height: 120px;
    }

    .profile-stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .profile-stat-image {
        width: 22px;
        height: 22px;
        object-fit: contain;
    }

    .profile-stat-label {
        font-size: 13px;
        color: #6b7280;
        line-height: 1.5;
    }

    .profile-stat-value {
        margin-top: 4px;
        font-size: clamp(18px, 1.3vw, 22px);
        line-height: 1.25;
        overflow-wrap: anywhere;
        color: #111827;
        font-weight: 700;
    }

    .profile-stat-unit {
        margin-top: 3px;
        font-size: 12px;
        color: #6b7280;
    }

    .profile-chart-card {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #ffffff;
        padding: 22px;
        min-height: 390px;
    }

    .profile-chart-title {
        font-size: 15px;
        font-weight: 700;
        color: #111827;
    }

    .profile-chart-description {
        margin-top: 3px;
        font-size: 10px;
        color: #9ca3af;
    }

    .profile-empty {
        padding: 45px 20px;
        text-align: center;
        color: #9ca3af;
        font-size: 13px;
    }

    @media (max-width: 1100px) {
        .profile-tabs {
            gap: 22px;
        }

        .profile-info-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 3px;
        }
    }

    @media (max-width: 768px) {
        .profile-avatar,
        .profile-avatar-initials {
            width: 120px;
            height: 120px;
        }

        .profile-avatar-initials {
            font-size: 34px;
        }

        .profile-tabs {
            overflow-x: auto;
            white-space: nowrap;
        }
    }


    /* CarbonWise shared dark-mode palette */
    html[data-theme="dark"] .profile-page {
        color: #F3F4F6;
        color-scheme: dark;
    }

    html[data-theme="dark"] .profile-card,
    html[data-theme="dark"] .profile-info-box,
    html[data-theme="dark"] .profile-stat-card,
    html[data-theme="dark"] .profile-chart-card {
        background: #121A16;
        border-color: #1B3A2B;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.14);
    }

    html[data-theme="dark"] .profile-title,
    html[data-theme="dark"] .profile-section-title,
    html[data-theme="dark"] .profile-value,
    html[data-theme="dark"] .profile-stat-value,
    html[data-theme="dark"] .profile-chart-title,
    html[data-theme="dark"] .profile-page .text-gray-900 {
        color: #F3F4F6 !important;
    }

    html[data-theme="dark"] .profile-muted,
    html[data-theme="dark"] .profile-label,
    html[data-theme="dark"] .profile-stat-label,
    html[data-theme="dark"] .profile-stat-unit,
    html[data-theme="dark"] .profile-chart-description,
    html[data-theme="dark"] .profile-empty,
    html[data-theme="dark"] .profile-page .text-gray-500,
    html[data-theme="dark"] .profile-page .text-gray-600 {
        color: #9CA3AF !important;
    }

    html[data-theme="dark"] .profile-back-button,
    html[data-theme="dark"] .profile-edit-button {
        background: #18241F;
        color: #76C893;
        border-color: #2D6A4F;
    }

    html[data-theme="dark"] .profile-back-button:hover,
    html[data-theme="dark"] .profile-edit-button:hover {
        background: #162E24;
        color: #B7E4C7;
        border-color: #52B788;
    }

    html[data-theme="dark"] .profile-tabs {
        border-bottom-color: #1B3A2B;
    }

    html[data-theme="dark"] .profile-tab {
        color: #9CA3AF;
    }

    html[data-theme="dark"] .profile-tab:hover,
    html[data-theme="dark"] .profile-tab.active {
        color: #76C893;
    }

    html[data-theme="dark"] .profile-tab.active::after {
        background: #52B788;
    }

    html[data-theme="dark"] .profile-role {
        background: #163D2A;
        color: #76C893;
    }

    html[data-theme="dark"] .profile-avatar {
        border-color: #1B3A2B;
    }

    html[data-theme="dark"] .profile-avatar-initials {
        border-color: #1B3A2B;
        background: #2D6A4F;
    }

    html[data-theme="dark"] .profile-info-row {
        border-bottom: 1px solid rgba(118, 200, 147, 0.08);
    }

    html[data-theme="dark"] .profile-info-row:last-child {
        border-bottom: 0;
    }

    html[data-theme="dark"] .profile-page .profile-status {
        color: #76C893;
    }

    html[data-theme="dark"] .profile-stat-icon {
        filter: brightness(0.85);
    }

    .profile-page,
    .profile-page .profile-card,
    .profile-page .profile-info-box,
    .profile-page .profile-stat-card,
    .profile-page .profile-chart-card,
    .profile-page .profile-tabs,
    .profile-page .profile-tab,
    .profile-page .profile-back-button,
    .profile-page .profile-edit-button {
        transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
    }

    @media (max-width: 640px) {
        .profile-info-row {
            align-items: flex-start;
            flex-direction: column;
            gap: 4px;
        }

        .profile-value {
            text-align: left !important;
            overflow-wrap: anywhere;
        }

        .profile-chart-card {
            padding: 16px;
            min-height: 340px;
        }

        .profile-stat-card {
            padding: 16px;
        }
    }

</style>

<div class="profile-page">

    {{-- Back button --}}
    <div class="mb-5">
        <a
            href="{{ route('admin.users') }}"
            class="profile-back-button"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="17"
                height="17"
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


    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

        {{-- =========================================
             LEFT USER INFORMATION
        ========================================== --}}
        <div class="profile-card p-7">

            <div class="flex flex-col items-center text-center">

                {{-- Profile Picture / Initials --}}
                @if($profileImage)
                    <img
                        src="{{ $profileImage }}"
                        alt="{{ $user->name }}"
                        class="profile-avatar"
                    >
                @else
                    <div class="profile-avatar-initials">
                        {{ $initials }}
                    </div>
                @endif

                {{-- User Email --}}
                <div class="mt-5 text-[14px] text-gray-500 font-medium break-all">
                    {{ $user->email }}
                </div>

                {{-- Role --}}
                <div class="mt-4">
                    <span class="profile-role">
                        {{ ucfirst($user->role ?? 'User') }}
                    </span>
                </div>

            </div>


            {{-- User Information --}}
            <div class="profile-info-box mt-7">

                <div class="profile-info-row">
                    <span class="profile-label">
                        Department
                    </span>

                    <span class="profile-value text-right">
                        {{ $user->department ?: 'N/A' }}
                    </span>
                </div>


                <div class="profile-info-row">
                    <span class="profile-label">
                        Campus
                    </span>

                    <span class="profile-value text-right">
                        {{ $user->campus ?: 'N/A' }}
                    </span>
                </div>


                <div class="profile-info-row">
                    <span class="profile-label">
                        SR Code
                    </span>

                    <span class="profile-value text-right">
                        {{ $user->sr_code ?: 'N/A' }}
                    </span>
                </div>


                <div class="profile-info-row">
                    <span class="profile-label">
                        Year Level
                    </span>

                    <span class="profile-value text-right">
                        {{ $user->year_level ?: 'N/A' }}
                    </span>
                </div>


                <div class="profile-info-row">
                    <span class="profile-label">
                        Joined
                    </span>

                    <span class="profile-value text-right">
                        {{ $user->created_at ? $user->created_at->format('F d, Y') : 'N/A' }}
                    </span>
                </div>


                <div class="profile-info-row">
                    <span class="profile-label">
                        Status
                    </span>

                    <span class="profile-status">
                        {{ ucfirst($user->status ?? 'Unknown') }}
                    </span>
                </div>

            </div>


            {{-- Edit User --}}
            <div class="mt-6">

                <a
                    href="{{ route('admin.users.edit', $user->g_suite) }}"
                    class="profile-edit-button block text-center"
                >
                    Edit User
                </a>

            </div>

        </div>


        {{-- =========================================
             RIGHT SIDE
        ========================================== --}}
        <div class="profile-card xl:col-span-2 overflow-hidden">

            {{-- Tabs --}}
            <div class="profile-tabs">

                <a
                    href="{{ route('admin.users.show', $user->g_suite) }}"
                    class="profile-tab active"
                >
                    Overview
                </a>

                <a
                    href="{{ route('admin.users.records', $user->g_suite) }}"
                    class="profile-tab"
                >
                    Carbon Records
                </a>

            </div>


            {{-- Overview Content --}}
            <div class="p-7">

                {{-- =========================================
                     STAT CARDS
                ========================================== --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

                    {{-- Total Emissions --}}
                    <div class="profile-stat-card">

                        <div class="flex items-center gap-3">

                            <div
                                class="profile-stat-icon"
                                style="background:#dcfce7; color:#16a34a;"
                            >
                                <img
                                    src="{{ asset('images/user-profile/total-emissions.png') }}"
                                    alt="total-emissions"
                                    class="profile-stat-image"
                                >
                            </div>

                            <div>
                                <div class="profile-stat-label">
                                    Total Emissions
                                </div>

                                <div class="profile-stat-value">
                                    {{ number_format($totalEmissions ?? 0, 2) }}
                                </div>

                                <div class="profile-stat-unit">
                                    kg CO₂e
                                </div>
                            </div>

                        </div>

                    </div>


                    {{-- This Month --}}
                    <div class="profile-stat-card">

                        <div class="flex items-center gap-3">

                            <div
                                class="profile-stat-icon"
                                style="background:#dbeafe; color:#2563eb;"
                            >
                                <img
                                    src="{{ asset('images/user-profile/calendar.png') }}"
                                    alt="calendar"
                                    class="profile-stat-image"
                                >
                            </div>

                            <div>
                                <div class="profile-stat-label">
                                    This Month
                                </div>

                                <div class="profile-stat-value">
                                    {{ number_format($thisMonthEmission ?? 0, 2) }}
                                </div>

                                <div class="profile-stat-unit">
                                    kg CO₂e
                                </div>
                            </div>

                        </div>

                    </div>


                    {{-- Average Per Day --}}
                    <div class="profile-stat-card">

                        <div class="flex items-center gap-3">

                            <div
                                class="profile-stat-icon"
                                style="background:#fce7f3; color:#db2777;"
                            >
                                <img
                                    src="{{ asset('images/user-profile/average1.png') }}"
                                    alt="average"
                                    class="profile-stat-image"
                                >
                            </div>

                            <div>
                                <div class="profile-stat-label">
                                    Average per Day
                                </div>

                                <div class="profile-stat-value">
                                    {{ number_format($averagePerDay ?? 0, 2) }}
                                </div>

                                <div class="profile-stat-unit">
                                    kg CO₂e
                                </div>
                            </div>

                        </div>

                    </div>


                    {{-- Mitigation Actions --}}
                    <div class="profile-stat-card">

                        <div class="flex items-center gap-3">

                            <div
                                class="profile-stat-icon"
                                style="background:#dcfce7; color:#16a34a;"
                            >
                                <img
                                    src="{{ asset('images/user-profile/mitigation.png') }}"
                                    alt="mitigation"
                                    class="profile-stat-image"
                                >
                            </div>

                            <div>
                                <div class="profile-stat-label">
                                    Mitigation Actions
                                </div>

                                <div class="profile-stat-value">
                                    {{ $mitigationActions ?? 0 }}
                                </div>

                                <div class="profile-stat-unit">
                                    Completed
                                </div>
                            </div>

                        </div>

                    </div>

                </div>


                {{-- =========================================
                     CHARTS
                ========================================== --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-6">

                    {{-- Emissions Breakdown --}}
                    <div class="profile-chart-card">

                        <div class="profile-chart-title">
                            Emissions Breakdown
                        </div>

                        <div class="profile-chart-description">
                            Distribution of the user's recorded emissions
                        </div>

                        <div
                            id="profileEmissionBreakdown"
                            class="mt-4"
                        ></div>

                    </div>


                    {{-- Emissions Trend --}}
                    <div class="profile-chart-card">

                        <div class="profile-chart-title">
                            Emissions Trend
                        </div>

                        <div class="profile-chart-description">
                            Recorded carbon emissions over time
                        </div>

                        <div
                            id="profileEmissionTrend"
                            class="mt-4"
                        ></div>

                    </div>

                </div>


                {{-- =========================================
                     RECORD SUMMARY
                ========================================== --}}
                <div class="profile-card mt-5 p-5">

                    <div class="flex items-center justify-between gap-4">

                        <div>
                            <div class="profile-section-title">
                                Carbon Activity
                            </div>

                            <div class="profile-small profile-muted mt-1">
                                Summary of this user's carbon tracking activity.
                            </div>
                        </div>

                        <div class="text-right">

                            <div class="profile-label">
                                Total Records
                            </div>

                            <div class="text-[20px] font-bold text-gray-900">
                                {{ number_format($totalRecords ?? 0) }}
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


{{-- =========================================
     CHART DATA
========================================== --}}
<script type="application/json" id="profile-chart-data">@json($chartData)</script>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const isDarkTheme = () => document.documentElement.getAttribute('data-theme') === 'dark';
    const chartTextColor = () => isDarkTheme() ? '#9CA3AF' : '#6b7280';
    const chartStrongTextColor = () => isDarkTheme() ? '#F3F4F6' : '#111827';
    const chartGridColor = () => isDarkTheme() ? '#1B3A2B' : '#e5e7eb';
    let profileBreakdownChart = null;
    let profileTrendChart = null;
    let profileTrendHasData = false;

    const dataElement = document.getElementById('profile-chart-data');

    if (!dataElement) {
        return;
    }

    const chartData = JSON.parse(dataElement.textContent);

    const transportationValue = Number(chartData.transportation || 0);
    const electricityValue = Number(chartData.electricity || 0);
    const foodValue = Number(chartData.food || 0);
    const hasBreakdownData =
        transportationValue > 0 || electricityValue > 0 || foodValue > 0;

    const neutralChartColor = () => isDarkTheme() ? '#26332D' : '#E5E7EB';

    /*
     * Emissions Breakdown
     */
    const breakdownElement =
        document.getElementById('profileEmissionBreakdown');

    if (breakdownElement && typeof ApexCharts !== 'undefined') {

        profileBreakdownChart = new ApexCharts(
            breakdownElement,
            {
                chart: {
                    type: 'donut',
                    height: 320,
                    toolbar: {
                        show: false
                    },
                    fontFamily: 'Poppins, sans-serif'
                },

                // ApexCharts does not draw a donut when every value is zero.
                // Use one neutral placeholder slice in that case, while the
                // center label continues to display the real total (0 kg).
                series: hasBreakdownData
                    ? [transportationValue, electricityValue, foodValue]
                    : [1],

                labels: hasBreakdownData
                    ? ['Transportation', 'Electricity', 'Food Consumption']
                    : ['No emissions'],

                colors: hasBreakdownData
                    ? ['#2f9d68', '#f59e0b', '#ef4444']
                    : [neutralChartColor()],

                dataLabels: {
                    enabled: false
                },

                legend: {
                    show: hasBreakdownData,
                    position: 'bottom',
                    fontFamily: 'Poppins, sans-serif',
                    fontSize: '11px',

                    labels: {
                        colors: chartTextColor()
                    }
                },

                tooltip: {
                    enabled: hasBreakdownData,
                    y: {
                        formatter: function (value) {
                            return Number(value).toLocaleString(
                                undefined,
                                { maximumFractionDigits: 2 }
                            ) + ' kg CO₂e';
                        }
                    }
                },

                stroke: {
                    width: 2,
                    colors: [isDarkTheme() ? '#121A16' : '#ffffff']
                },

                plotOptions: {
                    pie: {
                        donut: {
                            size: '68%',

                            labels: {
                                show: true,

                                name: {
                                    show: true,
                                    fontFamily: 'Poppins, sans-serif',
                                    fontSize: '12px',
                                    color: chartTextColor()
                                },

                                value: {
                                    show: true,
                                    fontFamily: 'Poppins, sans-serif',
                                    fontSize: '18px',
                                    fontWeight: 700,
                                    color: chartStrongTextColor(),

                                    formatter: function (value) {
                                        return Number(value).toLocaleString(
                                            undefined,
                                            {
                                                maximumFractionDigits: 2
                                            }
                                        );
                                    }
                                },

                                total: {
                                    show: true,
                                    label: 'Total Emissions',
                                    fontFamily: 'Poppins, sans-serif',
                                    fontSize: '12px',
                                    color: chartTextColor(),

                                    formatter: function () {
                                        return Number(
                                            chartData.total || 0
                                        ).toLocaleString(
                                            undefined,
                                            {
                                                maximumFractionDigits: 2
                                            }
                                        ) + ' kg';
                                    }
                                }
                            }
                        }
                    }
                },

            }
        );

        profileBreakdownChart.render();
    }


    /*
     * Emissions Trend
     */
    const trendElement =
        document.getElementById('profileEmissionTrend');

    if (trendElement && typeof ApexCharts !== 'undefined') {

        const historyLabels =
            Array.isArray(chartData.historyLabels)
                ? chartData.historyLabels
                : [];

        const historyValues =
            Array.isArray(chartData.historyValues)
                ? chartData.historyValues.map(function (value) {
                    return Number(value) || 0;
                })
                : [];


        profileTrendHasData = historyValues.some(function (value) {
            return value > 0;
        });

        profileTrendChart = new ApexCharts(
            trendElement,
            {
                chart: {
                    type: 'area',
                    height: 320,
                    toolbar: {
                        show: false
                    },
                    fontFamily: 'Poppins, sans-serif'
                },

                // Keep the chart axes and dates visible, but don't draw a
                // colored line or markers when every recorded value is zero.
                series: [
                    {
                        name: 'CO₂e',
                        data: profileTrendHasData
                            ? historyValues
                            : historyValues.map(function () { return null; })
                    }
                ],

                colors: [profileTrendHasData ? '#2f7d57' : neutralChartColor()],

                stroke: {
                    curve: 'smooth',
                    width: 3
                },

                fill: {
                    type: 'gradient',

                    gradient: {
                        opacityFrom: 0.25,
                        opacityTo: 0.03
                    }
                },

                dataLabels: {
                    enabled: false
                },

                markers: {
                    size: profileTrendHasData ? 4 : 0,
                    strokeWidth: 2,

                    hover: {
                        size: profileTrendHasData ? 6 : 0
                    }
                },

                xaxis: {
                    categories: historyLabels,

                    labels: {
                        style: {
                            fontFamily: 'Poppins, sans-serif',
                            fontSize: '10px',
                            colors: chartTextColor()
                        },

                        rotate: -35,
                        hideOverlappingLabels: true
                    },

                    axisBorder: {
                        show: false
                    },

                    axisTicks: {
                        show: false
                    }
                },

                yaxis: {
                    min: 0,
                    ...(profileTrendHasData ? {} : { max: 1 }),
                    labels: {
                        style: {
                            fontFamily: 'Poppins, sans-serif',
                            fontSize: '10px',
                            colors: chartTextColor()
                        },

                        formatter: function (value) {
                            return Number(value).toLocaleString(
                                undefined,
                                { maximumFractionDigits: 0 }
                            );
                        }
                    }
                },

                grid: {
                    borderColor: chartGridColor(),
                    strokeDashArray: 4
                },

                tooltip: {
                    style: {
                        fontFamily: 'Poppins, sans-serif'
                    },

                    y: {
                        formatter: function (value) {
                            return Number(value).toLocaleString(
                                undefined,
                                {
                                    maximumFractionDigits: 2
                                }
                            ) + ' kg CO₂e';
                        }
                    }
                }
            }
        );

        profileTrendChart.render();
    }


    function updateProfileChartsForTheme() {
        const textColor = chartTextColor();
        const strongTextColor = chartStrongTextColor();
        const gridColor = chartGridColor();
        const dark = isDarkTheme();

        if (profileBreakdownChart) {
            profileBreakdownChart.updateOptions({
                chart: { background: 'transparent', foreColor: textColor },
                colors: hasBreakdownData
                    ? ['#2f9d68', '#f59e0b', '#ef4444']
                    : [neutralChartColor()],
                legend: { show: hasBreakdownData, labels: { colors: textColor } },
                stroke: { colors: [dark ? '#121A16' : '#ffffff'] },
                plotOptions: { pie: { donut: { labels: {
                    name: { color: textColor },
                    value: { color: strongTextColor },
                    total: { color: textColor }
                } } } }
            }, false, true);
        }

        if (profileTrendChart) {
            profileTrendChart.updateOptions({
                chart: { background: 'transparent', foreColor: textColor },
                colors: [profileTrendHasData ? '#2f7d57' : neutralChartColor()],
                xaxis: { labels: { style: { colors: textColor } } },
                yaxis: {
                    min: 0,
                    ...(profileTrendHasData ? {} : { max: 1 }),
                    labels: { style: { colors: textColor } }
                },
                markers: {
                    size: profileTrendHasData ? 4 : 0,
                    hover: { size: profileTrendHasData ? 6 : 0 }
                },
                grid: { borderColor: gridColor }
            }, false, true);
        }
    }

    window.addEventListener('carbonwise:themechange', updateProfileChartsForTheme);
    window.addEventListener('storage', function (event) {
        if (event.key === 'theme') updateProfileChartsForTheme();
    });

});
</script>

@endsection