@extends('layouts.admin')

@section('page-title', 'Emission Overview')
@section('page-subtitle', 'Monitor and analyze carbon emissions')

@section('content')
<!-- The negative margins counteract parent container padding to make the layout perfectly flush to all edges -->
<div class="bg-[#f1f1ee] min-h-screen p-6 space-y-6 -mx-6 -mt-6 pb-12 w-[calc(100%_+_3rem)]">

   <!-- Filters Header -->
<form method="GET"
      action="{{ route('admin.emissions') }}"
      class="flex justify-end items-center gap-4">


    <!-- Date Selector -->
    <input
        type="month"
        name="month"
        value="{{ request('month') }}"
        onchange="this.form.submit()"
        class="border border-gray-300 rounded-lg px-4 py-2 bg-white text-gray-700 shadow-sm text-sm"
    >


    <!-- Department Selector -->
    <div class="flex items-center gap-2">

        <span class="text-sm text-gray-500 whitespace-nowrap">
            Filter by:
        </span>


        <select
            name="department"
            onchange="this.form.submit()"
            class="border border-gray-300 rounded-lg px-4 py-2 bg-white text-gray-700 shadow-sm text-sm min-w-[160px]"
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
    <a href="{{ route('admin.emissions.export', request()->query()) }}"
       class="bg-[#166534] hover:bg-green-800 text-white px-5 py-2 rounded-lg text-sm font-medium shadow-sm">

        Export Report

    </a>


