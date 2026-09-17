@extends('layouts.admin')

@section('page-title', 'Forecasting')

@section('page-subtitle', 'Predict future carbon emissions')

@section('content')

<style>

    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

    .forecast-page {
        font-family: 'Poppins', sans-serif;
        color: #111111;
    }

    .forecast-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.12);
    }

    .forecast-card-label {
        font-family: 'Poppins', sans-serif;
        font-size: 11px;
        line-height: 16px;
        font-weight: 500;
        color: #111111;
    }

    .forecast-main-value {
        font-family: 'Poppins', sans-serif;
        font-size: 23px;
        line-height: 32px;
        font-weight: 700;
        letter-spacing: -0.5px;
        color: #111111;
    }

    .forecast-unit {
        font-family: 'Poppins', sans-serif;
        font-size: 8px;
        line-height: 12px;
        font-weight: 400;
        color: #333333;
    }

    .forecast-small-text {
        font-family: 'Poppins', sans-serif;
        font-size: 8px;
        line-height: 13px;
        font-weight: 400;
        color: #9ca3af;
    }

    .forecast-confidence {
        font-family: 'Poppins', sans-serif;
        font-size: 23px;
        line-height: 32px;
        font-weight: 700;
        color: #2f7d57;
    }

    .forecast-model {
        font-family: 'Poppins', sans-serif;
        font-size: 15px;
        line-height: 21px;
        font-weight: 700;
        color: #111111;
    }

    .forecast-chart-title {
        font-family: 'Poppins', sans-serif;
        font-size: 15px;
        line-height: 22px;
        font-weight: 700;
        color: #111111;
    }

    .forecast-chart-description {
        font-family: 'Poppins', sans-serif;
        font-size: 9px;
        line-height: 14px;
        font-weight: 400;
        color: #6b7280;
    }

    .forecast-metric-label {
        font-family: 'Poppins', sans-serif;
        font-size: 11px;
        line-height: 16px;
        font-weight: 500;
        color: #111111;
    }

    .forecast-metric-value {
        font-family: 'Poppins', sans-serif;
        font-size: 23px;
        line-height: 32px;
        font-weight: 700;
        color: #111111;
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

    <div class="forecast-card mt-6 px-7 py-7">

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

        <div
            id="forecastChart"
            class="w-full"
            style="height: 330px;"
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


    // Convert TFT forecast data

    const forecastData = forecastLabels.map(function (date, index) {

        return {
            x: new Date(date).getTime(),
            y: Number(forecastValues[index])
        };

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


    // Configure the chart

    const forecastOptions = {

        chart: {

            type: 'area',

            height: 330,

            toolbar: {
                show: false
            },

            zoom: {
                enabled: false
            },

            fontFamily: 'Poppins, sans-serif',

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

                    fontSize: '9px',

                    colors: '#9ca3af'

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

                    fontSize: '9px',

                    colors: '#9ca3af'

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

                    fontSize: '9px',

                    fontWeight: 500,

                    color: '#6b7280'

                }

            }

        },


        grid: {

            borderColor: '#e5e7eb',

            strokeDashArray: 3,

            padding: {

                left: 8,

                right: 10

            }

        },


        legend: {

            position: 'top',

            horizontalAlign: 'right',

            fontFamily: 'Poppins, sans-serif',

            fontSize: '9px',

            labels: {

                colors: '#111111'

            },

            markers: {

                width: 0,

                height: 0

            },

            itemMargin: {

                horizontal: 8

            },

            customLegendItems: [

                'Historical',
                'Forecast'
            ],

            onItemClick: {

                toggleDataSeries: true

            },

            onItemHover: {

                highlightDataSeries: false

            }

        },


        tooltip: {

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


    // Render the chart

    forecastChart.render().then(function () {

        const legendItems =
            document.querySelectorAll(
                '#forecastChart .apexcharts-legend-series'
            );

        legendItems.forEach(function (item, index) {

            const marker = document.createElement('span');

            marker.style.display = 'inline-block';
            marker.style.width = '18px';
            marker.style.height = '2px';
            marker.style.marginRight = '6px';
            marker.style.verticalAlign = 'middle';
            marker.style.backgroundColor = '#2F7D57';

            if (index === 1) {

                marker.style.background =
                    'repeating-linear-gradient(to right, #2F7D57 0px, #2F7D57 5px, transparent 5px, transparent 8px)';

            }

            const text =
                item.querySelector('.apexcharts-legend-text');

            if (text) {

                item.insertBefore(marker, text);

            }

        });

    });

</script>

@endpush

@endsection