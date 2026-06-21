@extends('layouts.admin')

@section('content')

<div class="p-4 space-y-4">

    <!-- Top Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">

        <!-- Total Users -->
        <div class="bg-white rounded-2xl shadow px-4 py-2 flex items-center gap-4">
    <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                <img src="{{ asset('icons/user.png') }}" class="w-7 h-7" alt="Users">
            </div>

            <div>
                <p class="text-sm text-gray-500">Total Users</p>
                <h2 class="text-2xl font-bold">{{ number_format($totalUsers) }}</h2>
                <p class="text-xs text-green-600">Registered users</p>
            </div>
        </div>

        <!-- Total Emissions -->
        <div class="bg-white rounded-2xl shadow px-4 py-2 flex items-center gap-4">
    <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                <img src="{{ asset('icons/emissions.png') }}" class="w-7 h-7" alt="Emissions">
            </div>

            <div>
                <p class="text-sm text-gray-500">Total Emissions</p>
                <h2 class="text-2xl font-bold">
                    {{ number_format($totalEmissions, 2) }}
                </h2>
                <p class="text-xs text-gray-500">kg CO₂e</p>
            </div>
        </div>

        <!-- Average -->
        <div class="bg-white rounded-2xl shadow px-4 py-2 flex items-center gap-4">
    <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                <img src="{{ asset('icons/user.png') }}" class="w-7 h-7" alt="Average">
            </div>

            <div>
                <p class="text-sm text-gray-500">Average / User</p>
                <h2 class="text-3xl font-bold">
                    {{ number_format($averageEmission, 2) }}
                </h2>
                <p class="text-xs text-gray-500">kg CO₂e</p>
            </div>
        </div>

        <!-- Mitigation -->
        <div class="bg-white rounded-2xl shadow px-4 py-2 flex items-center gap-4">
    <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                <img src="{{ asset('icons/mitigation.png') }}" class="w-7 h-7" alt="Mitigation">
            </div>

            <div>
                <p class="text-sm text-gray-500">Mitigation Actions</p>
                <h2 class="text-3xl font-bold">{{ $mitigationCount }}</h2>
            </div>
        </div>

        <!-- Reports -->
        <div class="bg-white rounded-2xl shadow px-4 py-3 flex items-center gap-4">
    <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                <img src="{{ asset('icons/audit.png') }}" class="w-7 h-7" alt="Reports">
            </div>

            <div>
                <p class="text-sm text-gray-500">SDO Reports</p>
                <h2 class="text-3xl font-bold">{{ $reportCount }}</h2>
            </div>
        </div>

    </div>

    <!-- Middle Section -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

        <!-- Emissions Trend -->
            <div class="bg-white rounded-2xl shadow p-5 xl:col-span-1">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold text-xl">
                    Emissions Trend
                </h3>

                <select id="trendFilter" class="border rounded-lg px-3 py-1 text-sm">
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly" selected>Monthly</option>
        </select>
            </div>

            <div class="h-48">
            <canvas id="emissionsTrendChart"></canvas>
        </div>
        </div>

        <!-- Emission by Source -->
<div class="bg-white rounded-2xl shadow p-5 min-h-[280px]">
    <h3 class="font-semibold text-xl mb-4">
        Emission by Source
    </h3>

    <div class="flex items-center gap-4">

        <!-- Donut Chart -->
        <div class="w-[42%] h-44">
            <canvas
            id="emissionSourceChart"
            data-transportation="{{ $transportationTotal }}"
            data-electricity="{{ $electricityTotal }}"
            data-food="{{ $foodTotal }}"
            data-waste="{{ $wasteTotal }}"
        ></canvas>
        </div>

       <!-- Legend -->