</form>

    <!-- Summary Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

        <!-- Total Emissions -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center gap-4 min-h-[110px]">
            <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19 18a4 4 0 000-8 5 5 0 00-9.58-1.67A4.5 4.5 0 005 17h14z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400">Total Emissions</p>
                <div class="flex items-baseline gap-1 mt-0.5">
                    <h2 class="text-xl font-bold text-gray-800 tracking-tight">{{ number_format($totalEmissions, 2) }}</h2>
                    <span class="text-[10px] text-gray-400 font-medium">kg CO₂e</span>
                </div>
            </div>
        </div>

        <!-- Transportation -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center gap-4 min-h-[110px]">
            <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 16v2h2v-2h10v2h2v-2M5 16H3v-4l2-5h14l2 5v4h-2M7 16a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400">Transportation</p>
                <div class="flex items-baseline gap-1 mt-0.5">
                    <h2 class="text-xl font-bold text-gray-800 tracking-tight">{{ number_format($transportation, 2) }}</h2>
                    <span class="text-[10px] text-gray-400 font-medium">kg CO₂e</span>
                </div>
                <p class="text-[11px] text-gray-400 mt-0.5">({{ number_format($transportationPercentage, 1) }}%)</p>
            </div>
        </div>

        <!-- Electricity -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center gap-4 min-h-[110px]">
            <div class="w-12 h-12 rounded-full bg-yellow-50 flex items-center justify-center flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 18h6M10 22h4M12 2a6 6 0 00-3.7 10.7c.5.4.7.9.7 1.5V15h6v-.8c0-.6.2-1.1.7-1.5A6 6 0 0012 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400">Electricity</p>
                <div class="flex items-baseline gap-1 mt-0.5">
                    <h2 class="text-xl font-bold text-gray-800 tracking-tight">{{ number_format($electricity, 2) }}</h2>
                    <span class="text-[10px] text-gray-400 font-medium">kg CO₂e</span>
                </div>
                <p class="text-[11px] text-gray-400 mt-0.5">({{ number_format($electricityPercentage, 1) }}%)</p>
            </div>
        </div>

        <!-- Food -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center gap-4 min-h-[110px]">
            <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 15h16a1 1 0 011 1c0 2.5-2.686 4.5-6 4.5H9c-3.314 0-6-2-6-4.5a1 1 0 011-1z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 15c0-3 3-5 6-5s6 2 6 5" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 8c0-1.5 1-3 3-3s3 1.5 3 3" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400">Food Consumption</p>
                <div class="flex items-baseline gap-1 mt-0.5">
                    <h2 class="text-xl font-bold text-gray-800 tracking-tight">{{ number_format($food, 2) }}</h2>
                    <span class="text-[10px] text-gray-400 font-medium">kg CO₂e</span>
                </div>
                <p class="text-[11px] text-gray-400 mt-0.5">({{ number_format($foodPercentage, 1) }}%)</p>
            </div>
        </div>

    </div>

    <!-- Row 1: Over Time & By Source -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Emissions Over Time -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 h-[380px] flex flex-col justify-between">
            <div class="flex justify-between items-center">
                <h3 class="font-bold text-gray-800 text-base">Emissions Over Time</h3>
                <select id="trendFilter" class="border border-gray-300 rounded-lg px-3 py-1 text-xs bg-white shadow-sm min-w-[100px]">
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly" selected>Monthly</option>
                </select>
            </div>
            <div class="flex-1 flex items-center justify-center">
                <div id="emissionTrendChart" class="w-full"></div>
            </div>
        </div>

        <!-- Emissions by Source -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 h-[380px] flex flex-col">
            <h3 class="text-base font-bold text-gray-800 mb-4">Emissions by Source</h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-[45%_55%] gap-4 items-center my-auto w-full">
                <div class="flex justify-center relative">
                    <div id="emissionSourceWrapper" class="w-full max-w-[170px]">
                        <div id="emissionSourceChart"></div>
                        <div id="emptyDonut" class="hidden flex items-center justify-center h-44">
                            <div class="w-32 h-32 rounded-full border-[12px] border-gray-200 flex flex-col items-center justify-center">
                                <span class="text-2xl font-bold text-gray-400">0</span>
                                <span class="text-[10px] text-gray-400">kg CO₂e</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-3 text-xs pr-1">
                    <!-- Transportation -->
                    <div class="flex items-center justify-between text-gray-700">
                        <div class="flex items-center gap-2 min-w-[110px]">
                            <span class="w-2.5 h-2.5 rounded-full bg-green-600 flex-shrink-0"></span>
                            <span class="truncate font-medium text-[11px]">Transportation</span>
                        </div>
                        <span class="text-[11px] font-semibold text-gray-600 w-10 text-right">{{ number_format($transportationPercentage, 1) }}%</span>
                        <span class="text-[11px] text-gray-400 w-24 text-right">{{ number_format($transportation, 2) }} kg CO₂e</span>
                    </div>

                    <!-- Electricity -->
                    <div class="flex items-center justify-between text-gray-700">
                        <div class="flex items-center gap-2 min-w-[110px]">
                            <span class="w-2.5 h-2.5 rounded-full bg-yellow-500 flex-shrink-0"></span>
                            <span class="truncate font-medium text-[11px]">Electricity</span>
                        </div>
                        <span class="text-[11px] font-semibold text-gray-600 w-10 text-right">{{ number_format($electricityPercentage, 1) }}%</span>
                        <span class="text-[11px] text-gray-400 w-24 text-right">{{ number_format($electricity, 2) }} kg CO₂e</span>
                    </div>

                    <!-- Food Consumption -->
                    <div class="flex items-center justify-between text-gray-700">
                        <div class="flex items-center gap-2 min-w-[110px]">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-500 flex-shrink-0"></span>
                            <span class="truncate font-medium text-[11px]">Food Consumption</span>
                        </div>
                        <span class="text-[11px] font-semibold text-gray-600 w-10 text-right">{{ number_format($foodPercentage, 1) }}%</span>
                        <span class="text-[11px] text-gray-400 w-24 text-right">{{ number_format($food, 2) }} kg CO₂e</span>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <!-- Row 2: Department & Comparison -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Emissions by Department -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 h-[380px] flex flex-col">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-base font-bold text-gray-800">Emissions by Department / College</h3>
            </div>

           @php
                $overallTotal = $departmentEmissions->sum('total');
            @endphp
            <div class="space-y-4 overflow-y-auto flex-1 pr-1">
                @foreach($departmentEmissions as $department)
                <div>
                    <div class="flex justify-between text-xs mb-1 text-gray-700">
                        <span class="font-medium text-gray-600">{{ $department->department }}</span>
                                <span class="text-gray-500 font-semibold">
                                    {{ number_format($department->total,2) }} kg CO₂e
                                </span>
                    </div>
                 @php
                        $width = $overallTotal > 0
                            ? round(($department->total / $overallTotal) * 100, 1)
                            : 0;
                    @endphp

                    <div class="w-full bg-gray-100 rounded-full overflow-hidden h-2.5">
                       <div
                            class="bg-[#166534] h-full rounded-full transition-all duration-500"
                            data-department-width="{{ $width }}">
                        </div>
                    </div>

                    <p class="text-right text-xs text-gray-500 mt-1">
                        {{ $width }}%
                    </p>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Emissions Comparison -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 h-[380px] flex flex-col justify-between">
            <div class="flex justify-between items-center">
                <h3 class="text-base font-bold text-gray-800">Emissions Comparison</h3>
             <select
                    class="border border-gray-300 rounded-lg px-3 py-2
                        text-xs bg-white text-gray-600 shadow-sm
                        min-w-[170px]"
                >

                    <option>
                        This Month vs Last Month
                    </option>

                </select>
            </div>
            <div class="flex-1 flex items-center justify-center">
                <div id="comparisonChart" class="w-full"></div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script id="daily-data" type="application/json">{!! $dailyTrend->toJson() !!}</script>
<script id="weekly-data" type="application/json">{!! $weeklyTrend->toJson() !!}</script>
<script id="monthly-data" type="application/json">{!! $monthlyTrend->toJson() !!}</script>
<script id="emission-source-data" type="application/json">
{!! json_encode([
    $transportation,
    $electricity,
    $food
]) !!}
</script>
<script id="total-emission" type="application/json">{!! json_encode($totalEmissions) !!}</script>

