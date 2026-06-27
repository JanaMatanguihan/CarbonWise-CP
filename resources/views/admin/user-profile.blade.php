@extends('layouts.admin')

@section('page-title', 'User Details')
@section('page-subtitle', 'User Management > User Details')

@section('content')

<div class="grid grid-cols-12 gap-6 mt-6">

    <!-- LEFT PROFILE CARD -->
    <div class="col-span-4">

        <div class="bg-white rounded-xl shadow p-8">

            <!-- Profile Picture -->
            <div class="flex justify-center">

                @if($user->profile_photo)
                    <img
                        src="{{ asset('storage/' . $user->profile_photo) }}"
                        class="w-40 h-40 rounded-full object-cover"
                    >
                @else
                    <img
                        src="https://ui-avatars.com/api/?name={{ urlencode($user->full_name) }}&size=200&background=16a34a&color=ffffff"
                        class="w-40 h-40 rounded-full"
                    >
                @endif

            </div>

            <!-- Name -->
            <div class="mt-6 text-center">

                <h2 class="text-2xl font-bold">
                    {{ $user->full_name }}
                </h2>

                <p class="text-gray-500 mt-2">
                    {{ $user->g_suite }}
                </p>

                <span
                    class="inline-block mt-4 px-4 py-2 rounded-full bg-green-100 text-green-700 font-semibold"
                >
                    {{ ucfirst($user->role) }}
                </span>

            </div>

            <!-- Information -->
            <div class="mt-8 border rounded-xl p-5 space-y-4">

                <div class="flex justify-between">
                    <span class="text-gray-500">Department</span>
                    <span>{{ $user->department }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-500">Campus</span>
                    <span>{{ $user->campus }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-500">SR Code</span>
                    <span>{{ $user->sr_code }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-500">Year Level</span>
                    <span>{{ $user->year_level }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-500">Joined</span>
                    <span>{{ \Carbon\Carbon::parse($user->created_at)->format('F d, Y') }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-500">Status</span>

                    <span class="
                        font-semibold
                        {{ $user->status == 'Active'
                            ? 'text-green-600'
                            : 'text-red-600' }}
                    ">
                        {{ $user->status }}
                    </span>
                </div>

            </div>

            <!-- Edit Button -->
            <div class="mt-8">

                <a
                href="{{ route('admin.users.edit',$user->g_suite) }}"
                class="block w-full text-center border border-green-600 text-green-600 py-3 rounded-lg font-semibold hover:bg-green-50"
            >
                Edit User
            </a>

            </div>

        </div>

    </div>

    <!-- RIGHT SIDE -->
    <div class="col-span-8">

        <div class="bg-white rounded-xl shadow h-full">

            <!-- Tabs -->
            <div class="border-b">

                <div class="flex gap-10 px-8 py-5">

                    <button class="border-b-4 border-green-600 pb-3 font-semibold text-green-700">
                        Overview
                    </button>

                    <button class="pb-3">
                        Activity Logs
                    </button>

                    <button class="pb-3">
                        Emissions
                    </button>

                    <button class="pb-3">
                        Reports
                    </button>

                    <button class="pb-3">
                        Badges
                    </button>

                </div>

            </div>

           <div class="grid grid-cols-4 gap-6 px-8 pt-8 items-stretch">
                    <!-- Total Emissions -->
                    <div class="bg-white rounded-xl border shadow-sm h-40 flex items-center px-6">
                   <div class="flex items-center gap-4">

                            <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center">

                            <svg xmlns="http://www.w3.org/2000/svg"
                            class="w-7 h-7 text-green-600"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2">

                                <path stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M7 4h10L9 12l8 8H7"/>

                                </svg>

                            </div>

                            <div>

                            <p class="text-sm text-gray-500">
                            Total Emissions
                            </p>

                            <h2 class="text-4xl font-bold text-gray-900">
                            {{ number_format($totalEmissions,2) }}
                            </h2>

                            <p class="text-xs text-gray-500">
                            kg CO₂e
                            </p>

                        </div>

                    </div>
            </div>
                <!-- This Month -->
                <div class="bg-white rounded-xl border shadow-sm h-40 flex items-center px-6">

                    <div class="flex items-center gap-4">

                        <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-7 h-7 text-blue-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">

                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/>

                            </svg>

                        </div>

                        <div>

                            <p class="text-sm text-gray-500">
                                This Month
                            </p>

                            <h2 class="text-4xl font-bold text-gray-900">
                                {{ number_format($thisMonthEmission,2) }}
                            </h2>

                            <p class="text-xs text-gray-500">
                                kg CO₂e
                            </p>

                        </div>

                    </div>

                </div>

                    <!-- Average per Day -->
                    <div class="bg-white rounded-xl border shadow-sm h-40 flex items-center px-6">

                        <div class="flex items-center gap-4">

                            <div class="w-14 h-14 rounded-full bg-pink-100 flex items-center justify-center">

                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="w-7 h-7 text-pink-600"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2">

                                    <path stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5A4.5 4.5 0 016.5 4C8.24 4 9.91 4.81 11 6.09 12.09 4.81 13.76 4 15.5 4A4.5 4.5 0 0120 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>

                                </svg>

                            </div>

                            <div>

                                <p class="text-sm text-gray-500">
                                    Average per Day
                                </p>

                                <h2 class="text-4xl font-bold text-gray-900">
                                    {{ number_format($averagePerDay,2) }}
                                </h2>

                                <p class="text-xs text-gray-500">
                                    kg CO₂e
                                </p>

                            </div>

                        </div>

                    </div>
                    <!-- Mitigation Actions -->
                    <div class="bg-white rounded-xl border shadow-sm h-40 flex items-center px-6">

                        <div class="flex items-center gap-4">

                            <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center">

                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="w-7 h-7 text-green-600"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2">

                                    <path stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 3l1.9 3.86L18 7.5l-3 2.93.71 4.14L12 12.75l-3.71 1.82.71-4.14L6 7.5l4.1-.64L12 3z"/>

                                </svg>

                            </div>

                            <div>

                                <p class="text-sm text-gray-500">
                                    Mitigation Actions
                                </p>

                                <h2 class="text-4xl font-bold text-gray-900">
                                    {{ $mitigationActions }}
                                </h2>

                                <p class="text-xs text-gray-500">
                                    Completed
                                </p>

                            </div>

                        </div>

                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6 px-8 py-8">

                <!-- Emissions Breakdown -->
                <div class="bg-white rounded-xl border shadow-sm p-6 h-[420px] flex flex-col">

                    <h3 class="text-lg font-semibold">
                        Emissions Breakdown
                    </h3>

                    <div
                        id="emissionBreakdownChart"
                        class="flex-1 mt-4">
                    </div>

                </div>
                <!-- Emissions Trend -->
                <div class="bg-white rounded-xl border shadow-sm p-6 h-[420px] flex flex-col">
                    <h3 class="text-lg font-semibold mb-5">
                        Emissions Trend
                    </h3>

                    <div class="flex-1 flex items-center justify-center">

                    <div
                        id="emissionTrendChart"
                        class="w-full max-w-[300px]">
                    </div>

                </div>

                </div>

            </div>
        </div>

    </div>

</div>

        <script id="emission-history-data" type="application/json">
            {!! json_encode($emissionHistory) !!}
        </script>

        <script id="emission-sources-data" type="application/json">
            {!! json_encode([$transportation, $electricity, $food, $waste]) !!}
        </script>

            @push('scripts')
            <script>

            const emissionHistory = JSON.parse(document.getElementById('emission-history-data').textContent);

            const dates = emissionHistory.map(item => item.date);

            const emissions = emissionHistory.map(item => item.value);

            const breakdownChart = new ApexCharts(
            document.querySelector("#emissionBreakdownChart"),
            {
                chart: {
                    type: 'area',
                    height: 300,
                    toolbar: {
                        show: false
                    },
                    zoom: {
                        enabled: false
                    }
                },

                series: [{
                    name: 'CO₂e',
                    data: emissions
                }],

                xaxis: {
                    categories: dates,
                    labels: {
                        rotate: 0,
                        style: {
                            fontSize: '12px'
                        }
                    }
                },

                yaxis: {
                    min: 0,
                    forceNiceScale: true,
                    decimalsInFloat: 0,
                    title: {
                        text: 'kg CO₂e'
                    }
                },

                stroke: {
                    curve: 'smooth',
                    width: 3
                },

                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 0.4,
                        opacityFrom: 0.35,
                        opacityTo: 0.05
                    }
                },

                markers: {
                    size: 4
                },

                colors: ['#1b7a3d'],

                grid: {
                    borderColor: '#e5e7eb'
                },

                dataLabels: {
                    enabled: false
                }
            }
        );

        breakdownChart.render();

            const emissionSources = JSON.parse(
            document.getElementById('emission-sources-data').textContent
        );

        const totalEmission =
            emissionSources.reduce((a,b)=>a+b,0);

            const trendChart = new ApexCharts(
                document.querySelector("#emissionTrendChart"),
                {
                    chart: {
                        type: 'donut',
                        height: '260'
                    },

                    series: totalEmission > 0
                    ? emissionSources
                    : [100],

                    labels:
                    totalEmission > 0
                    ?
                    [
                        'Transportation',
                        'Electricity',
                        'Food',
                        'Waste'
                    ]
                    :
                    [
                        'No Data'
                    ],

                    plotOptions:{
                    pie:{
                        donut:{
                        size:'72%',

                            labels:{
                            show:true,

                            total:{
                            show:true,

                            label:'Active Users',

                            formatter:function(){

                                return totalEmission > 0
                                    ? totalEmission.toFixed(2)
                                    : '0%';

                                        }

                                    }

                                }

                            }

                        }

                    },
                    
                    colors:
                    totalEmission > 0
                    ?
                    [
                        '#166534',
                        '#84cc16',
                        '#facc15',
                        '#fb923c'
                    ]
                    :
                    [
                        '#e5e7eb'
],

                    legend:{
                    position:'right',
                    show: totalEmission > 0
                },
                    dataLabels: {
                        enabled: true
                    }
                }
            );

            trendChart.render();

            </script>
            @endpush

@endsection