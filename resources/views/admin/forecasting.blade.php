@extends('layouts.admin')

@section('page-title', 'Forecasting')

@section('page-subtitle', 'Predict future carbon emissions')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

    .forecast-page {
        --forecast-card: #ffffff;
        --forecast-text: #111827;
        --forecast-secondary: #5b6472;
        --forecast-muted: #6b7280;
        --forecast-border: #e5e7eb;
        --forecast-grid: #e5e7eb;
        --forecast-tooltip: #ffffff;
        font-family: 'Poppins', sans-serif;
        color: var(--forecast-text);
        width: 100%;
        min-width: 0;
    }

    .forecast-page *, .forecast-page *::before, .forecast-page *::after { box-sizing: border-box; }

    .forecast-card {
        min-width: 0;
        background: var(--forecast-card);
        color: var(--forecast-text);
        border: 1px solid var(--forecast-border);
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        transition: background-color .2s ease, border-color .2s ease, color .2s ease;
    }

    .forecast-card-label, .forecast-metric-label {
        font-family: 'Poppins', sans-serif;
        font-size: clamp(12px, .85rem, 14px);
        line-height: 1.5;
        font-weight: 600;
        color: var(--forecast-text);
        overflow-wrap: anywhere;
    }

    .forecast-main-value, .forecast-metric-value {
        font-family: 'Poppins', sans-serif;
        font-size: clamp(22px, 1.6vw, 28px);
        line-height: 1.35;
        font-weight: 700;
        letter-spacing: -.35px;
        color: var(--forecast-text);
        overflow-wrap: anywhere;
    }

    .forecast-unit {
        font-family: 'Poppins', sans-serif;
        font-size: clamp(10px, .75rem, 12px);
        line-height: 1.5;
        font-weight: 500;
        color: var(--forecast-secondary);
    }

    .forecast-small-text {
        font-family: 'Poppins', sans-serif;
        font-size: clamp(11px, .78rem, 13px);
        line-height: 1.55;
        font-weight: 400;
        color: var(--forecast-muted);
        overflow-wrap: anywhere;
    }

    .forecast-confidence {
        font-family: 'Poppins', sans-serif;
        font-size: clamp(22px, 1.6vw, 28px);
        line-height: 1.35;
        font-weight: 700;
        color: #2f7d57;
        overflow-wrap: anywhere;
    }

    .forecast-model {
        font-family: 'Poppins', sans-serif;
        font-size: clamp(14px, 1rem, 16px);
        line-height: 1.5;
        font-weight: 700;
        color: var(--forecast-text);
        overflow-wrap: anywhere;
    }

    .forecast-chart-title { font-size: clamp(16px, 1.1rem, 18px); line-height: 1.5; font-weight: 700; color: var(--forecast-text); }
    .forecast-chart-description { font-size: clamp(12px, .82rem, 14px); line-height: 1.55; font-weight: 400; color: var(--forecast-secondary); }

    #forecastChart { display: block; width: 100%; min-width: 0; height: 320px; min-height: 320px; overflow: hidden; }
    #forecastChart .apexcharts-canvas { width: 100% !important; height: 320px !important; }
    .forecast-chart-legend { display: flex; justify-content: flex-end; align-items: center; flex-wrap: wrap; gap: 1rem; margin: 0 0 .5rem; color: var(--forecast-text); font-size: 12px; line-height: 1.4; }
    .forecast-chart-legend-item { display: inline-flex; align-items: center; gap: .4rem; white-space: nowrap; }
    .forecast-legend-line { display: inline-block; width: 22px; height: 0; border-top: 2px solid #2F7D57; }
    .forecast-legend-line--forecast { border-top-style: dashed; }
    #forecastChart .apexcharts-canvas, #forecastChart svg { max-width: 100%; }
    #forecastChart .apexcharts-tooltip { border: 1px solid var(--forecast-border); }

    /* Match the shared admin layout theme: html[data-theme="dark"]. */
    html[data-theme="dark"] .forecast-page {
        --forecast-card: #121a16;
        --forecast-text: #f3f4f6;
        --forecast-secondary: #c0c8c3;
        --forecast-muted: #a7b0aa;
        --forecast-border: #294236;
        --forecast-grid: #294236;
        --forecast-tooltip: #121a16;
    }

    html[data-theme="dark"] .forecast-card { box-shadow: 0 2px 10px rgba(0, 0, 0, .12); }
    html[data-theme="dark"] .forecast-confidence { color: #75c99a; }

    @media (max-width: 640px) {
        #forecastChart, #forecastChart .apexcharts-canvas { height: 280px !important; min-height: 280px; }
        .forecast-chart-card { padding: 1rem !important; }
        .forecast-page .forecast-card { border-radius: 10px; }
    }

    @media (min-width: 641px) and (max-width: 1024px) {
        #forecastChart, #forecastChart .apexcharts-canvas { height: 300px !important; min-height: 300px; }
    }

    @media (prefers-reduced-motion: reduce) {
        .forecast-card { transition: none; }
    }
</style>

<div class="forecast-page">

    <!-- Summary Cards -->

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">

        <!-- Predicted Emissions -->

        <div class="forecast-card px-6 py-5">

            <p class="forecast-card-label">
                Predicted Emissions
            </p>

            <div class="mt-4 flex items-baseline gap-1.5">

                <h3 class="forecast-main-value">
                    {{ number_format($predictedEmissions, 2) }}
                </h3>

                <span class="forecast-unit">
                    kg CO₂e
                </span>

            </div>

            <p class="forecast-small-text mt-2">
                Next 30 Days
            </p>

        </div>

        <!-- Predicted Daily Average -->

        <div class="forecast-card px-6 py-5">

            <p class="forecast-card-label">
                Predicted Daily Average
            </p>

            <div class="mt-4 flex items-baseline gap-1.5">

                <h3 class="forecast-main-value">
                    {{ $predictedDailyAverage !== null
                        ? number_format($predictedDailyAverage, 2)
                        : '—' }}
                </h3>

                <span class="forecast-unit">
                    kg CO₂e
                </span>

            </div>

            <p class="forecast-small-text mt-2">
                Estimated
            </p>

        </div>

        <!-- Prediction Interval -->

        <div class="forecast-card px-6 py-5">

            <p class="forecast-card-label">
                Prediction Interval
            </p>

            <div class="mt-4">

                <h3 class="forecast-confidence">
                    {{ $confidenceLevel ?? '—' }}
                </h3>

            </div>

            <p class="forecast-small-text mt-2">
                Based on TFT forecast output
            </p>

        </div>

        <!-- Model Used -->

        <div class="forecast-card px-6 py-5">

            <p class="forecast-card-label">
                Model Used
            </p>

            <h3 class="forecast-model mt-4">
                Temporal Fusion<br>
                Transformer (TFT)
            </h3>

            <p class="forecast-small-text mt-2">
                TFT Forecast Model
            </p>

        </div>

    </div>


    <!-- Forecast Chart -->

    <div class="forecast-card forecast-chart-card mt-6 px-7 py-7">

        <div class="mb-5">

            <h2 class="forecast-chart-title">
                Forecasted Emissions
            </h2>

            <p class="forecast-chart-description mt-1">
                Historical carbon emissions and future TFT predictions
            </p>

        </div>

        <div
            id="forecastData"
            data-historical-labels="{{ json_encode($labels->values()) }}"
            data-historical-values="{{ json_encode($values->values()) }}"
            data-forecast-labels="{{ json_encode($forecastLabels->values()) }}"
            data-forecast-values="{{ json_encode($forecastValues->values()) }}"
        ></div>

        <div class="forecast-chart-legend" aria-label="Chart legend">
            <span class="forecast-chart-legend-item"><span class="forecast-legend-line" aria-hidden="true"></span>Historical</span>
            <span class="forecast-chart-legend-item"><span class="forecast-legend-line forecast-legend-line--forecast" aria-hidden="true"></span>Forecast</span>
        </div>

        <div
            id="forecastChart"
            class="w-full"
            role="img"
            aria-label="Historical carbon emissions and future TFT forecast chart"
        ></div>

    </div>


    <!-- Model Performance -->

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-5">

        <!-- RMSE -->

        <div class="forecast-card px-6 py-6">

            <p class="forecast-metric-label">
                RMSE
            </p>

           <h3 class="forecast-metric-value mt-4">
                {{ $rmse !== null ? number_format($rmse, 2) : '—' }}
            </h3>

            <p class="forecast-small-text mt-1">
                Root Mean Square Error
            </p>

        </div>

        <!-- MAE -->

        <div class="forecast-card px-6 py-6">

            <p class="forecast-metric-label">
                MAE
            </p>

           <h3 class="forecast-metric-value mt-4">
                {{ $mae !== null ? number_format($mae, 2) : '—' }}
            </h3>

            <p class="forecast-small-text mt-1">
                Mean Absolute Error
            </p>

        </div>

        <!-- R2 -->

        <div class="forecast-card px-6 py-6">

            <p class="forecast-metric-label">
                R² (Coefficient of Determination)
            </p>

            <h3 class="forecast-metric-value mt-4">
                {{ $r2 !== null ? number_format($r2, 4) : '—' }}
            </h3>

            <p class="forecast-small-text mt-1">
                Model evaluation metric
            </p>

        </div>

    </div>

</div>


@push('scripts')

<script>

    // Get Laravel data

    const forecastDataElement = document.getElementById('forecastData');

    const historicalLabels = JSON.parse(
        forecastDataElement.dataset.historicalLabels
    );

    const historicalValues = JSON.parse(
        forecastDataElement.dataset.historicalValues
    );

    const forecastLabels = JSON.parse(
        forecastDataElement.dataset.forecastLabels
    );

    const forecastValues = JSON.parse(
        forecastDataElement.dataset.forecastValues
    );


    // Convert historical data

    const historicalData = historicalLabels.map(function (date, index) {

        return {
            x: new Date(date).getTime(),
            y: Number(historicalValues[index])
        };

    });


    // Convert TFT forecast data and connect it to the last historical value.

        const forecastData = [];

        // Start the forecast line at the last historical point.
        if (historicalData.length > 0) {
            const lastHistoricalPoint = historicalData[historicalData.length - 1];

            forecastData.push({
                x: lastHistoricalPoint.x,
                y: lastHistoricalPoint.y
            });
        }

        // Add the actual TFT predictions.
        forecastLabels.forEach(function (date, index) {
            forecastData.push({
                x: new Date(date).getTime(),
                y: Number(forecastValues[index])
            });
        });


    // Calculate the Y-axis range from the real data

    const allChartValues = [
        ...historicalValues.map(Number),
        ...forecastValues.map(Number)
    ].filter(function (value) {

        return Number.isFinite(value);

    });


    let chartMin = 0;
    let chartMax = 15000;


    if (allChartValues.length > 0) {

        const dataMin = Math.min(...allChartValues);
        const dataMax = Math.max(...allChartValues);

        const range = Math.max(
            dataMax - dataMin,
            100
        );

        const padding = range * 0.20;

        chartMin = Math.max(
            0,
            Math.floor((dataMin - padding) / 100) * 100
        );

        chartMax =
            Math.ceil((dataMax + padding) / 100) * 100;

    }


    // Get the forecast starting point

    const forecastStart =
        forecastLabels.length > 0
            ? new Date(forecastLabels[0]).getTime()
            : null;


    // Theme values follow the shared admin layout's html[data-theme] attribute.
    const isForecastDarkMode = () => document.documentElement.getAttribute('data-theme') === 'dark';
    const forecastTextColor = () => isForecastDarkMode() ? '#c0c8c3' : '#6b7280';
    const forecastGridColor = () => isForecastDarkMode() ? '#294236' : '#e5e7eb';

    // Configure the chart

    const forecastOptions = {

        theme: { mode: isForecastDarkMode() ? 'dark' : 'light' },

        chart: {

            type: 'area',

            height: 320,
            width: '100%',
            parentHeightOffset: 0,
            redrawOnParentResize: true,
            redrawOnWindowResize: true,

            toolbar: {
                show: false
            },

            zoom: {
                enabled: false
            },

            fontFamily: 'Poppins, sans-serif',
            foreColor: forecastTextColor(),
            background: 'transparent',


            animations: {
                enabled: true
            }

        },


        series: [

            {
                name: 'Historical',
                data: historicalData
            },

            {
                name: 'Forecast',
                data: forecastData
            }

        ],


        colors: [
            '#2F7D57',
            '#2F7D57'
        ],


        stroke: {

            curve: 'smooth',

            width: [
                2.5,
                2.5
            ],

            dashArray: [
                0,
                6
            ]

        },


        fill: {

            type: 'solid',

            opacity: [
                0.12,
                0.07
            ]

        },


        markers: {

            size: 0,

            strokeWidth: 0,

            hover: {

                size: 5

            }

        },


        xaxis: {

            type: 'datetime',

            labels: {

                datetimeUTC: false,

                format: 'MMM d',

                style: {

                    fontFamily: 'Poppins, sans-serif',

                    fontSize: '12px',

                    colors: forecastTextColor()

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

            min: chartMin,

            max: chartMax,

            tickAmount: 4,

            labels: {

                style: {

                    fontFamily: 'Poppins, sans-serif',

                    fontSize: '12px',

                    colors: forecastTextColor()

                },

                formatter: function (value) {

                    return Number(value).toLocaleString(
                        undefined,
                        {
                            maximumFractionDigits: 0
                        }
                    );

                }

            },

            title: {

                text: 'kg CO₂e',

                style: {

                    fontFamily: 'Poppins, sans-serif',

                    fontSize: '12px',

                    fontWeight: 500,

                    color: forecastTextColor()

                }

            }

        },


        grid: {

            borderColor: forecastGridColor(),

            strokeDashArray: 3,

            padding: {

                left: 8,

                right: 10

            }

        },


        legend: {
            show: false
        },

        tooltip: {

            theme: isForecastDarkMode() ? 'dark' : 'light',

            shared: false,

            intersect: false,

            x: {

                format: 'MMM d, yyyy'

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

        },


        dataLabels: {

            enabled: false

        },


        annotations: {

            xaxis: forecastStart !== null
                ? [
                    {

                        x: forecastStart,

                        borderColor: '#d1d5db',

                        strokeDashArray: 3,

                        label: {

                            text: 'Forecast',

                            borderColor: 'transparent',

                            orientation: 'horizontal',

                            style: {

                                background: '#E8F3ED',

                                color: '#2F7D57',

                                fontFamily: 'Poppins, sans-serif',

                                fontSize: '9px',

                                fontWeight: 600

                            }

                        }

                    }

                ]

                : []

        }

    };


    // Create the chart

    const forecastChart = new ApexCharts(
        document.querySelector('#forecastChart'),
        forecastOptions
    );


    // Render chart. A separate HTML legend guarantees solid/dashed samples.
    forecastChart.render();

    // Update ApexCharts colors when the shared theme toggle changes.
    const forecastThemeObserver = new MutationObserver(function () {
        const dark = isForecastDarkMode();
        forecastChart.updateOptions({
            chart: { foreColor: forecastTextColor() },
            theme: { mode: dark ? 'dark' : 'light' },
            xaxis: { labels: { style: { colors: forecastTextColor(), fontSize: '12px', fontFamily: 'Poppins, sans-serif' } } },
            yaxis: { labels: { style: { colors: forecastTextColor(), fontSize: '12px', fontFamily: 'Poppins, sans-serif' } }, title: { style: { color: forecastTextColor() } } },
            grid: { borderColor: forecastGridColor() },
            tooltip: { theme: dark ? 'dark' : 'light' }
        }, false, true);
    });
    forecastThemeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

</script>

@endpush

@endsection