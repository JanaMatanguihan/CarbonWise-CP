@extends('layouts.admin')

@section('page-title', 'Emission Overview')
@section('page-subtitle', 'Monitor and analyze carbon emissions')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

    .emissions-page {
        font-family: 'Poppins', sans-serif;
        color: #111111;
    }

    /* ================================
       Main Cards
    ================================= */

    .emissions-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.12);
    }

    /* ================================
       Summary Cards
    ================================= */

    .emissions-summary-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.12);
        min-height: 112px;
    }

    .emissions-card-label {
        font-size: 11px;
        line-height: 16px;
        font-weight: 500;
        color: #111111;
    }

    .emissions-main-value {
        font-size: 23px;
        line-height: 30px;
        font-weight: 700;
        color: #111111;
        letter-spacing: -0.3px;
    }

    .emissions-unit {
        font-size: 9px;
        line-height: 13px;
        font-weight: 400;
        color: #333333;
    }

    .emissions-small-text {
        font-size: 9px;
        line-height: 14px;
        font-weight: 400;
        color: #9ca3af;
    }

    .emissions-percentage {
        font-size: 9px;
        line-height: 14px;
        font-weight: 500;
        color: #6b7280;
    }

    /* ================================
       Summary Icons
    ================================= */

    .emissions-icon-box {
        width: 48px;
        height: 48px;
        min-width: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        overflow: hidden;
    }

    .emissions-icon {
        width: 34px;
        height: 34px;
        object-fit: contain;
        display: block;
    }

    .icon-fallback {
        display: none;
        align-items: center;
        justify-content: center;
    }

    .icon-fallback svg {
        width: 27px;
        height: 27px;
    }

    /* ================================
       Chart Titles
    ================================= */

    .emissions-chart-title {
        font-size: 15px;
        line-height: 21px;
        font-weight: 700;
        color: #111111;
    }

    .emissions-chart-description {
        font-size: 10px;
        line-height: 15px;
        font-weight: 400;
        color: #9ca3af;
    }

    /* ================================
       Filters
    ================================= */

    .emissions-filter-label {
        font-size: 10px;
        line-height: 15px;
        font-weight: 500;
        color: #111111;
    }

    .emissions-filter-input {
        font-family: 'Poppins', sans-serif;
        font-size: 10px;
        line-height: 15px;
        color: #111111;
    }

    .emissions-export-btn {
        font-family: 'Poppins', sans-serif;
        font-size: 10px;
        line-height: 15px;
        font-weight: 500;
    }

    /* ================================
       Source Details
    ================================= */

    .emissions-source-name {
        font-size: 10px;
        line-height: 15px;
        font-weight: 500;
        color: #374151;
    }

    .emissions-source-percent {
        font-size: 9px;
        line-height: 14px;
        font-weight: 600;
        color: #4b5563;
    }

    .emissions-source-value {
        font-size: 9px;
        line-height: 14px;
        font-weight: 400;
        color: #9ca3af;
    }

    /* ================================
       Department
    ================================= */

    .emissions-department-name {
        font-size: 10px;
        line-height: 15px;
        font-weight: 500;
        color: #4b5563;
    }

    .emissions-department-value {
        font-size: 9px;
        line-height: 14px;
        font-weight: 500;
        color: #6b7280;
    }

    .emissions-department-percent {
        font-size: 9px;
        line-height: 14px;
        font-weight: 400;
        color: #6b7280;
    }

    /* ================================
       Responsive
    ================================= */

    @media (max-width: 768px) {

        .emissions-main-value {
            font-size: 20px;
            line-height: 28px;
        }

        .emissions-chart-title {
            font-size: 14px;
            line-height: 20px;
        }

        .emissions-icon-box {
            width: 44px;
            height: 44px;
            min-width: 44px;
        }

        .emissions-icon {
            width: 31px;
            height: 31px;
        }

    }
</style>


