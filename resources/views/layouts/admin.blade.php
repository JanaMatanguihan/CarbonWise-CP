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

        <nav class="flex-1 py-4">

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

        <div class="p-4 border-t border-green-700">
            <form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit"
        style="
            width:100%;
            background:#2F6E49;
            color:white;
            border:none;
            border-radius:10px;
            padding:12px;
            font-weight:600;
        ">
        Log Out
    </button>
</form>
        </div>

    </aside>

    <!-- Main -->
    <main class="flex-1 overflow-y-auto">

        <div class="bg-white shadow px-8 py-4 flex justify-between items-center">

            <div>
                <h2 class="font-bold text-2xl">
                    Overview
                </h2>

                <p class="text-gray-500">
                    Welcome back, {{ Auth::user()->name }}!
                </p>
            </div>

            <div class="text-right">
                <div class="font-semibold">
                    {{ now()->format('F d, Y') }}
                </div>

                <div class="text-sm text-gray-500">
                    Administrator
                </div>
            </div>

        </div>

        <div class="py-12">
            @yield('content')
        </div>

    </main>

</div>

</body>
</html>