<div class="flex-1 space-y-3 text-sm min-w-0">

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 min-w-0">
            <span class="w-3 h-3 rounded-full bg-green-800 shrink-0"></span>
            <span class="truncate">Transportation</span>
        </div>
        <span class="shrink-0 ml-2">{{ number_format($transportationTotal, 2) }} kg</span>
    </div>

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 min-w-0">
            <span class="w-3 h-3 rounded-full bg-green-500 shrink-0"></span>
            <span class="truncate">Electricity</span>
        </div>
        <span class="shrink-0 ml-2">{{ number_format($electricityTotal, 2) }} kg</span>
    </div>

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 min-w-0">
            <span class="w-3 h-3 rounded-full bg-yellow-400 shrink-0"></span>
            <span class="truncate">Food</span>
        </div>
        <span class="shrink-0 ml-2">{{ number_format($foodTotal, 2) }} kg</span>
    </div>

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 min-w-0">
            <span class="w-3 h-3 rounded-full bg-orange-500 shrink-0"></span>
            <span class="truncate">Waste</span>
        </div>
        <span class="shrink-0 ml-2">{{ number_format($wasteTotal, 2) }} kg</span>
    </div>

</div>

    </div>
</div>

<!-- Top Emitting Departments -->
<div class="bg-white rounded-2xl shadow p-5">
    <h3 class="font-semibold text-xl mb-4">
        Top Emitting Departments
    </h3>

    @if($topDepartments->isEmpty())
        <p class="text-gray-500 text-sm">
            No department data available.
        </p>
    @else
        <div class="space-y-2 max-h-28 overflow-y-auto">
            @foreach($topDepartments as $department)

<div class="mb-4">

    <div class="flex justify-between text-sm mb-1">
        <span class="font-medium">
            {{ $department->department }}
        </span>

        <span class="text-gray-500">
            {{ $department->percentage }}%
        </span>
    </div>

    <div class="w-full bg-gray-200 rounded-full h-2">
        <div
            class="bg-green-700 h-2 rounded-full"
            @style(['width' => (int) $department->percentage . '%'])>
        </div>
    </div>

</div>

@endforeach
        </div>
    @endif
</div>

</div> <!-- End Middle Section -->

   <!-- Bottom Section -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

    <!-- Forecast -->
    <div class="bg-white rounded-2xl shadow p-5 min-h-[220px]">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-semibold text-xl">
                Forecasted Emissions
            </h3>

            <select id="forecastFilter" class="border rounded-lg px-3 py-1 text-sm">
            <option value="7">7 Days</option>
            <option value="30" selected>30 Days</option>
            <option value="60">60 Days</option>
            <option value="90">90 Days</option>
        </select>
        </div>

        <div class="h-[180px]">
        <canvas id="forecastChart"></canvas>
        </div>
    </div>

    <!-- User Engagement -->
<div class="bg-white rounded-2xl shadow p-5 min-h-[220px]">
    <h3 class="font-semibold text-xl mb-4">
        User Engagement
    </h3>

    <div class="flex items-center justify-between gap-2">

        <!-- Donut -->
        <div class="w-40 h-40 relative">
            <canvas
                id="engagementChart"
                data-engagement-rate="{{ $engagementRate }}"
            ></canvas>
        </div>

        <!-- Legend -->
<div class="flex-1 space-y-4 text-sm">

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-green-700"></span>
            <span>Active Today</span>
        </div>
        <span class="font-medium">{{ $activeUsers }}</span>
    </div>

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-lime-500"></span>
            <span>Active This Week</span>
        </div>
        <span class="font-medium">{{ $activeUsers }}</span>
    </div>

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-blue-500"></span>
            <span>New This Month</span>
        </div>
        <span class="font-medium">{{ $totalUsers }}</span>
    </div>

</div>
    </div>
</div>

    <!-- Recent Alerts -->
    <div class="bg-white rounded-2xl shadow p-5 min-h-[220px]">
        <h3 class="font-semibold text-xl mb-4">
            Recent Alerts
        </h3>

        @if($recentAlerts->isEmpty())

    <p class="text-gray-500 text-sm">
        No alerts available.
    </p>