<div class="emissions-page bg-[#f1f1ee] min-h-screen p-5 md:p-6 space-y-5 -mx-6 -mt-6 pb-10 w-[calc(100%_+_3rem)]">


    <!-- =========================================
         FILTERS
    ========================================== -->

    <form
        method="GET"
        action="{{ route('admin.emissions') }}"
        class="flex flex-col md:flex-row md:justify-end md:items-end gap-3"
    >

        <!-- Month -->

        <div>

            <label class="emissions-filter-label block mb-1 md:hidden">
                Month
            </label>

            <input
                type="month"
                name="month"
                value="{{ request('month') }}"
                onchange="this.form.submit()"
                class="emissions-filter-input border border-gray-300 rounded-md px-3 py-2 bg-white shadow-sm focus:outline-none focus:ring-1 focus:ring-gray-300"
            >

        </div>


        <!-- Department -->

        <div class="flex items-center gap-2">

            <span class="emissions-filter-label whitespace-nowrap">
                Filter by:
            </span>

            <select
                name="department"
                onchange="this.form.submit()"
                class="emissions-filter-input border border-gray-300 rounded-md px-3 py-2 bg-white shadow-sm min-w-[160px] focus:outline-none focus:ring-1 focus:ring-gray-300"
            >

                <option value="">
                    All Departments
                </option>

                @foreach($departments as $department)

                    <option
                        value="{{ $department }}"
                        {{ request('department') == $department ? 'selected' : '' }}
                    >
                        {{ $department }}
                    </option>

                @endforeach

            </select>

        </div>


        <!-- Export -->

        <a
            id="exportBtn"
            href="{{ route('admin.emissions.export', request()->query()) }}"
            class="emissions-export-btn inline-flex items-center justify-center bg-[#2f7d57] hover:bg-[#256847] text-white px-4 py-2 rounded-md shadow-sm transition"
        >
            Export Report
        </a>

    </form>


    <!-- =========================================
         SUMMARY CARDS
    ========================================== -->

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">


        <!-- Total Emissions -->

        <div class="emissions-summary-card p-4">

            <div class="flex items-center gap-4">

                <!-- Icon -->

                <div class="emissions-icon-box bg-blue-50">

                    <img
                        src="{{ asset('images/emissions/cloud.png') }}"
                        alt="Total Emissions"
                        class="emissions-icon"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                    >

                    <span class="icon-fallback">

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="#3b82f6"
                            stroke-width="1.8"
                        >

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 19h12a4 4 0 0 0 .4-7.98A6 6 0 0 0 6.3 9.3 4 4 0 0 0 6 19Z"
                            />

                        </svg>

                    </span>

                </div>


                <!-- Text -->

                <div class="min-w-0">

                    <p class="emissions-card-label">
                        Total Emissions
                    </p>

                    <div class="flex items-baseline gap-1 mt-1">

                        <h2 class="emissions-main-value">
                            {{ number_format($totalEmissions, 2) }}
                        </h2>

                        <span class="emissions-unit">
                            kg CO₂e
                        </span>

                    </div>

                    <p class="emissions-small-text mt-1">
                        Total recorded emissions
                    </p>

                </div>

            </div>

        </div>


        <!-- Transportation -->

        <div class="emissions-summary-card p-4">

            <div class="flex items-center gap-4">

                <!-- Icon -->

                <div class="emissions-icon-box bg-green-50">

                    <img
                        src="{{ asset('images/emissions/car.png') }}"
                        alt="Transportation"
                        class="emissions-icon"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                    >

                    <span class="icon-fallback">

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="#16a34a"
                            stroke-width="1.8"
                        >

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M5 17h14l1-5-2-5H6l-2 5 1 5Zm0 0v2m14-2v2M7 17h.01M17 17h.01M6 12h12"
                            />

                        </svg>

                    </span>

                </div>


                <!-- Text -->

                <div class="min-w-0">

                    <p class="emissions-card-label">
                        Transportation
                    </p>

                    <div class="flex items-baseline gap-1 mt-1">

                        <h2 class="emissions-main-value">
                            {{ number_format($transportation, 2) }}
                        </h2>

                        <span class="emissions-unit">
                            kg CO₂e
                        </span>

                    </div>

                    <p class="emissions-percentage mt-1">
                        ({{ number_format($transportationPercentage, 1) }}%)
                    </p>

                </div>

            </div>

        </div>


        <!-- Electricity -->

        <div class="emissions-summary-card p-4">

            <div class="flex items-center gap-4">

                <!-- Icon -->

                <div class="emissions-icon-box bg-yellow-50">

                    <img
                        src="{{ asset('images/emissions/lightbulb.png') }}"
                        alt="Electricity"
                        class="emissions-icon"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                    >

                    <span class="icon-fallback">

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="#eab308"
                            stroke-width="1.8"
                        >

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 18h6M10 21h4M8.5 14.5A6 6 0 1 1 15.5 14.5c-.7.6-1 1.1-1 2.5h-5c0-1.4-.3-1.9-1-2.5Z"
                            />

                        </svg>

                    </span>

                </div>


                <!-- Text -->

                <div class="min-w-0">

                    <p class="emissions-card-label">
                        Electricity
                    </p>

                    <div class="flex items-baseline gap-1 mt-1">

                        <h2 class="emissions-main-value">
                            {{ number_format($electricity, 2) }}
                        </h2>

                        <span class="emissions-unit">
                            kg CO₂e
                        </span>

                    </div>

                    <p class="emissions-percentage mt-1">
                        ({{ number_format($electricityPercentage, 1) }}%)
                    </p>

                </div>

            </div>

        </div>


        <!-- Food Consumption -->

        <div class="emissions-summary-card p-4">

            <div class="flex items-center gap-4">

                <!-- Icon -->

                <div class="emissions-icon-box bg-red-50">

                    <img
                        src="{{ asset('images/emissions/food.png') }}"
                        alt="Food Consumption"
                        class="emissions-icon"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                    >

                    <span class="icon-fallback">

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="#ef4444"
                            stroke-width="1.8"
                        >

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M5 14h14a1 1 0 0 1 1 1c0 3-3 5-8 5s-8-2-8-5a1 1 0 0 1 1-1Zm2-1c0-3 2-5 5-5s5 2 5 5M9 8c0-2 1.3-3 3-3s3 1.5 3 3"
                            />

                        </svg>

                    </span>

                </div>


                <!-- Text -->

                <div class="min-w-0">

                    <p class="emissions-card-label">
                        Food Consumption
                    </p>

                    <div class="flex items-baseline gap-1 mt-1">

                        <h2 class="emissions-main-value">
                            {{ number_format($food, 2) }}
                        </h2>

                        <span class="emissions-unit">
                            kg CO₂e
                        </span>

                    </div>

                    <p class="emissions-percentage mt-1">
                        ({{ number_format($foodPercentage, 1) }}%)
                    </p>

                </div>

            </div>

        </div>

    </div>


    <!-- =========================================
         ROW 1
    ========================================== -->

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">


        <!-- Emissions Over Time -->

        <div class="emissions-card p-5 h-[380px] flex flex-col">

            <div class="flex justify-between items-start gap-3">

                <div>

                    <h3 class="emissions-chart-title">
                        Emissions Over Time
                    </h3>

                    <p class="emissions-chart-description mt-1">
                        Track carbon emissions over time
                    </p>

                </div>


                <select
                    id="trendFilter"
                    class="emissions-filter-input border border-gray-300 rounded-md px-3 py-1.5 bg-white shadow-sm min-w-[105px] focus:outline-none"
                >

                    <option value="yearly">
                        Yearly
                    </option>

                    <option value="monthly" selected>
                        Monthly
                    </option>

                    <option value="weekly">
                        Weekly
                    </option>

                </select>

            </div>


            <div class="flex-1 flex items-center justify-center">

                <div
                    id="emissionTrendChart"
                    class="w-full"
                ></div>

            </div>

        </div>


        <!-- Emissions by Source -->

        <div class="emissions-card p-5 h-[380px] flex flex-col">

            <div>

                <h3 class="emissions-chart-title">
                    Emissions by Source
                </h3>

                <p class="emissions-chart-description mt-1">
                    Distribution of emissions by source
                </p>

            </div>


            <div class="grid grid-cols-1 sm:grid-cols-[45%_55%] gap-3 items-center flex-1">

                <!-- Donut -->

                <div class="flex justify-center">

                    <div
                        id="emissionSourceWrapper"
                        class="w-full max-w-[250px]"
                    >

                        <div id="emissionSourceChart"></div>


                        <div
                            id="emptyDonut"
                            class="hidden items-center justify-center h-44"
                        >

                            <div class="w-32 h-32 rounded-full border-[12px] border-gray-200 flex flex-col items-center justify-center">

                                <span class="text-xl font-bold text-gray-400">
                                    0
                                </span>

                                <span class="text-[9px] text-gray-400">
                                    kg CO₂e
                                </span>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- Source Details -->

                <div class="space-y-5 pr-1">


                    <!-- Transportation -->

                    <div class="flex items-center gap-2">

                        <span class="w-2.5 h-2.5 rounded-full bg-green-600 flex-shrink-0"></span>

                        <div class="flex-1 min-w-0">

                            <div class="flex justify-between items-center gap-2">

                                <span class="emissions-source-name">
                                    Transportation
                                </span>

                                <span class="emissions-source-percent">
                                    {{ number_format($transportationPercentage, 1) }}%
                                </span>

                            </div>

                            <p class="emissions-source-value mt-0.5">
                                {{ number_format($transportation, 2) }} kg CO₂e
                            </p>

                        </div>

                    </div>


                    <!-- Electricity -->

                    <div class="flex items-center gap-2">

                        <span class="w-2.5 h-2.5 rounded-full bg-yellow-500 flex-shrink-0"></span>

                        <div class="flex-1 min-w-0">

                            <div class="flex justify-between items-center gap-2">

                                <span class="emissions-source-name">
                                    Electricity
                                </span>

                                <span class="emissions-source-percent">
                                    {{ number_format($electricityPercentage, 1) }}%
                                </span>

                            </div>

                            <p class="emissions-source-value mt-0.5">
                                {{ number_format($electricity, 2) }} kg CO₂e
                            </p>

                        </div>

                    </div>


                    <!-- Food Consumption -->

                    <div class="flex items-center gap-2">

                        <span class="w-2.5 h-2.5 rounded-full bg-red-500 flex-shrink-0"></span>

                        <div class="flex-1 min-w-0">

                            <div class="flex justify-between items-center gap-2">

                                <span class="emissions-source-name">
                                    Food Consumption
                                </span>

                                <span class="emissions-source-percent">
                                    {{ number_format($foodPercentage, 1) }}%
                                </span>

                            </div>

                            <p class="emissions-source-value mt-0.5">
                                {{ number_format($food, 2) }} kg CO₂e
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- =========================================
         ROW 2
    ========================================== -->

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">


        <!-- Emissions by Department -->

        <div class="emissions-card p-5 h-[380px] flex flex-col">

            <div class="mb-5">

                <h3 class="emissions-chart-title">
                    Emissions by Department / College
                </h3>

                <p class="emissions-chart-description mt-1">
                    Compare emissions across departments and colleges
                </p>

            </div>


            @php
                $overallTotal = $departmentEmissions->sum('total');
            @endphp


            <div class="space-y-5 overflow-y-auto flex-1 pr-1">

                @foreach($departmentEmissions as $department)

                    <div>

                        <div class="flex justify-between items-center mb-1.5">

                            <span class="emissions-department-name">
                                {{ $department->department }}
                            </span>

                            <span class="emissions-department-value">
                                {{ number_format($department->total, 2) }} kg CO₂e
                            </span>

                        </div>


                        @php
                            $width = $overallTotal > 0
                                ? round(($department->total / $overallTotal) * 100, 1)
                                : 0;
                        @endphp


                        <div class="w-full bg-gray-100 rounded-full overflow-hidden h-2.5">

                            <div
                                class="bg-[#2f7d57] h-full rounded-full transition-all duration-500"
                                data-department-width="{{ $width }}"
                            ></div>

                        </div>


                        <p class="emissions-department-percent text-right mt-1">
                            {{ $width }}%
                        </p>

                    </div>

                @endforeach

            </div>

        </div>


        <!-- Emissions Comparison -->

        <div class="emissions-card p-5 h-[380px] flex flex-col">

            <div class="flex justify-between items-start gap-3">

                <div>

                    <h3 class="emissions-chart-title">
                        Emissions Comparison
                    </h3>

                    <p class="emissions-chart-description mt-1">
                        Compare current and previous emissions
                    </p>

                </div>


                <select
                    class="emissions-filter-input border border-gray-300 rounded-md px-3 py-1.5 bg-white text-gray-600 shadow-sm min-w-[170px]"
                >

                    <option>
                        This Month vs Last Month
                    </option>

                </select>

            </div>


            <div class="flex-1 flex items-center justify-center">

                <div
                    id="comparisonChart"
                    class="w-full"
                ></div>

            </div>

        </div>

    </div>

