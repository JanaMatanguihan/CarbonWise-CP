
@extends('layouts.admin')

@section('page-title', 'Analytics and Report')
@section('page-subtitle', 'Generate reports and gain insights')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

    .analytics-page,
    .analytics-page * {
        font-family: 'Poppins', sans-serif;
        box-sizing: border-box;
    }

    .analytics-page {
        width: 100%;
        min-width: 0;
        color: #111827;
        background: #f4f5f4;
    }

    .analytics-card {
        width: 100%;
        min-width: 0;
        background: #ffffff;
        color: #111827;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
        padding: 24px;
    }

    /* Section headings */

    .analytics-title {
        margin: 0;
        font-size: 18px;
        line-height: 1.5;
        font-weight: 700;
        color: #111827;
    }

    .analytics-description {
        margin-top: 5px;
        font-size: 12px;
        line-height: 1.6;
        font-weight: 400;
        color: #6b7280;
    }

    /* Summary report */

    .analytics-summary-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 24px;
        align-items: center;
    }

    .analytics-summary-item {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .analytics-summary-content {
        min-width: 0;
        flex: 1;
    }

    .analytics-label {
        font-size: 13px;
        line-height: 1.5;
        font-weight: 500;
        color: #6b7280;
        overflow-wrap: anywhere;
    }

    .analytics-value {
        margin: 2px 0 0;
        font-size: clamp(18px, 1.3vw, 22px);
        line-height: 1.4;
        font-weight: 700;
        color: #111827;
        overflow-wrap: anywhere;
    }

    .analytics-unit {
        display: inline-block;
        font-size: 11px;
        line-height: 1.5;
        font-weight: 500;
        color: #4b5563;
        white-space: normal;
    }

    /* Summary icons */

    .analytics-icon {
        width: 44px;
        height: 44px;
        min-width: 44px;
        flex-shrink: 0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .analytics-icon img {
        width: 23px;
        height: 23px;
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

    .analytics-icon img {
        filter: brightness(0) invert(1);
    }

    /* Top emitting sources */

    .analytics-sources {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .analytics-source-row {
        display: grid;
        grid-template-columns: minmax(130px, 170px) minmax(0, 1fr) 65px;
        gap: 16px;
        align-items: center;
        min-width: 0;
    }

    .analytics-source-name {
        font-size: 13px;
        line-height: 1.5;
        font-weight: 500;
        color: #374151;
        overflow-wrap: anywhere;
    }

    .analytics-progress {
        width: 100%;
        height: 10px;
        background: #e5e7eb;
        border-radius: 999px;
        overflow: hidden;
    }

    .analytics-progress-bar {
        height: 100%;
        width: 0;
        background: #2f7d57;
        border-radius: 999px;
        transition: width 0.3s ease;
    }

    .analytics-percentage {
        text-align: right;
        font-size: 12px;
        line-height: 1.5;
        font-weight: 500;
        color: #6b7280;
        white-space: nowrap;
    }

    /* Comparison chart */

    .analytics-chart-wrapper {
        position: relative;
        width: 100%;
        min-width: 0;
        min-height: 320px;
    }

    #comparisonChart {
        width: 100%;
        min-width: 0;
    }

    .analytics-page .apexcharts-canvas,
    .analytics-page .apexcharts-svg {
        max-width: 100%;
    }

    .analytics-page .apexcharts-text,
    .analytics-page .apexcharts-xaxis-label,
    .analytics-page .apexcharts-yaxis-label,
    .analytics-page .apexcharts-legend-text {
        font-family: 'Poppins', sans-serif !important;
    }

    /* =========================================
       DARK MODE
       ========================================= */

    html[data-theme="dark"] .analytics-page {
        color: #F3F4F6;
        background: #0A0F0D;
    }

    html[data-theme="dark"] .analytics-card {
        background: #121A16;
        color: #F3F4F6;
        border-color: #1B3A2B;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.14);
    }

    html[data-theme="dark"] .analytics-title,
    html[data-theme="dark"] .analytics-value {
        color: #F3F4F6;
    }

    html[data-theme="dark"] .analytics-description,
    html[data-theme="dark"] .analytics-label,
    html[data-theme="dark"] .analytics-percentage {
        color: #9CA3AF;
    }

    html[data-theme="dark"] .analytics-source-name {
        color: #D1D5DB;
    }

    html[data-theme="dark"] .analytics-unit {
        color: #D1D5DB;
    }

    html[data-theme="dark"] .analytics-progress {
        background: #26352D;
    }

    html[data-theme="dark"] .analytics-progress-bar {
        background: #52B788;
    }

    /* =========================================
       RESPONSIVE LAYOUT
       ========================================= */

    @media (max-width: 1400px) {
        .analytics-summary-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 24px;
        }
    }

    @media (max-width: 1000px) {
        .analytics-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 22px;
        }

        .analytics-card {
            padding: 22px;
        }

        .analytics-source-row {
            grid-template-columns: minmax(110px, 150px) minmax(0, 1fr) 60px;
            gap: 12px;
        }
    }

    @media (max-width: 640px) {
        .analytics-card {
            padding: 16px;
            border-radius: 9px;
        }

        .analytics-title {
            font-size: 17px;
        }

        .analytics-description {
            font-size: 12px;
        }

        .analytics-summary-grid {
            grid-template-columns: minmax(0, 1fr);
            gap: 18px;
        }

        .analytics-summary-item {
            gap: 14px;
        }

        .analytics-icon {
            width: 42px;
            height: 42px;
            min-width: 42px;
        }

        .analytics-value {
            font-size: 20px;
        }

        .analytics-source-row {
            grid-template-columns: minmax(0, 1fr) 55px;
            gap: 8px 12px;
        }

        .analytics-source-name {
            grid-column: 1;
            grid-row: 1;
        }

        .analytics-percentage {
            grid-column: 2;
            grid-row: 1;
        }

        .analytics-progress {
            grid-column: 1 / -1;
            grid-row: 2;
        }

        .analytics-chart-wrapper {
            min-height: 300px;
        }
    }

    /* Allow cards to fit at browser zoom levels */
    @media (max-width: 400px) {
        .analytics-card {
            padding: 13px;
        }

        .analytics-value {
            font-size: 18px;
        }

        .analytics-unit {
            font-size: 10px;
        }
    }
</style>

<div class="analytics-page min-h-screen p-6 -mx-6 -mt-6 space-y-5">

    {{-- Summary Report --}}
    <section class="analytics-card">

        <h3 class="analytics-title">
            Summary Report
        </h3>

        <p class="analytics-description mb-7">
            Overview of overall emissions and user engagement
        </p>

        <div class="analytics-summary-grid">

            {{-- Total Users --}}
            <div class="analytics-summary-item">
                <div class="analytics-icon analytics-icon-green">
                    <img src="{{ asset('icons/user.png') }}" alt="">
                </div>

                <div class="analytics-summary-content">
                    <p class="analytics-label">Total Users</p>
                    <h2 class="analytics-value">
                        {{ number_format($totalUsers) }}
                    </h2>
                </div>
            </div>

            {{-- Total Emissions --}}
            <div class="analytics-summary-item">
                <div class="analytics-icon analytics-icon-light">
                    <img src="{{ asset('icons/emissions.png') }}" alt="">
                </div>

                <div class="analytics-summary-content">
                    <p class="analytics-label">Total Emissions</p>
                    <h2 class="analytics-value">
                        {{ number_format($totalEmissions, 2) }}
                        <span class="analytics-unit">kg CO₂e</span>
                    </h2>
                </div>
            </div>

            {{-- Average Emission --}}
            <div class="analytics-summary-item">
                <div class="analytics-icon analytics-icon-gray">
                    <img src="{{ asset('icons/analytics.png') }}" alt="">
                </div>

                <div class="analytics-summary-content">
                    <p class="analytics-label">Avg Emission per User</p>
                    <h2 class="analytics-value">
                        {{ number_format($averageEmission, 2) }}
                    </h2>
                </div>
            </div>

            {{-- Active Users --}}
            <div class="analytics-summary-item">
                <div class="analytics-icon analytics-icon-green">
                    <img src="{{ asset('icons/user.png') }}" alt="">
                </div>

                <div class="analytics-summary-content">
                    <p class="analytics-label">Active Users</p>
                    <h2 class="analytics-value">
                        {{ number_format($activeUsers) }}
                    </h2>
                </div>
            </div>

            {{-- Mitigation Actions --}}
            <div class="analytics-summary-item">
                <div class="analytics-icon analytics-icon-dark">
                    <img src="{{ asset('icons/mitigation.png') }}" alt="">
                </div>

                <div class="analytics-summary-content">
                    <p class="analytics-label">Mitigation Actions</p>
                    <h2 class="analytics-value">
                        {{ number_format($mitigationActions) }}
                    </h2>
                </div>
            </div>

        </div>
    </section>

    {{-- Top Emitting Sources --}}
    <section class="analytics-card">

        <h3 class="analytics-title mb-7">
            Top Emitting Sources
        </h3>

        <div class="analytics-sources">

            @foreach($sources as $name => $value)

                @php
                    $percentage = $highestSource > 0
                        ? min(100, max(0, ($value / $highestSource) * 100))
                        : 0;
                @endphp

                <div class="analytics-source-row">

                    <span class="analytics-source-name">
                        {{ $name }}
                    </span>

                    <div class="analytics-progress">
                        <div
                            class="analytics-progress-bar"
                            data-width="{{ $percentage }}"
                        ></div>
                    </div>

                    <span class="analytics-percentage">
                        {{ number_format($percentage, 1) }}%
                    </span>

                </div>

            @endforeach

        </div>
    </section>

    {{-- Emissions Comparison --}}
    <section class="analytics-card">

        <h3 class="analytics-title mb-5">
            Emissions Comparison
        </h3>

        <div class="analytics-chart-wrapper">
            <div id="comparisonChart" style="height: 350px;"></div>
        </div>

    </section>

</div>

<script type="application/json" id="comparisonData">{!! json_encode($comparisonData) !!}</script>

@push('scripts')
<script>
(function () {
    // Set progress bar widths.
    document.querySelectorAll('.analytics-progress-bar').forEach(function (bar) {
        const width = Number(bar.dataset.width || 0);
        bar.style.width = Math.min(100, Math.max(0, width)) + '%';
    });

    const chartElement = document.getElementById('comparisonChart');
    const dataElement = document.getElementById('comparisonData');

    if (!chartElement || !dataElement || typeof ApexCharts === 'undefined') {
        return;
    }

    let comparisonData = {
        current: [],
        last: []
    };

    try {
        comparisonData = JSON.parse(dataElement.textContent || '{}');
    } catch (error) {
        console.error('Unable to read comparison data.', error);
    }

    const currentValues = Array.isArray(comparisonData.current)
        ? comparisonData.current
        : [];

    const lastValues = Array.isArray(comparisonData.last)
        ? comparisonData.last
        : [];

    // Read the current theme when the chart is created.
    function isDarkMode() {
        return document.documentElement.getAttribute('data-theme') === 'dark';
    }

    function getChartOptions() {
        const dark = isDarkMode();
        const textColor = dark ? '#D1D5DB' : '#4B5563';
        const mutedColor = dark ? '#9CA3AF' : '#6B7280';
        const gridColor = dark ? '#26352D' : '#E5E7EB';

        return {
            chart: {
                type: 'bar',
                height: 350,
                width: '100%',
                toolbar: {
                    show: false
                },
                fontFamily: 'Poppins, sans-serif',
                foreColor: textColor,
                background: 'transparent',
                redrawOnParentResize: true,
                redrawOnWindowResize: true,
                animations: {
                    enabled: true,
                    speed: 350
                }
            },

            series: [
                {
                    name: 'This Month',
                    data: currentValues
                },
                {
                    name: 'Last Month',
                    data: lastValues
                }
            ],

            colors: ['#2F7D57', dark ? '#64748B' : '#D1D5DB'],

            plotOptions: {
                bar: {
                    horizontal: false,
                    borderRadius: 4,
                    columnWidth: '45%',
                    dataLabels: {
                        position: 'center'
                    }
                }
            },

            dataLabels: {
                enabled: true,
                style: {
                    fontFamily: 'Poppins, sans-serif',
                    fontSize: '11px',
                    fontWeight: 600,
                    colors: ['#FFFFFF']
                },
                formatter: function (value) {
                    return Number(value).toFixed(2);
                },
                background: {
                    enabled: false
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
                    trim: true,
                    hideOverlappingLabels: false,
                    rotate: -10,
                    rotateAlways: false,
                    style: {
                        fontFamily: 'Poppins, sans-serif',
                        fontSize: '12px',
                        colors: mutedColor
                    }
                },
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                },
                tooltip: {
                    enabled: false
                }
            },

            yaxis: {
                min: 0,
                labels: {
                    style: {
                        fontFamily: 'Poppins, sans-serif',
                        fontSize: '12px',
                        colors: [mutedColor]
                    },
                    formatter: function (value) {
                        return Number(value).toFixed(2);
                    }
                }
            },

            grid: {
                borderColor: gridColor,
                strokeDashArray: 3,
                padding: {
                    left: 8,
                    right: 8
                }
            },

            legend: {
                position: 'top',
                horizontalAlign: 'right',
                fontFamily: 'Poppins, sans-serif',
                fontSize: '12px',
                labels: {
                    colors: textColor
                },
                markers: {
                    width: 10,
                    height: 10,
                    radius: 3
                },
                itemMargin: {
                    horizontal: 8,
                    vertical: 4
                }
            },

            tooltip: {
                theme: dark ? 'dark' : 'light',
                y: {
                    formatter: function (value) {
                        return Number(value).toFixed(2) + ' kg CO₂e';
                    }
                }
            },

            noData: {
                text: 'No comparison data available',
                align: 'center',
                verticalAlign: 'middle',
                style: {
                    color: mutedColor,
                    fontSize: '14px',
                    fontFamily: 'Poppins, sans-serif'
                }
            },

            responsive: [
                {
                    breakpoint: 900,
                    options: {
                        chart: {
                            height: 330
                        },
                        plotOptions: {
                            bar: {
                                columnWidth: '55%'
                            }
                        },
                        dataLabels: {
                            style: {
                                fontSize: '10px'
                            }
                        }
                    }
                },
                {
                    breakpoint: 600,
                    options: {
                        chart: {
                            height: 350
                        },
                        plotOptions: {
                            bar: {
                                columnWidth: '65%'
                            }
                        },
                        dataLabels: {
                            enabled: false
                        },
                        xaxis: {
                            labels: {
                                rotate: -25,
                                style: {
                                    fontSize: '11px'
                                }
                            }
                        },
                        legend: {
                            position: 'bottom',
                            horizontalAlign: 'center',
                            fontSize: '11px'
                        }
                    }
                }
            ]
        };
    }

    const comparisonChart = new ApexCharts(
        chartElement,
        getChartOptions()
    );

    comparisonChart.render();

    // Refresh chart colors when the shared admin theme changes.
    const themeObserver = new MutationObserver(function () {
        comparisonChart.updateOptions(getChartOptions(), false, true);
    });

    themeObserver.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-theme']
    });
})();
</script>
@endpush

@endsection
