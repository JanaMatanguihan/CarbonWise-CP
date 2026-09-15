@extends('layouts.admin')

@section('page-title', 'Forecasting')

@section('page-subtitle', 'Predict future carbon emissions')

@section('content')

<div class="space-y-6">

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">

        <!-- Predicted Emissions -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <p class="text-sm text-gray-600">
                Predicted Emissions
            </p>

            <div class="mt-4 flex items-baseline gap-2">
                <h3 class="text-2xl font-bold text-green-700">
                    —
                </h3>

                <span class="text-xs text-gray-500">
                    kg CO₂e
                </span>
            </div>

            <p class="text-xs text-gray-400 mt-3">
                Next 30 Days
            </p>
        </div>


        <!-- Predicted Daily Average -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <p class="text-sm text-gray-600">
                Predicted Daily Average
            </p>

            <div class="mt-4 flex items-baseline gap-2">
                <h3 class="text-2xl font-bold text-blue-600">
                    —
                </h3>

                <span class="text-xs text-gray-500">
                    kg CO₂e
                </span>
            </div>

            <p class="text-xs text-gray-400 mt-3">
                Estimated
            </p>
        </div>


        <!-- Confidence -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <p class="text-sm text-gray-600">
                Confidence Level
            </p>

            <div class="mt-4">
                <h3 class="text-2xl font-bold text-green-700">
                    —
                </h3>
            </div>

            <p class="text-xs text-gray-400 mt-3">
                Based on TFT forecast output
            </p>
        </div>


        <!-- Model Used -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <p class="text-sm text-gray-600">
                Model Used
            </p>

            <h3 class="text-lg font-bold text-gray-900 mt-4 leading-snug">
                Temporal Fusion
                <br>
                Transformer (TFT)
            </h3>

            <p class="text-xs text-gray-400 mt-3">
                TFT Forecast Model
            </p>
        </div>

    </div>


    <!-- Forecasted Emissions Chart -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">

        <div class="mb-5">
            <h2 class="text-lg font-bold text-gray-900">
                Forecasted Emissions
            </h2>

            <p class="text-sm text-gray-500 mt-1">
                Historical carbon emissions and future TFT predictions
            </p>
        </div>

        <div
            id="forecastChart"
            class="w-full"
            style="min-height: 380px;"
        ></div>

    </div>


    <!-- Model Performance -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

        <!-- RMSE -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <p class="text-sm text-gray-600">
                RMSE
            </p>

            <h3 class="text-2xl font-bold text-gray-900 mt-4">
                —
            </h3>

            <p class="text-xs text-gray-400 mt-2">
                Root Mean Square Error
            </p>
        </div>


        <!-- MAE -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <p class="text-sm text-gray-600">
                MAE
            </p>

            <h3 class="text-2xl font-bold text-gray-900 mt-4">
                —
            </h3>

            <p class="text-xs text-gray-400 mt-2">
                Mean Absolute Error
            </p>
        </div>


        <!-- R² -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <p class="text-sm text-gray-600">
                R² (Coefficient of Determination)
            </p>

            <h3 class="text-2xl font-bold text-gray-900 mt-4">
                —
            </h3>

            <p class="text-xs text-gray-400 mt-2">
                Model evaluation metric
            </p>
        </div>

    </div>

</div>


@push('scripts')

<script>

    const historicalLabels = @json($labels);
    const historicalValues = @json($values);

    const forecastOptions = {

        chart: {
            type: 'line',
            height: 380,
            toolbar: {
                show: false
            },
            zoom: {
                enabled: false
            }
        },

        series: [
            {
                name: 'Historical',
                data: historicalValues.map(Number)
            }
        ],

        xaxis: {
            categories: historicalLabels,
            labels: {
                rotate: -45,
                style: {
                    fontSize: '11px'
                }
            }
        },

        yaxis: {
            title: {
                text: 'kg CO₂e'
            }
        },

        stroke: {
            curve: 'smooth',
            width: 2
        },

        markers: {
            size: 0
        },

        grid: {
            borderColor: '#e5e7eb',
            strokeDashArray: 4
        },

        legend: {
            position: 'top',
            horizontalAlign: 'right'
        },

        tooltip: {
            shared: true,
            intersect: false
        },

        dataLabels: {
            enabled: false
        }

    };

    const forecastChart = new ApexCharts(
        document.querySelector('#forecastChart'),
        forecastOptions
    );

    forecastChart.render();

</script>

@endpush

@endsection