@extends('layouts.admin')

@section('page-title', 'Overview')
@section('page-subtitle', 'Welcome Back, ' . Auth::user()->name . '!')

@section('content')

@php
    // Prepare forecast data for the dashboard.
// The TFT model currently produces a 30-day forecast.
// If the controller already provides forecast data, use it.
// Otherwise, retrieve the latest forecast through ForecastService.

    $dashboardForecastLabels = collect($forecastLabels ?? []);
    $dashboardForecastValues = collect($forecastValues ?? []);

    if ($dashboardForecastValues->isEmpty()) {
        try {
            $forecastServiceData = app(\App\Services\ForecastService::class)->getHistoricalData();

            $serviceForecast = collect($forecastServiceData['forecast'] ?? []);

            if ($serviceForecast->isNotEmpty()) {
                $dashboardForecastLabels = $serviceForecast
                    ->pluck('record_date')
                    ->values();

                $dashboardForecastValues = $serviceForecast
                    ->map(function ($item) {
                        return (float) (
                            $item['predicted_total_emission']
                            ?? $item['forecast']
                            ?? $item['predicted_emissions']
                            ?? 0
                        );
                    })
                    ->values();
            } else {
                $dashboardForecastLabels = collect(
                    $forecastServiceData['forecastLabels'] ?? []
                )->values();

                $dashboardForecastValues = collect(
                    $forecastServiceData['forecastValues'] ?? []
                )->map(function ($value) {
                    return (float) $value;
                })->values();
            }
        } catch (\Throwable $e) {
            $dashboardForecastLabels = collect();
            $dashboardForecastValues = collect();
        }
    }
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

    .overview-page {
        font-family: 'Poppins', sans-serif;
        color: #111827;
    }

    .overview-page * {
        font-family: 'Poppins', sans-serif;
    }

    .overview-card {
        background: #ffffff;
        border: 1px solid #e3e7eb;
        border-radius: 10px;
        box-shadow: 0 2px 6px rgba(17, 24, 39, 0.07);
    }

    .overview-card {
        transition: border-color 0.18s ease, box-shadow 0.18s ease;
    }

    .overview-card:hover {
        border-color: #d7dde3;
        box-shadow: 0 4px 10px rgba(17, 24, 39, 0.08);
    }

    .overview-page .w-12.h-12.rounded-full {
        flex-shrink: 0;
    }

    .overview-card-title {
        font-size: 18px;
        line-height: 26px;
        font-weight: 700;
        color: #172033;
        letter-spacing: -0.15px;
    }

    .overview-card-description {
        font-size: 12px;
        line-height: 18px;
        color: #7b8491;
        font-weight: 400;
    }

    .overview-stat-label {
        font-size: 13px;
        line-height: 19px;
        color: #667085;
        font-weight: 500;
    }

    .overview-stat-value {
        font-size: 28px;
        line-height: 36px;
        color: #172033;
        font-weight: 700;
        letter-spacing: -0.45px;
    }

    .overview-stat-unit {
        font-size: 11px;
        line-height: 16px;
        color: #667085;
        font-weight: 400;
    }

    .overview-stat-subtext {
        font-size: 11px;
        line-height: 16px;
        color: #2f7d57;
        font-weight: 500;
    }

    .overview-legend {
        font-size: 13px;
        line-height: 19px;
        color: #374151;
        font-weight: 500;
    }

    .overview-department-name {
        font-size: 13px;
        line-height: 19px;
        color: #172033;
        font-weight: 600;
    }

    .overview-department-value {
        font-size: 12px;
        line-height: 17px;
        color: #667085;
        font-weight: 400;
    }

    .overview-small {
        font-size: 12px;
        line-height: 17px;
        color: #667085;
    }

    .overview-alert-title {
        font-size: 13px;
        line-height: 19px;
        color: #172033;
        font-weight: 600;
    }

    .overview-alert-message {
        font-size: 11px;
        line-height: 16px;
        color: #7b8491;
    }

    .overview-alert-time {
        font-size: 11px;
        line-height: 16px;
        color: #8a93a0;
        white-space: nowrap;
    }

    .overview-select {
        height: 40px;
        min-width: 112px;
        padding: 0 14px;
        border: 1px solid #d1d5db;
        border-radius: 7px;
        background: #ffffff;
        color: #374151;
        font-size: 13px;
        font-weight: 500;
        outline: none;
        cursor: pointer;
    }

    .overview-select:focus {
        border-color: #2f7d57;
        box-shadow: 0 0 0 2px rgba(47, 125, 87, 0.10);
    }

    .overview-chart {
        position: relative;
        width: 100%;
    }

    .overview-chart-large {
        height: 320px;
    }

    .overview-chart-medium {
        height: 290px;
    }

    .overview-chart-small {
        height: 230px;
    }

    .overview-empty {
        min-height: 210px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
    }

    .overview-forecast-empty {
        height: 210px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: #7b8491;
        font-size: 13px;
    }

    .overview-strategy-title {
        font-size: 13px;
        line-height: 19px;
        font-weight: 600;
        color: #172033;
    }

    .overview-strategy-description {
        font-size: 12px;
        line-height: 17px;
        color: #667085;
    }

    .overview-strategy-saving {
        font-size: 11px;
        line-height: 16px;
        color: #2f7d57;
        font-weight: 500;
    }
