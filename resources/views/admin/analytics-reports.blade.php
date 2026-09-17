@extends('layouts.admin')

@section('page-title', 'Analytics and Report')
@section('page-subtitle', 'Generate reports and gain insights')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

    .analytics-page {
        font-family: 'Poppins', sans-serif;
        color: #111111;
    }

    .analytics-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.10);
    }

    /* Section titles */

    .analytics-title {
        font-family: 'Poppins', sans-serif;
        font-size: 16px;
        line-height: 23px;
        font-weight: 700;
        color: #111111;
    }

    /* Section descriptions */

    .analytics-description {
        font-family: 'Poppins', sans-serif;
        font-size: 10px;
        line-height: 15px;
        font-weight: 400;
        color: #9ca3af;
    }

    /* Summary card labels */

    .analytics-label {
        font-family: 'Poppins', sans-serif;
        font-size: 11px;
        line-height: 16px;
        font-weight: 400;
        color: #6b7280;
    }

    /* Summary card values */

    .analytics-value {
        font-family: 'Poppins', sans-serif;
        font-size: 20px;
        line-height: 28px;
        font-weight: 700;
        color: #111111;
    }

    /* Units */

    .analytics-unit {
        font-family: 'Poppins', sans-serif;
        font-size: 9px;
        line-height: 13px;
        font-weight: 500;
        color: #333333;
    }

    /* Emission source names */

    .analytics-source-name {
        font-family: 'Poppins', sans-serif;
        font-size: 11px;
        line-height: 16px;
        font-weight: 400;
        color: #111111;
    }

    /* Percentages */

    .analytics-percentage {
        font-family: 'Poppins', sans-serif;
        font-size: 9px;
        line-height: 13px;
        font-weight: 400;
        color: #6b7280;
    }

    /* Icons */

    .analytics-icon {
        width: 48px;
        height: 48px;
        min-width: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .analytics-icon img {
        width: 24px;
        height: 24px;
        object-fit: contain;
    }

    .analytics-icon-green {
        background: #3f8f68;
    }

    .analytics-icon-gray {
        background: #4d8f70;
    }

    .analytics-icon-light {
        background: #478d6b;
    }

    .analytics-icon-dark {
        background: #2f7d57;
    }

    .analytics-icon-green img,
    .analytics-icon-gray img,
    .analytics-icon-light img,
    .analytics-icon-dark img {
        filter: brightness(0) invert(1);
    }

    /* Progress bars */

    .analytics-progress {
        height: 9px;
        background: #e5e7eb;
        border-radius: 999px;
        overflow: hidden;
    }

    .analytics-progress-bar {
        height: 100%;
        background: #2f7d57;
        border-radius: 999px;
        transition: width 0.3s ease;
    }

    /* Chart */

    #comparisonChart {
        width: 100%;
    }

    .apexcharts-text,
    .apexcharts-xaxis-label,
    .apexcharts-yaxis-label,
    .apexcharts-legend-text {
        font-family: 'Poppins', sans-serif !important;
    }
</style>

<div class="analytics-page bg-[#f4f5f4] min-h-screen p-6 -mx-6 -mt-6 space-y-5">

    {{-- Summary Report --}}

    <div class="analytics-card p-6">

        <h3 class="analytics-title">
            Summary Report
        </h3>

        <p class="analytics-description mt-1 mb-7">
            Overview of overall emissions and user engagement
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-6 items-center">

            {{-- Total Users --}}

            <div class="flex items-center gap-3">

                <div class="analytics-icon analytics-icon-green">

                    <img
                        src="{{ asset('icons/user.png') }}"
                        alt="Total Users"
                    >

                </div>

                <div>

                    <p class="analytics-label">
                        Total Users
                    </p>

                    <h2 class="analytics-value">
                        {{ number_format($totalUsers) }}
                    </h2>

                </div>

            </div>


            {{-- Total Emissions --}}

            <div class="flex items-center gap-3">

                <div class="analytics-icon analytics-icon-light">

                    <img
                        src="{{ asset('icons/emissions.png') }}"
                        alt="Total Emissions"
                    >

                </div>

                <div>

                    <p class="analytics-label">
                        Total Emissions
                    </p>

                    <h2 class="analytics-value">

                        {{ number_format($totalEmissions, 2) }}

                        <span class="analytics-unit">
                            kg CO₂e
                        </span>

                    </h2>

                </div>

            </div>


            {{-- Average Emission --}}

            <div class="flex items-center gap-3">

                <div class="analytics-icon analytics-icon-gray">

                    <img
                        src="{{ asset('icons/analytics.png') }}"
                        alt="Average Emission"
                    >

                </div>

                <div>

                    <p class="analytics-label">
                        Avg Emission per User
                    </p>

                    <h2 class="analytics-value">
                        {{ number_format($averageEmission, 2) }}
                    </h2>

                </div>

            </div>


            {{-- Active Users --}}

            <div class="flex items-center gap-3">

                <div class="analytics-icon analytics-icon-green">

                    <img
                        src="{{ asset('icons/user.png') }}"
                        alt="Active Users"
                    >

                </div>

                <div>

                    <p class="analytics-label">
                        Active Users
                    </p>

                    <h2 class="analytics-value">
                        {{ number_format($activeUsers) }}
                    </h2>

                </div>

            </div>


            {{-- Mitigation Actions --}}

            <div class="flex items-center gap-3">

                <div class="analytics-icon analytics-icon-dark">

                    <img
                        src="{{ asset('icons/mitigation.png') }}"
                        alt="Mitigation Actions"
                    >

                </div>

                <div>

                    <p class="analytics-label">
                        Mitigation Actions
                    </p>

                    <h2 class="analytics-value">
                        {{ number_format($mitigationActions) }}
                    </h2>

                </div>

            </div>

        </div>

    </div>


    {{-- Top Emitting Sources --}}

    <div class="analytics-card p-6">

        <h3 class="analytics-title mb-7">
            Top Emitting Sources
        </h3>

        <div class="space-y-5">

            @foreach($sources as $name => $value)

                @php
                    $percentage = $highestSource > 0
                        ? ($value / $highestSource) * 100
                        : 0;
                @endphp

                <div class="grid grid-cols-[170px_1fr_55px] gap-4 items-center">

                    <span class="analytics-source-name">
                        {{ $name }}
                    </span>

                    <div class="analytics-progress">

                        <div
                            class="analytics-progress-bar"
                            data-width="{{ $percentage }}"
                        ></div>

                    </div>

                    <span class="analytics-percentage text-right">
                        {{ number_format($percentage, 1) }}%
                    </span>

                </div>

            @endforeach

        </div>

    </div>


    {{-- Emissions Comparison --}}

    <div class="analytics-card p-6">

        <h3 class="analytics-title mb-5">
            Emissions Comparison
        </h3>

        <div
            id="comparisonChart"
            style="height: 350px;"
        ></div>

    </div>