</div>


<!-- =========================================
     CHART DATA
========================================== -->

@push('scripts')

<script id="yearly-data" type="application/json">
    {!! $yearlyTrend->toJson() !!}
</script>

<script id="weekly-data" type="application/json">
    {!! $weeklyTrend->toJson() !!}
</script>

<script id="monthly-data" type="application/json">
    {!! $monthlyTrend->toJson() !!}
</script>

<script id="emission-source-data" type="application/json">
{!! json_encode([
    $transportation,
    $electricity,
    $food
]) !!}
</script>

<script id="total-emission" type="application/json">
    {!! json_encode($totalEmissions) !!}
</script>

<script id="comparison-data" type="application/json">
    {!! json_encode($comparisonData) !!}
</script>


<script>

    /* =========================================
       Department Progress Bars
    ========================================== */

    document
        .querySelectorAll('[data-department-width]')
        .forEach(function (bar) {

            const width =
                bar.getAttribute('data-department-width');

            if (width !== null) {

                bar.style.width =
                    width + '%';

            }

        });


    /* =========================================
       Load Chart Data
    ========================================== */

    const yearlyData = JSON.parse(
        document.getElementById('yearly-data').textContent
    );

    const weeklyData = JSON.parse(
        document.getElementById('weekly-data').textContent
    );

    const monthlyData = JSON.parse(
        document.getElementById('monthly-data').textContent
    );

    const emissionSources = JSON.parse(
        document.getElementById('emission-source-data').textContent
    ).map(Number);

    const totalEmission = JSON.parse(
        document.getElementById('total-emission').textContent
    );


    /* =========================================
       Emissions Over Time
    ========================================== */

    let currentData = monthlyData;

    let labels = currentData.length
        ? currentData.map(item => item.label)
        : ['No Data'];

    let totals = currentData.length
        ? currentData.map(item => item.total)
        : [0];


    const trendChart = new ApexCharts(
        document.querySelector('#emissionTrendChart'),
        {

            chart: {
                type: 'area',
                height: 280,
                toolbar: {
                    show: false
                },
                fontFamily: 'Poppins, sans-serif'
            },


            series: [
                {
                    name: 'CO₂e',
                    data: totals
                }
            ],


            xaxis: {

                categories: labels,

                labels: {

                    rotate: -45,

                    hideOverlappingLabels: true,

                    style: {
                        fontFamily: 'Poppins, sans-serif',
                        fontSize: '9px'
                    }

                },

                tickAmount: 8

            },


            yaxis: {

                labels: {

                    formatter: function (val) {

                        return val.toFixed(0);

                    },

                    style: {
                        fontFamily: 'Poppins, sans-serif',
                        fontSize: '9px'
                    }

                }

            },


            stroke: {

                curve: 'smooth',

                width: 3

            },


            fill: {

                type: 'gradient',

                gradient: {

                    opacityFrom: 0.3,

                    opacityTo: 0.05

                }

            },


            colors: [
                '#2f7d57'
            ],


            dataLabels: {
                enabled: false
            },


            grid: {

                borderColor: '#eeeeee',

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
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }
                        ) + ' kg CO₂e';

                    }

                }

            }

        }
    );


    trendChart.render();


    /* =========================================
       Trend Filter
    ========================================== */

    document
        .getElementById('trendFilter')
        .addEventListener('change', function () {

            let selected =
                this.value;

            let data;


            if (selected === 'yearly') {

                data =
                    yearlyData;

            }
            else if (selected === 'monthly') {

                data =
                    monthlyData;

            }
            else {

                data =
                    weeklyData;

            }


            const updatedLabels =
                data.length
                    ? data.map(item => item.label)
                    : ['No Data'];


            const updatedTotals =
                data.length
                    ? data.map(item => item.total)
                    : [0];


            trendChart.updateOptions({

                xaxis: {

                    categories:
                        updatedLabels

                }

            });


            trendChart.updateSeries([

                {

                    name: 'CO₂e',

                    data:
                        updatedTotals

                }

            ]);

        });


    /* =========================================
       Emissions by Source
    ========================================== */

    const hasEmissionData =
        totalEmission > 0;


    const sourceChart =
        new ApexCharts(

            document.querySelector(
                '#emissionSourceChart'
            ),

            {

                chart: {

                    type: 'donut',

                    height: 245,

                    width: 245,

                    sparkline: {
                        enabled: true
                    },

                    fontFamily:
                        'Poppins, sans-serif'

                },


                series:
                    emissionSources,


                labels: [

                    'Transportation',

                    'Electricity',

                    'Food Consumption'

                ],


                colors: [

                    '#16a34a',

                    '#eab308',

                    '#ef4444'

                ],


                legend: {
                    show: false
                },


                dataLabels: {
                    enabled: false
                },


                tooltip: {

                    style: {

                        fontFamily:
                            'Poppins, sans-serif'

                    },

                    y: {

                        formatter:
                            function (value) {

                                return Number(value)
                                    .toLocaleString(
                                        undefined,
                                        {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        }
                                    )
                                    + ' kg CO₂e';

                            }

                    }

                },


                plotOptions: {

                    pie: {

                        donut: {

                            size: '72%',


                            labels: {

                                show: true,


                                value: {

                                    show: true,

                                    fontFamily:
                                        'Poppins, sans-serif',

                                    fontSize: '16px',

                                    fontWeight: 700,

                                    color:
                                        '#111111',

                                    offsetY: 8,


                                    formatter:
                                        function (val) {

                                            return Number(
                                                val
                                            ).toFixed(2);

                                        }

                                },


                                total: {

                                    show: true,

                                    showAlways: true,

                                    label:
                                        'Total Emissions',

                                    color:
                                        '#6b7280',

                                    fontFamily:
                                        'Poppins, sans-serif',

                                    fontSize: '9px',

                                    fontWeight: 500,


                                    formatter:
                                        function () {

                                            return Number(
                                                totalEmission
                                            ).toLocaleString(
                                                undefined,
                                                {
                                                    minimumFractionDigits: 2,
                                                    maximumFractionDigits: 2
                                                }
                                            )
                                            + ' kg CO₂e';

                                        }

                                }

                            }

                        }

                    }

                }

            }

        );


    if (hasEmissionData) {

        document
            .getElementById('emptyDonut')
            .classList.add('hidden');


        document
            .getElementById('emptyDonut')
            .classList.remove('flex');


        document
            .getElementById('emissionSourceChart')
            .classList.remove('hidden');


        sourceChart.render();

    }
    else {

        document
            .getElementById('emissionSourceChart')
            .classList.add('hidden');


        document
            .getElementById('emptyDonut')
            .classList.remove('hidden');


        document
            .getElementById('emptyDonut')
            .classList.add('flex');

    }


    /* =========================================
       Emissions Comparison
    ========================================== */

    const comparisonData =
        JSON.parse(

            document
                .getElementById(
                    'comparison-data'
                )
                .textContent

        );


    const comparisonChart =
        new ApexCharts(

            document.querySelector(
                '#comparisonChart'
            ),

            {

                chart: {

                    type: 'bar',

                    height: 260,

                    toolbar: {
                        show: false
                    },

                    fontFamily:
                        'Poppins, sans-serif'

                },


                series: [

                    {

                        name:
                            'This Month',

                        data:
                            comparisonData.current

                    },


                    {

                        name:
                            'Last Month',

                        data:
                            comparisonData.last

                    }

                ],


                xaxis: {

                    categories: [

                        'Transportation',

                        'Electricity',

                        'Food Consumption'

                    ],


                    labels: {

                        style: {

                            fontFamily:
                                'Poppins, sans-serif',

                            fontSize:
                                '9px'

                        }

                    }

                },


                yaxis: {

                    title: {

                        text:
                            'kg CO₂e',

                        style: {

                            fontFamily:
                                'Poppins, sans-serif',

                            fontSize:
                                '10px',

                            fontWeight:
                                500

                        }

                    },


                    labels: {

                        style: {

                            fontFamily:
                                'Poppins, sans-serif',

                            fontSize:
                                '9px'

                        }

                    }

                },


                colors: [

                    '#4f8b3a',

                    '#9ca3af'

                ],


                plotOptions: {

                    bar: {

                        columnWidth:
                            '45%',

                        borderRadius:
                            4

                    }

                },


                dataLabels: {

                    enabled:
                        false

                },


                grid: {

                    borderColor:
                        '#eeeeee',

                    strokeDashArray:
                        4

                },


                legend: {

                    position:
                        'top',

                    horizontalAlign:
                        'right',

                    fontFamily:
                        'Poppins, sans-serif',

                    fontSize:
                        '9px'

                },


                tooltip: {

                    style: {

                        fontFamily:
                            'Poppins, sans-serif'

                    },

                    y: {

                        formatter:
                            function (value) {

                                return Number(value)
                                    .toLocaleString(
                                        undefined,
                                        {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        }
                                    )
                                    + ' kg CO₂e';

                            }

                    }

                }

            }

        );


    comparisonChart.render();

</script>

@endpush


<!-- =========================================
     EXPORT REPORT
========================================== -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const exportBtn =
            document.getElementById(
                'exportBtn'
            );


        if (!exportBtn) {
            return;
        }


        exportBtn.addEventListener(
            'click',
            function (e) {

                e.preventDefault();


                Swal.fire({

                    title:
                        'Preparing Report...',

                    text:
                        'Please wait while your Excel report is being generated.',

                    icon:
                        'info',

                    allowOutsideClick:
                        false,

                    showConfirmButton:
                        false,


                    didOpen: () => {

                        Swal.showLoading();

                    }

                });


                setTimeout(
                    () => {

                        window.location.href =
                            this.href;


                        setTimeout(
                            () => {

                                Swal.close();

                            },
                            3000
                        );

                    },
                    500
                );

            }
        );

    }
);

</script>


@endsection