<!-- Emissions Comparison Data -->
<script id="comparison-data" type="application/json">
    {!! json_encode($comparisonData) !!}
</script>

<script>
    document.querySelectorAll('[data-department-width]').forEach(function (bar) {
        const width = bar.getAttribute('data-department-width');
        if (width !== null) {
            bar.style.width = width + '%';
        }
    });

    const dailyData = JSON.parse(document.getElementById('daily-data').textContent);
    const weeklyData = JSON.parse(document.getElementById('weekly-data').textContent);
    const monthlyData = JSON.parse(document.getElementById('monthly-data').textContent);
    const emissionSources = JSON.parse(document.getElementById('emission-source-data').textContent).map(Number);
    const totalEmission = JSON.parse(document.getElementById('total-emission').textContent);

    let currentData = monthlyData;
    let labels = currentData.length ? currentData.map(item => item.label) : ['No Data'];
    let totals = currentData.length ? currentData.map(item => item.total) : [0];

    // 1. Line Trend Chart Config
    const trendChart = new ApexCharts(
        document.querySelector("#emissionTrendChart"),
        {
            chart: {
                type: 'area',
                height: 280,
                toolbar: { show: false }
            },
            series: [{
                name: 'CO₂e',
                data: totals
            }],
            xaxis: { categories: labels },
            yaxis: {
                labels: {
                    formatter: function(val) { return val.toFixed(0); }
                }
            },
            stroke: { curve: 'smooth', width: 3 },
            fill: {
                type: 'gradient',
                gradient: { opacityFrom: 0.3, opacityTo: 0.05 }
            },
            colors: ['#166534'],
            dataLabels: { enabled: false }
        }
    );
    trendChart.render();

    document.getElementById('trendFilter').addEventListener('change', function () {
        let selected = this.value;
        let data = selected === 'daily' ? dailyData : (selected === 'weekly' ? weeklyData : monthlyData);

        const updatedLabels = data.length ? data.map(item => item.label) : ['No Data'];
        const updatedTotals = data.length ? data.map(item => item.total) : [0];

        trendChart.updateOptions({ xaxis: { categories: updatedLabels } });
        trendChart.updateSeries([{ name: 'CO₂e', data: updatedTotals }]);
    });

    // 2. Donut Chart Config
    const hasEmissionData = totalEmission > 0;
    const sourceChart = new ApexCharts(
        document.querySelector("#emissionSourceChart"),
        {
            chart: { 
                type: 'donut', 
                height: 210,
                sparkline: { enabled: true } 
            },
            series: emissionSources,
            labels: ['Transportation', 'Electricity', 'Food Consumption'],
            colors: ['#16a34a', '#eab308', '#ef4444', '#8b5cf6'],
            legend: { show: false },
            dataLabels: { enabled: false },
            tooltip: {
                y: {
                    formatter: function (value) { return value.toFixed(2) + " kg CO₂e"; }
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '75%',
                        labels: {
                            show: true,
                            value: {
                                show: true,
                                fontSize: '16px',
                                fontStyle: 'bold',
                                color: '#1f2937',
                                offsetVal: -4,
                                formatter: function(val) { return Number(val).toFixed(2); }
                            },
                            total: {
                                show: true,
                                showAlways: true,
                                label: 'Total Emissions',
                                color: '#6b7280',
                                fontSize: '11px',
                                formatter: function () {
                                    return Number(totalEmission).toFixed(2) + ' kg CO₂e';
                                }
                            }
                        }
                    }
                }
            }
        }
    );

    if (hasEmissionData) {
        document.getElementById("emptyDonut").classList.add("hidden");
        document.getElementById("emissionSourceChart").classList.remove("hidden");
        sourceChart.render();
    } else {
        document.getElementById("emissionSourceChart").classList.add("hidden");
        document.getElementById("emptyDonut").classList.remove("hidden");
    }

    /*
    |--------------------------------------------------------------------------
    | Emissions Comparison Chart
    |--------------------------------------------------------------------------
    */

    const comparisonData = JSON.parse(
        document.getElementById('comparison-data').textContent
    );

    const comparisonChart = new ApexCharts(
        document.querySelector("#comparisonChart"),
        {
            chart: {
                type: 'bar',
                height: 260,
                toolbar: {
                    show: false
                }
            },
            series: [
                {
                    name: 'This Month',
                    data: comparisonData.current
                },
                {
                    name: 'Last Month',
                    data: comparisonData.last
                }
            ],
            xaxis: {
                categories: [
                    'Transportation',
                    'Electricity',
                    'Food Consumption'
                ]
            },
            yaxis: {
                title: {
                    text: 'kg CO₂e'
                }
            },
            colors: [
                '#4f8b3a',
                '#9ca3af'
            ],
            plotOptions: {
                bar: {
                    columnWidth: '45%',
                    borderRadius: 4
                }
            },
            dataLabels: {
                enabled: false
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right'
            }
        }
    );

    comparisonChart.render();
</script>
@endpush
@endsection