@else

    <div class="space-y-3">

        @foreach($recentAlerts as $alert)

            <div class="flex items-center justify-between border-b pb-3">

                <!-- Left -->
                <div class="flex items-center gap-3">

                    <!-- Icon -->
                    <div
                        class="w-9 h-9 rounded-full flex items-center justify-center
                       @if($alert->severity == 'critical')
                            bg-red-100
                        @elseif($alert->severity == 'warning')
                            bg-yellow-100
                        @else
                            bg-blue-100
                        @endif

                        @if($alert->severity == 'critical')
                            <span class="text-red-600 text-lg">⚠</span>
                        @elseif($alert->severity == 'warning')
                            <span class="text-yellow-600 text-lg">!</span>
                        @else
                            <span class="text-blue-600 text-lg">i</span>
                        @endif
                    </div>

                    <!-- Text -->
                    <div>
                        <p class="text-sm font-medium">
                           {{ $alert->title }}
                        </p>
                    </div>

                </div>

                <!-- Time -->
                <span class="text-xs text-gray-400">
                   {{ $alert->created_at->diffForHumans() }}
                </span>

            </div>

        @endforeach

    </div>

@endif
    </div>

</div>

<!-- Recommended Mitigation Strategies -->
<div class="bg-white rounded-2xl shadow p-4">
    <h3 class="font-semibold text-xl mb-4">
        Recommended Mitigation Strategies
    </h3>

    @if($recommendedStrategies->isEmpty())
        <p class="text-gray-500">
            No mitigation strategies available.
        </p>
    @else
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">

    @foreach($recommendedStrategies as $strategy)

    <div class="flex items-start gap-3 border-r last:border-r-0 pr-3">

        <!-- Icon -->
        <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center shrink-0">
            <img src="{{ asset('icons/mitigation.png') }}"
                 class="w-6 h-6"
                 alt="Mitigation">
        </div>

        <!-- Content -->
        <div>
            <h4 class="font-semibold text-sm leading-tight">
                {{ $strategy->title }}
            </h4>

            <p class="text-xs text-gray-500 mt-1">
                {{ $strategy->description }}
            </p>

            @if(isset($strategy->carbon_reduced))
                <p class="text-xs text-green-700 mt-2">
                    Saves {{ number_format($strategy->carbon_reduced, 2) }} kg CO₂e
                </p>
            @endif
        </div>

    </div>

    @endforeach

</div>
@endif
</div>

</div>

<script id="monthly-emissions-data" type="application/json">
    @json($monthlyEmissions)