</style>

<div class="overview-page max-w-[1800px] mx-auto px-4 py-5 space-y-4">

    {{-- Top statistics --}}

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">

        {{-- Total Users --}}

        <div class="overview-card px-5 py-4 flex items-center gap-4 min-h-[104px]">

            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                <img
                    src="{{ asset('icons/group.png') }}"
                    class="w-6 h-6 object-contain"
                    alt="Users"
                >
            </div>

            <div>
                <p class="overview-stat-label">
                    Total Users
                </p>

                <h2 class="overview-stat-value">
                    {{ number_format($totalUsers) }}
                </h2>

                <p class="overview-stat-subtext">
                    Registered users
                </p>
            </div>

        </div>


        {{-- Total Emissions --}}

        <div class="overview-card px-5 py-4 flex items-center gap-4 min-h-[104px]">

            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                <img
                    src="{{ asset('icons/co2.png') }}"
                    class="w-6 h-6 object-contain"
                    alt="Emissions"
                >
            </div>

            <div>
                <p class="overview-stat-label">
                    Total Emissions
                </p>

                <h2 class="overview-stat-value">
                    {{ number_format($totalEmissions, 2) }}
                </h2>

                <p class="overview-stat-unit">
                    kg CO₂e
                </p>
            </div>

        </div>


        {{-- Average Emission --}}

        <div class="overview-card px-5 py-4 flex items-center gap-4 min-h-[104px]">

            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                <img
                    src="{{ asset('icons/user2.png') }}"
                    class="w-6 h-6 object-contain"
                    alt="Average"
                >
            </div>

            <div>
                <p class="overview-stat-label">
                    Average / User
                </p>

                <h2 class="overview-stat-value">
                    {{ number_format($averageEmission, 2) }}
                </h2>

                <p class="overview-stat-unit">
                    kg CO₂e
                </p>
            </div>

        </div>


        {{-- Mitigation Actions --}}

        <div class="overview-card px-5 py-4 flex items-center gap-4 min-h-[104px]">

            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                <img
                    src="{{ asset('icons/leaf.png') }}"
                    class="w-6 h-6 object-contain"
                    alt="Mitigation"
                >
            </div>

            <div>
                <p class="overview-stat-label">
                    Mitigation Actions
                </p>

                <h2 class="overview-stat-value">
                    {{ $mitigationCount }}
                </h2>
            </div>

        </div>

    </div>


    {{-- Emissions section --}}

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

        {{-- Emissions Trend --}}

        <div class="overview-card p-5">

            <div class="flex items-center justify-between mb-4">

                <div>
                    <h3 class="overview-card-title">
                        Emissions Trend
                    </h3>

                    <p class="overview-card-description mt-1">
                        Carbon emissions recorded over time
                    </p>
                </div>

                <select
                    id="trendFilter"
                    class="overview-select"
                >
                    <option value="daily">
                        Daily
                    </option>

                    <option value="weekly">
                        Weekly
                    </option>

                    <option value="monthly" selected>
                        Monthly
                    </option>
                </select>

            </div>

            <div class="overview-chart overview-chart-large">
                <canvas id="emissionsTrendChart"></canvas>
            </div>

        </div>


        {{-- Emission By Source --}}

        <div class="overview-card p-5">

            <div class="mb-4">

                <h3 class="overview-card-title">
                    Emission by Source
                </h3>

                <p class="overview-card-description mt-1">
                    Distribution of recorded carbon emissions
                </p>

            </div>

            <div class="flex items-center justify-center gap-5 h-[270px]">

                <div class="w-48 h-48 flex items-center justify-center shrink-0">

                    <canvas
                        id="emissionSourceChart"
                        data-transportation="{{ $transportationTotal }}"
                        data-electricity="{{ $electricityTotal }}"
                        data-food="{{ $foodTotal }}"
                    ></canvas>

                </div>

                <div class="flex-1 flex flex-col justify-center space-y-5 min-w-0">

                    <div class="flex items-center justify-between gap-3">

                        <div class="flex items-center gap-2 min-w-0">

                            <span class="w-3 h-3 rounded-full bg-green-800 shrink-0"></span>

                            <span class="overview-legend truncate">
                                Transportation
                            </span>

                        </div>

                        <span class="overview-small whitespace-nowrap">
                            {{ number_format($transportationTotal, 2) }} kg
                        </span>

                    </div>


                    <div class="flex items-center justify-between gap-3">

                        <div class="flex items-center gap-2 min-w-0">

                            <span class="w-3 h-3 rounded-full bg-green-500 shrink-0"></span>

                            <span class="overview-legend truncate">
                                Electricity
                            </span>

                        </div>

                        <span class="overview-small whitespace-nowrap">
                            {{ number_format($electricityTotal, 2) }} kg
                        </span>

                    </div>


                    <div class="flex items-center justify-between gap-3">

                        <div class="flex items-center gap-2 min-w-0">

                            <span class="w-3 h-3 rounded-full bg-yellow-400 shrink-0"></span>

                            <span class="overview-legend truncate">
                                Food
                            </span>

                        </div>

                        <span class="overview-small whitespace-nowrap">
                            {{ number_format($foodTotal, 2) }} kg
                        </span>

                    </div>

                </div>

            </div>

        </div>


       {{-- Top Emitting Departments --}}

