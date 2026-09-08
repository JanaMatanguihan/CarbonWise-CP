@extends('layouts.admin')

@section('page-title', 'Forecasting')

@section('page-subtitle', 'Predict future carbon emissions')

@section('content')


<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

    <!-- Predicted Emissions -->
   <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 h-40 flex flex-col justify-between transition-all">
        <p class="text-gray-500 text-sm">Predicted Emissions</p>

        <h2 class="text-2xl font-bold text-green-700 mt-3">
            0 kg CO₂
        </h2>

        <p class="text-sm text-gray-400 mt-2">
            Next 30 Days
        </p>
    </div>

    <!-- Predicted Daily Average -->
    <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 h-40 flex flex-col justify-between transition-all">
        <p class="text-gray-500 text-sm">Predicted Daily Average</p>

        <h2 class="text-2xl font-bold text-blue-600 mt-3">
            0 kg
        </h2>

        <p class="text-sm text-gray-400 mt-2">
            Estimated
        </p>
    </div>

    <!-- Confidence -->
    <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 h-40 flex flex-col justify-between transition-all">
        <p class="text-gray-500 text-sm">Confidence Level</p>

        <h2 class="text-2xl font-bold text-emerald-600 mt-3">
            95%
        </h2>

        <p class="text-sm text-gray-400 mt-2">
            Model Confidence
        </p>
    </div>

    <!-- Model -->
    <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 h-40 flex flex-col justify-between transition-all">
        <p class="text-gray-500 text-sm">Model Used</p>

        <h2 class="text-lg font-bold text-purple-700 mt-3 leading-snug">
            Temporal Fusion Transformer
        </h2>

        <p class="text-sm text-gray-400 mt-2">
            TFT Forecast Model
        </p>
    </div>

</div>

<div class="bg-white rounded-2xl shadow p-6 mt-8">

    <div class="flex justify-between items-center mb-6">

        <div>

            <h2 class="text-xl font-bold">
                Historical Carbon Emissions
            </h2>

            <p class="text-gray-500 text-sm">
                Historical emissions based on recorded carbon data
            </p>

        </div>

    </div>

    <div id="historicalChart" class="w-full h-96"></div>

</div>
@push('scripts')

<script>

</script>

@endpush

@endsection