</script>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // =========================
    // Emission by Source Chart
    // =========================

    const sourceCanvas = document.getElementById('emissionSourceChart');

    if (sourceCanvas && typeof Chart !== 'undefined') {

        const chartData = [
            Number(sourceCanvas.dataset.transportation || 0),
            Number(sourceCanvas.dataset.electricity || 0),
            Number(sourceCanvas.dataset.food || 0),
            Number(sourceCanvas.dataset.waste || 0)
        ];

        const totalEmission = chartData.reduce((a, b) => a + b, 0);

        const centerTextPlugin = {
    id: 'centerText',

    afterDraw(chart) {
        const { ctx } = chart;
        const meta = chart.getDatasetMeta(0);

        if (!meta.data.length) return;

        const x = meta.data[0].x;
        const y = meta.data[0].y;

        ctx.save();

        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';

        // Total number
        ctx.font = 'bold 18px Arial';
        ctx.fillStyle = '#222';
        ctx.fillText(totalEmission.toFixed(2), x, y - 8);

        // Unit
        ctx.font = '12px Arial';
        ctx.fillStyle = '#666';
        ctx.fillText('kg CO₂e', x, y + 12);

        ctx.restore();
    }
};
        new Chart(sourceCanvas, {
         plugins: [centerTextPlugin],
            type: 'doughnut',
            data: {
                labels: [
                    'Transportation',
                    'Electricity',
                    'Food',
                    'Waste'
                ],
                datasets: [{
                    data: chartData,
                    backgroundColor: [
                        '#1B5E20',
                        '#66BB6A',
                        '#FBC02D',
                        '#FB8C00'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    // =========================
    // Emissions Trend Chart
    // =========================

    const trendCanvas = document.getElementById('emissionsTrendChart');

    if (trendCanvas && typeof Chart !== 'undefined') {

        const monthlyDataEl = document.getElementById('monthly-emissions-data');
        const monthlyData = monthlyDataEl
            ? JSON.parse(monthlyDataEl.textContent || '[]')
            : [];

        const trendChart = new Chart(trendCanvas, {
            type: 'line',
            data: {
                labels: [
                    'Jan','Feb','Mar','Apr','May','Jun',
                    'Jul','Aug','Sep','Oct','Nov','Dec'
                ],
                datasets: [{
                    label: 'CO₂ Emissions',
                    data: monthlyData,
                    borderColor: '#2E7D32',
                    backgroundColor: 'rgba(46,125,50,0.15)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Dropdown UI (placeholder)
        const filter = document.getElementById('trendFilter');

        if (filter) {
            filter.addEventListener('change', function () {

                if (this.value === 'daily') {
                    trendChart.data.labels =
                        ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
                }

                if (this.value === 'weekly') {
                    trendChart.data.labels =
                        ['Week 1','Week 2','Week 3','Week 4'];
                }

                if (this.value === 'monthly') {
                    trendChart.data.labels =
                        ['Jan','Feb','Mar','Apr','May','Jun',
                         'Jul','Aug','Sep','Oct','Nov','Dec'];
                }

                // Uses existing data until daily/weekly backend is implemented
                trendChart.update();
            });
        }
    }
        // Forecast Chart
const forecastCanvas = document.getElementById('forecastChart');

if (forecastCanvas && typeof Chart !== 'undefined') {

    const forecastChart = new Chart(forecastCanvas, {
        type: 'line',
        data: {
            labels: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7'],
            datasets: [{
                label: 'Forecast',
                data: [0, 0, 0, 0, 0, 0, 0], // Replace with real forecast data later
                borderColor: '#2E7D32',
                backgroundColor: 'rgba(46,125,50,0.15)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    const filter = document.getElementById('forecastFilter');

    if (filter) {
        filter.addEventListener('change', function () {

            if (this.value === '7') {
                forecastChart.data.labels = [
                    'Day 1','Day 2','Day 3','Day 4','Day 5','Day 6','Day 7'
                ];

            } else if (this.value === '30') {
                forecastChart.data.labels = [
                    'Week 1','Week 2','Week 3','Week 4'
                ];

            } else if (this.value === '60') {
                forecastChart.data.labels = [
                    'Month 1','Month 2'
                ];

            } else if (this.value === '90') {
                forecastChart.data.labels = [
                    'Month 1','Month 2','Month 3'
                ];
            }

            // Keep placeholder values at 0 until forecasting is implemented
            forecastChart.data.datasets[0].data =
                new Array(forecastChart.data.labels.length).fill(0);

            forecastChart.update();
        });
    }
}
// =========================
// User Engagement Chart
// =========================

const engagementCanvas = document.getElementById('engagementChart');

if (engagementCanvas && typeof Chart !== 'undefined') {

    const engagementRate = Number(engagementCanvas.dataset.engagementRate || 0);

    const engagementPlugin = {
        id: 'engagementCenter',
        afterDraw(chart) {

            const { ctx } = chart;
            const meta = chart.getDatasetMeta(0);

            if (!meta.data.length) return;

            const x = meta.data[0].x;
            const y = meta.data[0].y;

            ctx.save();

            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';

            ctx.font = 'bold 26px Arial';
            ctx.fillStyle = '#2E7D32';
            ctx.fillText(engagementRate + '%', x, y - 8);

            ctx.font = '14px Arial';
            ctx.fillStyle = '#666';
            ctx.fillText('Active Users', x, y + 18);

            ctx.restore();
        }
    };

    new Chart(engagementCanvas, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [engagementRate, 100 - engagementRate],
                backgroundColor: ['#2E7D32', '#E0E0E0'],
                borderWidth: 0
            }]
        },
        options: {
            cutout: '80%',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        },
        plugins: [engagementPlugin]
    });
} 
});
</script>
@endpush