</div>


<script
    type="application/json"
    id="comparisonData"
>{!! json_encode($comparisonData) !!}</script>


@push('scripts')

<script>

    // Set progress bar widths

    document
        .querySelectorAll('.analytics-progress-bar')
        .forEach(function (bar) {

            bar.style.width = bar.dataset.width + '%';

        });


    // Get comparison data

    const comparisonDataElement =
        document.getElementById('comparisonData');


    let comparisonData = {
        current: [],
        last: []
    };


    if (comparisonDataElement) {

        try {

            comparisonData =
                JSON.parse(
                    comparisonDataElement.textContent
                );

        } catch (error) {

            console.error(
                'Unable to read comparison data.',
                error
            );

        }

    }


    // Create emissions comparison chart

    const comparisonChartElement =
        document.querySelector('#comparisonChart');


    if (comparisonChartElement) {

        const comparisonChart =
            new ApexCharts(

                comparisonChartElement,

                {

                    chart: {

                        type: 'bar',

                        height: 350,

                        toolbar: {
                            show: false
                        },

                        fontFamily:
                            'Poppins, sans-serif'

                    },


                    series: [

                        {

                            name: 'This Month',

                            data:
                                comparisonData.current || []

                        },

                        {

                            name: 'Last Month',

                            data:
                                comparisonData.last || []

                        }

                    ],


                    // Keep the original chart colors

                    colors: [
                        '#2F7D57',
                        '#D1D5DB'
                    ],


                    plotOptions: {

                        bar: {

                            horizontal: false,

                            borderRadius: 4,

                            columnWidth: '45%'

                        }

                    },


                    dataLabels: {

                        enabled: true,

                        style: {

                            fontFamily:
                                'Poppins, sans-serif',

                            fontSize: '10px',

                            fontWeight: 600

                        },

                        formatter: function (value) {

                            return Number(value).toFixed(2);

                        }

                    },


                    xaxis: {

                        categories: [

                            'Transportation',

                            'Electricity',

                            'Food Consumption',

                            'Others'

                        ],

                        labels: {

                            style: {

                                fontFamily:
                                    'Poppins, sans-serif',

                                fontSize: '10px',

                                colors: '#6b7280'

                            }

                        },

                        axisBorder: {

                            show: false

                        },

                        axisTicks: {

                            show: false

                        }

                    },


                    yaxis: {

                        labels: {

                            style: {

                                fontFamily:
                                    'Poppins, sans-serif',

                                fontSize: '10px',

                                colors: '#9ca3af'

                            },

                            formatter: function (value) {

                                return Number(value).toFixed(2);

                            }

                        }

                    },


                    grid: {

                        borderColor: '#e5e7eb',

                        strokeDashArray: 3

                    },


                    legend: {

                        position: 'top',

                        horizontalAlign: 'right',

                        fontFamily:
                            'Poppins, sans-serif',

                        fontSize: '10px',

                        labels: {

                            colors: '#111111'

                        },

                        markers: {

                            width: 8,

                            height: 8,

                            radius: 8

                        }

                    },


                    tooltip: {

                        y: {

                            formatter: function (value) {

                                return Number(value).toFixed(2);

                            }

                        }

                    },


                    responsive: [

                        {

                            breakpoint: 768,

                            options: {

                                plotOptions: {

                                    bar: {

                                        columnWidth: '55%'

                                    }

                                }

                            }

                        }

                    ]

                }

            );


        comparisonChart.render();

    }

</script>

@endpush

@endsection