<div class="overview-card p-5">

    <div class="mb-5">

        <h3 class="overview-card-title">
            Top Emitting Departments
        </h3>

        <p class="overview-card-description mt-1">
            Departments with recorded emissions
        </p>

    </div>


    @php
        $validDepartments = $topDepartments->filter(function ($department) {
            return !empty(trim((string) ($department->department ?? '')));
        })->values();
    @endphp


    @if($validDepartments->isEmpty())

        <div class="overview-empty">

            <div>

                <img
                    src="{{ asset('icons/report.png') }}"
                    class="w-9 h-9 object-contain mx-auto opacity-40 mb-3"
                    alt=""
                >

                <p class="overview-small">
                    No department data available.
                </p>

            </div>

        </div>

    @else

        <div class="space-y-6">

            @foreach($validDepartments as $department)

                @php
                    $departmentPercentage = max(
                        0,
                        min(
                            100,
                            (float) ($department->percentage ?? 0)
                        )
                    );
                @endphp


                <div>

                    <div class="flex items-start justify-between gap-4 mb-2">

                        <span class="overview-department-name">
                            {{ $department->department }}
                        </span>

                        <span class="overview-department-value whitespace-nowrap">
                            {{ number_format($department->total_emissions, 2) }} kg CO₂e
                        </span>

                    </div>


                    <div
                        class="w-full h-3 bg-gray-200 rounded-full overflow-hidden"
                        data-percentage="{{ $departmentPercentage }}"
                    >

                        <div
                            class="department-progress-bar h-full bg-green-700 rounded-full"
                            @style(['width' => $departmentPercentage . '%'])
                        ></div>

                    </div>


                    <p class="text-right overview-small mt-1">
                        {{ rtrim(rtrim(number_format($departmentPercentage, 2), '0'), '.') }}%
                    </p>

                </div>

            @endforeach

        </div>

    @endif

