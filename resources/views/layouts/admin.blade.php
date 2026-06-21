<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarbonWise</title>

    @vite(['resources/css/app.css','resources/js/app.js'])
</head>

<body class="bg-gray-100">

<div class="flex h-screen">

    <!-- Sidebar -->
    <aside class="w-72 h-screen bg-green-800 text-white flex flex-col">
        <div class="p-5 border-b border-green-700 flex items-center gap-3">

            <img
                src="{{ asset('images/carbonwise-logo.png') }}"
                class="w-14 h-14 rounded-full bg-white p-1"
            >

            <div>
                <h1 class="font-bold text-xl">
                    CarbonWise
                </h1>
            </div>

        </div>

        <nav class="flex-1 py-4 overflow-hidden">
            
    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700" href="/dashboard">
        <img src="{{ asset('icons/dashboard.png') }}" class="w-5 h-5">
        <span>Overview</span>
    </a>

    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700" href="#">
        <img src="{{ asset('icons/user.png') }}" class="w-5 h-5">
        <span>User Management</span>
    </a>

    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700" href="#">
        <img src="{{ asset('icons/emissions.png') }}" class="w-5 h-5">
        <span>Emissions Overview</span>
    </a>

    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700" href="#">
        <img src="{{ asset('icons/analytics.png') }}" class="w-5 h-5">
        <span>Analytics & Reports</span>
    </a>

    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700" href="#">
        <img src="{{ asset('icons/forecast.png') }}" class="w-5 h-5">
        <span>Forecasting</span>
    </a>

    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700" href="#">
        <img src="{{ asset('icons/mitigation.png') }}" class="w-5 h-5">
        <span>Mitigation Strategies</span>
    </a>

    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700" href="#">
        <img src="{{ asset('icons/alerts.png') }}" class="w-5 h-5">
        <span>Alerts & Notifications</span>
    </a>

    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700" href="#">
        <img src="{{ asset('icons/sdo.png') }}" class="w-5 h-5">
        <span>SDO Monitoring</span>
    </a>

    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700" href="#">
        <img src="{{ asset('icons/settings.png') }}" class="w-5 h-5">
        <span>Settings</span>
    </a>

    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700" href="#">
        <img src="{{ asset('icons/audit.png') }}" class="w-5 h-5">
        <span>Audit Logs</span>
    </a>

    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700" href="#">
        <img src="{{ asset('icons/help.png') }}" class="w-5 h-5">
        <span>Help & Support</span>
    </a>

</nav>

        <div class="mt-auto p-4 border-t border-green-700">
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button
            type="submit"
            class="w-full bg-green-700 hover:bg-green-600 text-white py-3 rounded-xl font-semibold transition"
        >
            Log Out
        </button>
    </form>
</div>

    </aside>

    <!-- Main -->
    <main class="flex-1 overflow-y-auto">

        <div class="bg-white shadow px-8 py-4 flex justify-between items-center">

    <!-- Left -->
    <div class="flex items-center gap-4">

        <button class="text-3xl text-gray-700">
            ☰
        </button>

        <div>
            <h2 class="text-2xl font-bold">
                Overview
            </h2>

            <p class="text-gray-500">
                Welcome Back, {{ Auth::user()->name }}!
            </p>
        </div>

    </div>

    <!-- Right -->
    <div class="flex items-center gap-6">

        <!-- Month Filter -->
        <select
            class="border rounded-lg px-4 py-2 bg-white shadow-sm"
        >
            <option>This Month</option>
            <option>January {{ now()->year }}</option>
            <option>February {{ now()->year }}</option>
            <option>March {{ now()->year }}</option>
            <option>April {{ now()->year }}</option>
            <option>May {{ now()->year }}</option>
            <option>June {{ now()->year }}</option>
            <option>July {{ now()->year }}</option>
            <option>August {{ now()->year }}</option>
            <option>September {{ now()->year }}</option>
            <option>October {{ now()->year }}</option>
            <option>November {{ now()->year }}</option>
            <option>December {{ now()->year }}</option>
        </select>

        <!-- Admin Info -->
        <div class="text-right">
            <div class="font-bold text-green-700">
                {{ Auth::user()->name }}
            </div>

            <div class="text-sm text-gray-500">
                Administrator
            </div>
        </div>

    </div>

</div>

       <div class="p-6">
    @yield('content')
</div>
    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

@stack('scripts')

</body>
</html>