</div>
    </div>


    {{-- Forecast and activity section --}}

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

        {{-- Forecasted Emissions --}}

        <div class="overview-card p-5">

            <div class="flex items-center justify-between mb-4">

                <div>

                    <h3 class="overview-card-title">
                        Forecasted Emissions
                    </h3>

                    <p class="overview-card-description mt-1">
                        TFT forecast of future carbon emissions
                    </p>

                </div>

                <select
                    id="forecastFilter"
                    class="overview-select"
                >
                    <option value="7">
                        7 Days
                    </option>

                    <option value="30" selected>
                        30 Days
                    </option>
                </select>

            </div>

            <div
                id="forecastChartContainer"
                class="overview-chart overview-chart-small"
            >

                <canvas id="forecastChart"></canvas>

                <div
                    id="forecastEmpty"
                    class="overview-forecast-empty"
                    style="display: none;"
                >
                    No forecast data available.
                </div>

            </div>

        </div>


        {{-- User Engagement --}}

        <div class="overview-card p-5">

            <div class="mb-4">

                <h3 class="overview-card-title">
                    User Engagement
                </h3>

                <p class="overview-card-description mt-1">
                    Current user activity
                </p>

            </div>

            <div class="flex items-center justify-between gap-4">

                <div class="w-36 h-36 relative flex-shrink-0">

                    <canvas
                        id="engagementChart"
                        data-engagement-rate="{{ $engagementRate }}"
                    ></canvas>

                </div>

                <div class="flex-1 space-y-5">

                    <div class="flex items-center justify-between gap-3">

                        <div class="flex items-center gap-2">

                            <span class="w-3 h-3 rounded-full bg-green-700"></span>

                            <span class="overview-legend">
                                Active Today
                            </span>

                        </div>

                        <span class="overview-legend">
                            {{ $activeUsers }}
                        </span>

                    </div>


                    <div class="flex items-center justify-between gap-3">

                        <div class="flex items-center gap-2">

                            <span class="w-3 h-3 rounded-full bg-lime-500"></span>

                            <span class="overview-legend">
                                Active This Week
                            </span>

                        </div>

                        <span class="overview-legend">
                            {{ $activeUsers }}
                        </span>

                    </div>


                    <div class="flex items-center justify-between gap-3">

                        <div class="flex items-center gap-2">

                            <span class="w-3 h-3 rounded-full bg-blue-500"></span>

                            <span class="overview-legend">
                                New This Month
                            </span>

                        </div>

                        <span class="overview-legend">
                            {{ $totalUsers }}
                        </span>

                    </div>

                </div>

            </div>

        </div>


        {{-- Recent Alerts --}}

        <div class="overview-card p-5">

            <div class="mb-4">

                <h3 class="overview-card-title">
                    Recent Alerts
                </h3>

                <p class="overview-card-description mt-1">
                    Latest system notifications
                </p>

            </div>

            @if($recentAlerts->isEmpty())

                <div class="overview-empty">

                    <p class="overview-small">
                        No alerts available.
                    </p>

                </div>

            @else

                <div class="space-y-4">

                    @foreach($recentAlerts as $alert)

                        <div class="flex items-center justify-between gap-3 border-b border-gray-100 pb-3">

                            <div class="flex items-center gap-3 min-w-0">

                                @if($alert->severity === 'critical')

                                    <div class="w-9 h-9 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                                        <span class="text-red-600 font-bold">
                                            !
                                        </span>
                                    </div>

                                @elseif($alert->severity === 'warning')

                                    <div class="w-9 h-9 rounded-full bg-yellow-100 flex items-center justify-center shrink-0">
                                        <span class="text-yellow-600 font-bold">
                                            !
                                        </span>
                                    </div>

                                @else

                                    <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                                        <span class="text-blue-600 font-semibold">
                                            i
                                        </span>
                                    </div>

                                @endif

                                <div class="min-w-0">

                                    <p class="overview-alert-title truncate">
                                        {{ $alert->title }}
                                    </p>

                                    <p class="overview-alert-message truncate">
                                        {{ Str::limit($alert->message, 45) }}
                                    </p>

                                </div>

                            </div>

                            <span class="overview-alert-time">
                                {{ $alert->created_at->diffForHumans() }}
                            </span>

                        </div>

                    @endforeach

                </div>

            @endif

        </div>

    </div>


    {{-- Recommended mitigation strategies --}}

    <div class="overview-card p-5">

        <div class="mb-4">

            <h3 class="overview-card-title">
                Recommended Mitigation Strategies
            </h3>

            <p class="overview-card-description mt-1">
                Suggested actions for reducing carbon emissions
            </p>

        </div>

        @if($recommendedStrategies->isEmpty())

            <p class="overview-small">
                No mitigation strategies available.
            </p>

        @else

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">

                @foreach($recommendedStrategies as $strategy)

                    <div class="flex items-start gap-3 border-r last:border-r-0 pr-4">

                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center shrink-0">

                            <img
                                src="{{ asset('icons/leaf.png') }}"
                                class="w-5 h-5 object-contain"
                                alt="Mitigation"
                            >

                        </div>

                        <div class="min-w-0">

                            <h4 class="overview-strategy-title">
                                {{ $strategy->title }}
                            </h4>

                            <p class="overview-strategy-description mt-1">
                                {{ $strategy->description }}
                            </p>

                            @if(isset($strategy->carbon_reduced))

                                <p class="overview-strategy-saving mt-2">
                                    Saves {{ number_format($strategy->carbon_reduced, 2) }} kg CO₂e
                                </p>

                            @endif

                        </div>

                    </div>

                @endforeach

            </div>

        @endif

    </div>


    {{-- Data for JavaScript --}}

    <script id="daily-emissions-data" type="application/json">
        @json($dailyEmissions ?? [])
    </script>

    <script id="weekly-emissions-data" type="application/json">
        @json($weeklyEmissions ?? [])
    </script>

    <script id="monthly-emissions-data" type="application/json">
        @json($monthlyEmissions ?? [])
    </script>

    <script id="forecast-data" type="application/json">
        @json($dashboardForecastValues->values())
    </script>

    <script id="forecast-labels" type="application/json">
        @json($dashboardForecastLabels->values())
    </script>

</div>

@endsection


@push('scripts')

<script>
document.addEventListener('DOMContentLoaded', function () {

    if (typeof Chart === 'undefined') {
        return;
    }


    Chart.defaults.font.family = 'Poppins, sans-serif';
    Chart.defaults.font.size = 13;
    Chart.defaults.color = '#667085';


    // Helper for converting Laravel chart data into numbers.

    function normalizeData(data) {

        if (!Array.isArray(data)) {
            return [];
        }

        return data.map(function (item) {

            if (item === null || item === undefined) {
                return 0;
            }

            if (typeof item === 'object') {

                if (item.total_emission !== undefined) {
                    return Number(item.total_emission) || 0;
                }

                if (item.total_emissions !== undefined) {
                    return Number(item.total_emissions) || 0;
                }

                if (item.predicted_total_emission !== undefined) {
                    return Number(item.predicted_total_emission) || 0;
                }

                if (item.forecast !== undefined) {
                    return Number(item.forecast) || 0;
                }

                if (item.emission !== undefined) {
                    return Number(item.emission) || 0;
                }

                if (item.value !== undefined) {
                    return Number(item.value) || 0;
                }

                return 0;
            }

            return Number(item) || 0;
        });
    }


    // Department progress bars.

    document
        .querySelectorAll('[data-percentage]')
        .forEach(function (container) {

            const percentage = Number(
                container.dataset.percentage || 0
            );

            const safePercentage = Math.max(
                0,
                Math.min(100, percentage)
            );

            const bar = container.querySelector(
                '.department-progress-bar'
            );

            if (bar) {
                bar.style.setProperty(
                    'width',
                    safePercentage + '%',
                    'important'
                );
            }

        });


    // Emission by source.

    const sourceCanvas = document.getElementById(
        'emissionSourceChart'
    );

    if (sourceCanvas) {

        const chartData = [
            Number(sourceCanvas.dataset.transportation || 0),
            Number(sourceCanvas.dataset.electricity || 0),
            Number(sourceCanvas.dataset.food || 0)
        ];

        const totalEmission = chartData.reduce(
            function (total, value) {
                return total + value;
            },
            0
        );


        const centerTextPlugin = {

            id: 'sourceCenterText',

            afterDraw: function (chart) {

                const meta = chart.getDatasetMeta(0);

                if (!meta.data.length) {
                    return;
                }

                const ctx = chart.ctx;

                const x = meta.data[0].x;
                const y = meta.data[0].y;

                ctx.save();

                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';

                ctx.font = '700 18px Poppins';
                ctx.fillStyle = '#111827';

                ctx.fillText(
                    totalEmission.toLocaleString(
                        undefined,
                        {
                            maximumFractionDigits: 2
                        }
                    ),
                    x,
                    y - 9
                );

                ctx.font = '400 12px Poppins';
                ctx.fillStyle = '#6b7280';

                ctx.fillText(
                    'kg CO₂e',
                    x,
                    y + 13
                );

                ctx.restore();
            }
        };


        new Chart(
            sourceCanvas,
            {
                type: 'doughnut',

                plugins: [
                    centerTextPlugin
                ],

                data: {
                    labels: [
                        'Transportation',
                        'Electricity',
                        'Food'
                    ],

                    datasets: [
                        {
                            data: chartData,

                            backgroundColor: [
                                '#1B5E20',
                                '#66BB6A',
                                '#FBC02D'
                            ],

                            borderWidth: 0
                        }
                    ]
                },

                options: {

                    responsive: true,

                    maintainAspectRatio: false,

                    cutout: '72%',

                    plugins: {

                        legend: {
                            display: false
                        },

                        tooltip: {

                            titleFont: {
                                family: 'Poppins',
                                size: 11,
                                weight: '600'
                            },

                            bodyFont: {
                                family: 'Poppins',
                                size: 11
                            },

                            callbacks: {

                                label: function (context) {

                                    return (
                                        context.label +
                                        ': ' +
                                        Number(
                                            context.raw || 0
                                        ).toLocaleString(
                                            undefined,
                                            {
                                                maximumFractionDigits: 2
                                            }
                                        ) +
                                        ' kg'
                                    );

                                }

                            }

                        }

                    }

                }

            }
        );

    }


    // Emissions trend.

    const trendCanvas = document.getElementById(
        'emissionsTrendChart'
    );

    if (trendCanvas) {

        const dailyElement = document.getElementById(
            'daily-emissions-data'
        );

        const weeklyElement = document.getElementById(
            'weekly-emissions-data'
        );

        const monthlyElement = document.getElementById(
            'monthly-emissions-data'
        );


        let dailyData = dailyElement
            ? JSON.parse(
                dailyElement.textContent || '[]'
            )
            : [];

        let weeklyData = weeklyElement
            ? JSON.parse(
                weeklyElement.textContent || '[]'
            )
            : [];

        let monthlyData = monthlyElement
            ? JSON.parse(
                monthlyElement.textContent || '[]'
            )
            : [];


        dailyData = normalizeData(dailyData);
        weeklyData = normalizeData(weeklyData);
        monthlyData = normalizeData(monthlyData);


        const monthlyLabels = [
            'Jan',
            'Feb',
            'Mar',
            'Apr',
            'May',
            'Jun',
            'Jul',
            'Aug',
            'Sep',
            'Oct',
            'Nov',
            'Dec'
        ];


        const dailyLabels = dailyData.map(
            function (_, index) {
                return 'Day ' + (index + 1);
            }
        );


        const weeklyLabels = weeklyData.map(
            function (_, index) {
                return 'Week ' + (index + 1);
            }
        );


        const trendDataSets = {

            daily: {
                labels: dailyLabels,
                data: dailyData
            },

            weekly: {
                labels: weeklyLabels,
                data: weeklyData
            },

            monthly: {
                labels: monthlyLabels,
                data: monthlyData
            }

        };


        const initialTrend = trendDataSets.monthly;


        const trendChart = new Chart(
            trendCanvas,
            {

                type: 'line',

                data: {

                    labels: initialTrend.labels,

                    datasets: [
                        {

                            label: 'CO₂ Emissions',

                            data: initialTrend.data,

                            borderColor: '#2E7D32',

                            backgroundColor:
                                'rgba(46, 125, 50, 0.12)',

                            fill: true,

                            tension: 0.35,

                            pointRadius: 3,

                            pointHoverRadius: 5,

                            borderWidth: 2.5

                        }
                    ]

                },

                options: {

                    responsive: true,

                    maintainAspectRatio: false,

                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },

                    plugins: {

                        legend: {
                            display: false
                        },

                        tooltip: {

                            titleFont: {
                                family: 'Poppins',
                                size: 11,
                                weight: '600'
                            },

                            bodyFont: {
                                family: 'Poppins',
                                size: 11
                            },

                            callbacks: {

                                label: function (context) {

                                    return (
                                        Number(
                                            context.raw || 0
                                        ).toLocaleString(
                                            undefined,
                                            {
                                                maximumFractionDigits: 2
                                            }
                                        ) +
                                        ' kg CO₂e'
                                    );

                                }

                            }

                        }

                    },

                    scales: {

                        y: {

                            beginAtZero: true,

                            grid: {
                                color: '#e5e7eb'
                            },

                            ticks: {

                                font: {
                                    family: 'Poppins',
                                    size: 12
                                },

                                color: '#6b7280',

                                callback: function (value) {

                                    return Number(
                                        value
                                    ).toLocaleString();

                                }

                            }

                        },

                        x: {

                            grid: {
                                display: false
                            },

                            ticks: {

                                font: {
                                    family: 'Poppins',
                                    size: 12
                                },

                                color: '#6b7280',

                                maxTicksLimit: 12

                            }

                        }

                    }

                }

            }
        );


        const trendFilter = document.getElementById(
            'trendFilter'
        );


        if (trendFilter) {

            trendFilter.addEventListener(
                'change',
                function () {

                    const selected = this.value;

                    const selectedData =
                        trendDataSets[selected];

                    if (!selectedData) {
                        return;
                    }


                    trendChart.data.labels =
                        selectedData.labels;

                    trendChart.data.datasets[0].data =
                        selectedData.data;

                    trendChart.data.datasets[0].label =
                        selected.charAt(0).toUpperCase() +
                        selected.slice(1) +
                        ' CO₂ Emissions';


                    trendChart.update();

                }
            );

        }

    }


    // Forecasted emissions.
// This uses the actual TFT values supplied by Laravel.

    const forecastCanvas = document.getElementById(
        'forecastChart'
    );

    const forecastEmpty = document.getElementById(
        'forecastEmpty'
    );

    const forecastDataElement =
        document.getElementById('forecast-data');

    const forecastLabelsElement =
        document.getElementById('forecast-labels');


    if (forecastCanvas) {

        let forecastValues = forecastDataElement
            ? JSON.parse(
                forecastDataElement.textContent || '[]'
            )
            : [];

        let forecastLabels = forecastLabelsElement
            ? JSON.parse(
                forecastLabelsElement.textContent || '[]'
            )
            : [];


        forecastValues = normalizeData(
            forecastValues
        );


        if (!Array.isArray(forecastLabels)) {
            forecastLabels = [];
        }


        const validLength = Math.min(
            forecastValues.length,
            forecastLabels.length
        );


        forecastValues =
            forecastValues.slice(
                0,
                validLength
            );

        forecastLabels =
            forecastLabels.slice(
                0,
                validLength
            );


        const hasForecast =
            forecastValues.length > 0;


        if (!hasForecast) {

            forecastCanvas.style.display = 'none';

            if (forecastEmpty) {
                forecastEmpty.style.display = 'flex';
            }

        } else {

            if (forecastEmpty) {
                forecastEmpty.style.display = 'none';
            }


            const formattedLabels =
                forecastLabels.map(function (date) {

                    const parsedDate =
                        new Date(date);

                    if (Number.isNaN(
                        parsedDate.getTime()
                    )) {
                        return date;
                    }

                    return parsedDate.toLocaleDateString(
                        'en-US',
                        {
                            month: 'short',
                            day: 'numeric'
                        }
                    );

                });


            const forecastChart = new Chart(
                forecastCanvas,
                {

                    type: 'line',

                    data: {

                        labels: formattedLabels,

                        datasets: [
                            {

                                label: 'TFT Forecast',

                                data: forecastValues,

                                borderColor: '#2F7D57',

                                backgroundColor:
                                    'rgba(47, 125, 87, 0.10)',

                                fill: true,

                                tension: 0.35,

                                pointRadius: 2.5,

                                pointHoverRadius: 5,

                                borderWidth: 2.5

                            }
                        ]

                    },

                    options: {

                        responsive: true,

                        maintainAspectRatio: false,

                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },

                        plugins: {

                            legend: {
                                display: false
                            },

                            tooltip: {

                                titleFont: {
                                    family: 'Poppins',
                                    size: 11,
                                    weight: '600'
                                },

                                bodyFont: {
                                    family: 'Poppins',
                                    size: 11
                                },

                                callbacks: {

                                    label: function (context) {

                                        return (
                                            Number(
                                                context.raw || 0
                                            ).toLocaleString(
                                                undefined,
                                                {
                                                    minimumFractionDigits: 2,
                                                    maximumFractionDigits: 2
                                                }
                                            ) +
                                            ' kg CO₂e'
                                        );

                                    }

                                }

                            }

                        },

                        scales: {

                            y: {

                                beginAtZero: true,

                                grid: {
                                    color: '#e5e7eb'
                                },

                                ticks: {

                                    font: {
                                        family: 'Poppins',
                                        size: 12
                                    },

                                    color: '#6b7280',

                                    callback: function (value) {

                                        return Number(
                                            value
                                        ).toLocaleString();

                                    }

                                }

                            },

                            x: {

                                grid: {
                                    display: false
                                },

                                ticks: {

                                    font: {
                                        family: 'Poppins',
                                        size: 12
                                    },

                                    color: '#6b7280',

                                    maxTicksLimit: 7

                                }

                            }

                        }

                    }

                }
            );


            const forecastFilter =
                document.getElementById(
                    'forecastFilter'
                );


            if (forecastFilter) {

                forecastFilter.addEventListener(
                    'change',
                    function () {

                        const days =
                            Number(this.value);


                        const values =
                            forecastValues.slice(
                                0,
                                days
                            );


                        const labels =
                            formattedLabels.slice(
                                0,
                                days
                            );


                        forecastChart.data.labels =
                            labels;

                        forecastChart.data.datasets[0].data =
                            values;


                        forecastChart.update();

                    }
                );

            }

        }

    }


    // User engagement.

    const engagementCanvas =
        document.getElementById(
            'engagementChart'
        );


    if (engagementCanvas) {

        const engagementRate =
            Math.max(
                0,
                Math.min(
                    100,
                    Number(
                        engagementCanvas.dataset.engagementRate || 0
                    )
                )
            );


        const engagementPlugin = {

            id: 'engagementCenter',

            afterDraw: function (chart) {

                const meta =
                    chart.getDatasetMeta(0);

                if (!meta.data.length) {
                    return;
                }

                const ctx = chart.ctx;

                const x = meta.data[0].x;
                const y = meta.data[0].y;

                ctx.save();

                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';

                ctx.font =
                    '700 24px Poppins';

                ctx.fillStyle =
                    '#2E7D32';

                ctx.fillText(
                    engagementRate.toFixed(0) + '%',
                    x,
                    y - 8
                );


                ctx.font =
                    '400 12px Poppins';

                ctx.fillStyle =
                    '#6b7280';

                ctx.fillText(
                    'Active Users',
                    x,
                    y + 13
                );

                ctx.restore();

            }

        };


        new Chart(
            engagementCanvas,
            {

                type: 'doughnut',

                data: {

                    datasets: [
                        {

                            data: [
                                engagementRate,
                                100 - engagementRate
                            ],

                            backgroundColor: [
                                '#2E7D32',
                                '#E5E7EB'
                            ],

                            borderWidth: 0

                        }
                    ]

                },

                options: {

                    cutout: '78%',

                    responsive: true,

                    maintainAspectRatio: false,

                    plugins: {

                        legend: {
                            display: false
                        }

                    }

                },

                plugins: [
                    engagementPlugin
                ]

            }
        );

    }

});
</script>

@endpush